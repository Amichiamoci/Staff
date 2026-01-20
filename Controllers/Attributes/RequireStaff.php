<?php

namespace Amichiamoci\Controllers\Attributes;

use Richie314\SimpleMvc\Controllers\Controller as BaseController;
use Richie314\SimpleMvc\Controllers\Attributes\Attribute;

use Amichiamoci\Controllers\Controller;

#[\Attribute]
class RequireStaff
implements Attribute
{
    public function __construct(
        private bool $requireAdmin = false, 
        private string $loginPath = '/login',
        private ?string $commissione = null,
    ) {}

    public function DoWork(BaseController $controller, string $action, array $parameters): void
    {
        Controller::RequireStaff(
            controller: $controller, 
            requireAdmin: $this->requireAdmin,
            loginPath: $this->loginPath,
            commissione: $this->commissione,
        );
    }
}