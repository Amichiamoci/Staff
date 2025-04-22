<?php

namespace Amichiamoci\Models\Api\Traits;
use Amichiamoci\Models\Api\Call as ApiCall;
use Amichiamoci\Models\Taglia;

trait Anagrafica
{
    protected function document_types(): ApiCall
    {
        return new ApiCall(
            query: "SELECT * FROM `tipi_documento`",
            row_parser: function (array $r): array {
                return [
                    'Id' => (int)$r['id'],
                    'Label' => $r['label'],
                ];
            }
        );
    }

    protected function managed_anagraphicals(string $Email): ApiCall
    {
        $email = $this->DB->escape_string($Email);
        $query = 
            "SELECT * " .
            "FROM `anagrafiche_con_iscrizioni_correnti` " .
            "WHERE LOWER(TRIM(`email`)) = LOWER(TRIM('$email')) OR LOWER(TRIM(`email_tutore`)) = LOWER(TRIM('$email'))";
        return new ApiCall(
            query: $query,
            row_parser: function (array $r): array {
                $base = [
                    'Id' => (int)$r['id'],
                    'Name' => $r['nome'],
                    'Surname' => $r['cognome'],
                    
                    'TaxCode' => $r['cf'],
                    
                    'Phone' => is_string(value: $r['telefono']) && strlen(string: $r['telefono']) > 0 ? $r['telefono'] : null,
                    'Email' => is_string(value: $r['email']) && strlen(string: $r['email']) > 0 ? $r['email'] : null,
                    
                    'BirthDate' => $r['data_nascita_italiana'],
                    //'BirthPlace' => $r['luogo_nascita'],
                    
                    'Document' => [
                        'Code' => $r['codice_documento'],
                        'Type' => [
                            'Id' => (int)$r['tipo_documento'],
                            'Label' => $r['nome_tipo_documento'],
                        ],
                    ],

                    // 'Subscription' => null,

                    'Status' => [ ],
                ];

                if (isset($r['id_parrocchia']) && isset($r['id_iscrizione']))
                {
                    $base['Subscription'] = [
                        'Id' => (int)$r['id_iscrizione'],
                        'Church' => [
                            'Id' => (int)$r['id_parrocchia'],
                            'Name' => $r['parrocchia'],
                        ],
                        'Shirt' => $r['maglia'],
                    ];
                }

                if (isset($r['scadenza_problem']))
                {
                    $base['Status'][] = $r['scadenza_problem'];
                }
                if (isset($r['stato_certificato']))
                {
                    $base['Status'][] = $r['stato_certificato'];
                }

                return $base;
            }
        );
    }

    protected function subscribe(
        int $Anagraphical,
        int $Church,
        string $Shirt,
        ?int $Id = null,

        ?int $Tutor = null,
    ): ApiCall
    {
        $taglia = Taglia::from(value: $Shirt)->value;
        $tutor = empty($Tutor) ? 'NULL' : $Tutor;

        return new ApiCall(
            query: 
                ($Id === null) ?
                    "CALL `IscriviEdizioneCorrente`($Anagraphical, $Church, '$Shirt', $tutor);" :
                    "UPDATE `iscritti` SET `dati_anagrafici` = $Anagraphical, `parrocchia` = $Church, `taglia_maglietta` = $Shirt, `tutore` = $tutor WHERE `id` = $Id",
            row_parser: function (array $r) use($Anagraphical, $Church, $taglia, $Tutor): array
            {
                return [
                    'Anagraphical' => $Anagraphical,
                    'Church' => $Church,
                    'Shirt' => $taglia,
                    'Tutor' => $Tutor,
                    'Id' => (int)$r['id'],
                ];
            },
            is_procedure: $Id === null,
        );
    }
}