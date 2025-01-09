<?php

namespace Amichiamoci\Controllers;

use Amichiamoci\Models\User;
use Amichiamoci\Utils\Cookie;

class UserController extends Controller {
    public function logout(): int
    {
        $this->RequireLogin()->Logout();
        Cookie::DeleteIfExists(name: "login_forward");
        return $this->Redirect(url: '/');
    }

    public function delete(?int $target_id): int {
        $this->RequireLogin(require_admin: true);

        if (!isset($target_id)) {
            return $this->NotFound();
        }
        
        $user = User::ById(connection: $this->DB, id: $target_id);
        if (!isset($user)) {
            return $this->NotFound();
        }
        
        // User::Delete(connection: $this->DB, target: $target_id);
    }

    public function reset(?int $target_id) {
        $this->RequireLogin(require_admin: true);
        
        // User::ResetPassword(connection: $this->DB, target: $target_id);
    }

    public function ban(?int $target_id) {
        $this->RequireLogin(require_admin: true);
        
        // User::Ban(connection: $this->DB, target: $target_id);
    }

    public function restore(?int $target_id) {
        $this->RequireLogin(require_admin: true);
        
        // User::Restore(connection: $this->DB, target: $target_id);
    }

    public function password_recover(?string $username) {
        
    }

    public function view(?int $id): int {
        if (!isset($id)) {
            return $this->BadRequest();
        }
        
        $user = $this->RequireLogin();
        $target = User::ById(connection: $this->DB, id: $id);
        if (!isset($target)) {
            return $this->NotFound();
        }

        if ($target->Id !== $user->Id && !$user->IsAdmin) { 
            // Admins can view personal pages of others
            return $this->NotAuthorized();
        }
        return $this->Render(
            view: 'User/view',
            title: $target->Name,
            data: [
                'target' => $target,
            ],
        );
    }

    public function me(): int {
        $user = $this->RequireLogin();
        return $this->view(id: $user->Id);
    }

    public function all(): int {
        $this->RequireLogin(require_admin: true);
        return $this->Render(
            view: 'User/all', 
            title: 'Utenti',
            data: ['users' => User::All(connection: $this->DB)]
        );
    }

    public function activity(): int {
        $this->RequireLogin(require_admin: true);
        return $this->Render(
            view: 'User/activity',
            title: 'Attività utenti',
            data: ['activity' => User::Activity(connection: $this->DB)]
        );
    }
}