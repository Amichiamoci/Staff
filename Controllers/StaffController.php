<?php

namespace Amichiamoci\Controllers;
use Amichiamoci\Models\Staff;

class StaffController extends Controller
{
    public function index(): int {
        $this->RequireStaff();
        return $this->Render(
            view: 'Staff/index',
            title: 'Portale Staff',
        );
    }

    public function all(): int {
        $this->RequireStaff();
        return $this->Render(
            view: 'Staff/all',
            data: ['staffs' => Staff::All(connection: $this->DB)],
            title: 'Tutti gli Staffisti'
        );
    }

    public function current(): int {
        $this->RequireStaff();
        return $this->Render(
            view: 'Staff/all',
            data: ['staffs' => Staff::All(connection: $this->DB)],
            title: 'Tutti gli Staffisti'
        );
    }
}