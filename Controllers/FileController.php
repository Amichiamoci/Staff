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
        if ($name === null || File::IsExternalFile($name)) {
            return $this->NotFound();
        }

        $path = File::PhysicalPath($name);
        if ($path === false || !is_file($path)) {
            return $this->NotFound();
        }

        $relativePath = File::VirtualPath($path);
        if (!$this->getUser()->Admin && (
            $relativePath === false
            || (!str_starts_with($relativePath, File::Documents . '/') && !str_starts_with($relativePath, File::Certificates . '/'))
        )) {
            return $this->NotFound();
        }

        return $this->File(file_path: $path);
    }

    #[RequireLogin(requireAdmin: true)]
    public function list(): StatusCode
    {
        return $this->Render(
            view: 'File/list',
            title: 'Lista dei File',
            data: ['tree' => File::ListDirectory(dir: File::STAFF)]
        );
    }

    #[RequireLogin(requireAdmin: true)]
    public function unreferenced(): StatusCode
    {
        $files = AnagraficaConIscrizione::UnreferencedFiles(connection: $this->DB);
        if ($this->IsPost())
        {
            $ok = true;
            $not_cancelled = [];
            foreach ($files as $file)
            {
                if (!File::Delete(file_path: $file))
                {
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
                'files' => $files,
            ],
        );
    }
}