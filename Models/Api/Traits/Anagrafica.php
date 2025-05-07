<?php

namespace Amichiamoci\Models\Api\Traits;

use Amichiamoci\Models\Anagrafica as ModelsAnagrafica;
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

    private static function managed_anagraphicals_parse(array $r): array 
    {
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
            $base['Status']['Scadenza documento'] = $r['scadenza_problem'];
        }
        if (isset($r['stato_certificato']) && isset($r['id_parrocchia']) && isset($r['id_iscrizione']))
        {
            $base['Status']['Certificato medico'] = $r['stato_certificato'];
        }

        return $base;
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
            is_procedure: $Id === null,
        );
    }

    protected function anagraphical(
        string $Name,
        string $Surname,
        string $TaxCode,
        string $Email,
        string $DocumentCode,
        string $DocumentExpiration,
        int $DocumentType,

        ?string $BirthDate = null,
        ?string $BirthPlace = null,
        ?string $Phone = null,
        ?int $Id = null,
    ): ApiCall
    {
        $a = ModelsAnagrafica::FromFiscalCode(connection: $this->DB, cf: $TaxCode);
        if (isset($a) && $a->Id !== $Id)
        {
            // Trying to change someone-else's data
            throw new \InvalidArgumentException(message: 'Invalid TaxCode or Id');
        }
        
        $id = ModelsAnagrafica::CreateOrUpdate(
            connection: $this->DB,
            nome: $Name,
            cognome: $Surname,
            compleanno: $BirthDate,
            provenienza: $BirthPlace ?? "Sconosciuto",
            tel: $Phone,
            email: $Email,
            cf: $TaxCode,
            doc_type: $DocumentType,
            doc_code: $DocumentCode,
            doc_expires: $DocumentExpiration,
            nome_file: '',
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