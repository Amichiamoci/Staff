<?php

namespace Amichiamoci\Controllers;
use Amichiamoci\Utils\File;

class FileController extends Controller
{
    public function index(?string $name = null): int {
        $this->RequireLogin();
        
        if (!is_string(value: $name)) {
            return $this->NotFound();
        }

        return $this->File(file_path: File::ServerPath(db_path: $name));
    }

    public function list(): int {
        $this->RequireLogin(require_admin: true);
        return $this->Render(
            view: 'File/list',
            title: 'Lista dei File',
            data: ['tree' => File::ListDirectory(dir: SERVER_UPLOAD_PATH)]
        );
    }
}