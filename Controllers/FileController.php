<?php

namespace Amichiamoci\Controllers;

use Richie314\SimpleMvc\Controllers\Attributes\RequireLogin;
use Richie314\SimpleMvc\Http\StatusCode;

use Amichiamoci\Models\AnagraficaConIscrizione;
use Amichiamoci\Models\Message;
use Amichiamoci\Utils\File;

class FileController
extends Controller
{
    #[RequireLogin]
    public function index(?string $name = null): StatusCode
    {
        if (!is_string(value: $name))
            return $this->NotFound();

        if (File::IsExternalFile(filename: $name))
            return $this->Redirect(url: File::GetExportUrl(path: $name));

        return $this->File(file_path: File::ServerPath(db_path: $name));
    }

    #[RequireLogin(requireAdmin: true)]
    public function list(): StatusCode
    {
        return $this->Render(
            view: 'File/list',
            title: 'Lista dei File',
            data: ['tree' => File::ListDirectory(dir: SERVER_UPLOAD_PATH)]
        );
    }

    #[RequireLogin(requireAdmin: true)]
    public function unreferenced(): StatusCode
    {
        $files = array_map(
            callback: function(string $file): string { return SERVER_UPLOAD_PATH . $file; },
            array: AnagraficaConIscrizione::UnreferencedFiles(connection: $this->DB)
        );
        if ($this->IsPost())
        {
            $ok = true;
            $not_cancelled = [];
            foreach ($files as $file)
            {
                if (!File::Delete(file_path: $file)) {
                    $ok = false;
                    $not_cancelled[] = $file;
                }
            }
            if ($ok) {
                $this->Message(message: Message::Success(
                    content: count(value: $files) . ' file cancellati')
                );
            } else {
                $this->Message(message: Message::Error(
                    content: (count(value: $files) - count(value: $not_cancelled)) . 
                        '/' . count(value: $files) . ' file cancellati!')
                );
            }
            $files = $not_cancelled;
        }
        
        return $this->Render(
            view: 'File/unreferenced',
            title: 'Elimina file vecchi',
            data: [
                'files' => array_map(callback: function(string $file): string {
                    return File::AbsoluteToDbPath(server_path: $file);
                }, array: $files),
            ],
        );
    }
}