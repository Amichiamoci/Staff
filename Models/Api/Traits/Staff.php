<?php

namespace Amichiamoci\Models\Api\Traits;
use Amichiamoci\Models\Api\Call as ApiCall;

trait Staff
{
    protected function staff_list(): ApiCall
    {
        return new ApiCall(
            query: 'SELECT * FROM `staffisti_attuali`',
            row_parser: function (array $r): array {
                return [
                    'Name' => $r['chi'],
                    'ChurchId' => (int)$r['id_parrocchia'],
                    
                    'Phone' => is_string(value: $r['telefono']) && strlen(string: $r['telefono']) > 0 ? $r['telefono'] : null,
                    'Email' => is_string(value: $r['email']) && strlen(string: $r['email']) > 0 ? $r['email'] : null,
                ];
            }
        );
    }
}