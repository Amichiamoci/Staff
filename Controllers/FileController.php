<?php

namespace Amichiamoci\Controllers;
use Amichiamoci\Utils\File;

class FileController extends Controller
{
    public function index(?string $target = null): int {
        $this->RequireLogin();
        
        if (!is_string(value: $target)) {
            return $this->NotFound();
        }

        return $this->File(file_path: File::ServerPath(db_path: $target));
    }
}