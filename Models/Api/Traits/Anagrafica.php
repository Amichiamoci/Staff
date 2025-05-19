<?php

namespace Amichiamoci\Models\Api\Traits;

use Amichiamoci\Models\Anagrafica as ModelsAnagrafica;
use Amichiamoci\Models\Api\Call as ApiCall;
use Amichiamoci\Models\Iscrizione;
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

    private static function managed_anagraphicals_parse(array $r): array 
    {
        $base = [
            'Id' => (int)$r['id'],
            'Name' => $r['nome'],
            'Surname' => $r['cognome'],
            
            'TaxCode' => $r['cf'],
            
            'Phone' => is_string(value: $r['telefono']) && strlen(string: $r['telefono']) > 0 ? $r['telefono'] : null,
            'Email' => is_string(value: $r['email']) && strlen(string: $r['email']) > 0 ? $r['email'] : null,
            
            //'BirthDate' => $r['data_nascita_italiana'],
            'BirthDate' => $r['data_nascita'],
            'BirthPlace' => $r['luogo_nascita'],
            
            'Document' => [
                'Code' => $r['codice_documento'],
                'Expiration' => $r['scadenza'] . 'T12:00:00',
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
            $base['Status']['Scadenza documento'] = $r['scadenza_problem'];
        }
        if (
            isset($r['stato_certificato']) && 
            strtolower(string: $r['stato_certificato']) !== 'presente' && 
            isset($r['id_parrocchia']) && 
            isset($r['id_iscrizione'])
        ) {
            $base['Status']['Certificato medico'] = $r['stato_certificato'];
        }

        return $base;
    }

    private static function managed_anagraphicals_parse_subscription(array $r): array
    {
        $whole_record = self::managed_anagraphicals_parse($r);
        return $whole_record['Subscription'];
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
            row_parser: function (array $r): array { return self::managed_anagraphicals_parse($r); },
        );
    }

    protected function subscribe(
        int $Anagraphical,
        int $Church,
        string $Shirt,
        ?int $Id = null,

        ?string $Certificate = null,
        ?int $Tutor = null,
    ): ApiCall
    {
        if (empty($Anagraphical))
        {
            throw new \InvalidArgumentException(message: 'Invalid Anagraphical');
        }
        $taglia = Taglia::from(value: $Shirt)->value;

        if (!empty($Id))
        {
            // Update existing record
            $this->DB->execute_query(
                "UPDATE `iscritti` 
                SET `parrocchia` = ?, 
                `taglia_maglietta` = ?, 
                `tutore` = IFNULL(?, `tutore`),
                `certificato_medico` = IFNULL(?, `certificato_medico`)  
                WHERE `id` = ? AND `dati_anagrafici` = ?",
                [
                    $Church,
                    $taglia,
                    $Tutor,
                    $Certificate,
                    $Id,
                    $Anagraphical,
                ],
            );

            return new ApiCall(
                query: "SELECT * FROM `anagrafiche_con_iscrizioni_correnti` WHERE `id` = $Anagraphical",
                row_parser: function (array $r): array { return self::managed_anagraphicals_parse_subscription($r); },
            );
        }
        $tutor = empty($Tutor) ? 'NULL' : (string)$Tutor;
        $certificate = empty($Certificate) ? 'NULL' : '\'' . $this->DB->escape_string($Certificate) . '\'';
        
        return new ApiCall(
            query: "CALL `IscriviEdizioneCorrente`($Anagraphical, $Church, '$taglia', $tutor, $certificate);",
            row_parser: function (array $r) use($Church, $taglia, $Tutor): array
            {
                return [
                    'Church' => [
                        'Id' => $Church,
                        'Name' => $r['nome_parrocchia'],
                    ],
                    'Shirt' => $taglia,
                    'Tutor' => $Tutor,
                    'Id' => (int)$r['id'],
                ];
            },
            is_procedure: true,
        );
    }

    protected function anagraphical(
        string $Name,
        string $Surname,
        string $Tax_Code,
        string $Email,

        string $Document_Code,
        string $Document_Expiration,
        int $Document_Type,

        ?string $Birth_Date = null,
        ?string $Birth_Place = null,
        ?string $Phone = null,
        ?string $Document_Url = null,
        ?int $Id = null,
    ): ApiCall
    {
        $a = ModelsAnagrafica::FromFiscalCode(connection: $this->DB, cf: $Tax_Code);
        if (isset($a) && $a->Id !== $Id)
        {
            // Trying to change someone-else's data
            throw new \InvalidArgumentException(message: 'Invalid TaxCode or Id');
        }
        
        $id = ModelsAnagrafica::CreateOrUpdate(
            connection: $this->DB,
            nome: $Name,
            cognome: $Surname,
            compleanno: $Birth_Date,
            provenienza: $Birth_Place ?? "Sconosciuto",
            tel: $Phone,
            email: $Email,
            cf: $Tax_Code,
            doc_type: $Document_Type,
            doc_code: $Document_Code,
            doc_expires: $Document_Expiration,
            nome_file: empty($Document_Url) ? '' : $Document_Url,
        );
        if (!empty($Id) && $Id !== $id)
        {
            throw new \LogicException(message: 'Internal data misalignment');
        }

        return new ApiCall(
            query: "SELECT * FROM `anagrafiche_con_iscrizioni_correnti` WHERE `id` = $id",
            row_parser: function (array $r): array { return self::managed_anagraphicals_parse($r); },
        );
    }
}