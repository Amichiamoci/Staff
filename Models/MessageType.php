<?php

namespace Amichiamoci\Models;

enum MessageType: string {
    case Info = 'info';
    case Warning = 'warning';
    case Success = 'success';
    case Error = 'danger';
    case Primary = 'primary';
    case Secondary = 'secondary';

    public function Icon(): string {
        return match($this) {
            self::Info => 'bi-info-circle',
            self::Warning => 'bi-exclamation-triangle',
            self::Success => 'bi-check-circle',
            self::Error => 'bi-exclamation-octagon-fill',
            self::Primary => 'bi-info-square-fill',
            self::Secondary => 'bi-info-square-fill',
        };
    }
}