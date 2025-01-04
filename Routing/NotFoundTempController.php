<?php

namespace Amichiamoci\Routing;
use Amichiamoci\Controllers\Controller;

class NotFoundTempController extends Controller
{
    public function NotFoundHandler(): int
    {
        return $this->NotFound();
    }
}