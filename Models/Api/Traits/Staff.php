<?php

namespace Amichiamoci\Models\Api\Traits;
use Amichiamoci\Models\Api\Call as ApiCall;

trait Staff
{
    protected function staff_list(): ApiCall
    {
        return new ApiCall(
            query: 'SELECT * FROM `staff_attuali`',
            row_parser: function (array $r): array {
                return [
                    'Name' => $r['nome'] . ' ' . $r['cognome'],
                    'ChurchId' => (int)$r['id_parrocchia'],
                    
                    'Phone' => is_string(value: $r['telefono']) && strlen(string: $r['telefono']) > 0 ? $r['telefono'] : null,
                    'Email' => is_string(value: $r['email']) && strlen(string: $r['email']) > 0 ? $r['email'] : null,
                ];
            }
        );
    }

    protected function get_user_claims(string $Email): ApiCall
    {
        return new ApiCall(
            query: "CALL `GetAppUserClaims`(?)",
            parameters: [$Email],
            is_procedure: true,
            row_parser: function (array $r): array {
                return [
                    'Admin' => (bool)$r['admin'],
                ];
            }
        );
    }

    protected function get_staff_email(string $Name, string $Surname): ApiCall
    {
        return new ApiCall(
            query: 
                "SELECT TRIM(p.`email`) AS \"email\" " . 
                "FROM `partecipazioni_staff` p " . 
                "WHERE p.`nome` = ? AND p.`cognome` = ? AND p.`email` IS NOT NULL " . 
                "LIMIT 1",
            parameters: [$Name, $Surname],
            row_parser: function (array $r): array {
                return [ 'email' => $r['email'],];
            }
        );
    }
}