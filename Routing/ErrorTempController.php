<?php

namespace Amichiamoci\Routing;
use Amichiamoci\Controllers\Controller;

class ErrorTempController extends Controller
{
    public function NotFoundHandler(): int
    {
        return $this->NotFound();
    }
    public function InternalErrorHandler(?\Throwable $ex = null): int
    {
        return $this->InternalError(ex: $ex);
    }
}