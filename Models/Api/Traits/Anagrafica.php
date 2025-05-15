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
        if (isset($r['stato_certificato']) && isset($r['id_parrocchia']) && isset($r['id_iscrizione']))
        {
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
                `tutore` = IFNULL(?, `tutore`) 
                WHERE `id` = ? AND `dati_anagrafici` = ?",
                [
                    $Church,
                    $taglia,
                    $Tutor,
                    $Id,
                    $Anagraphical,
                ],
            );

            return new ApiCall(
                query: "SELECT * FROM `anagrafiche_con_iscrizioni_correnti` WHERE `id` = $Anagraphical",
                row_parser: function (array $r): array { return self::managed_anagraphicals_parse_subscription($r); },
            );
        }
        $tutor = empty($Tutor) ? 'NULL' : $Tutor;
        
        return new ApiCall(
            query: "CALL `IscriviEdizioneCorrente`($Anagraphical, $Church, '$taglia', $tutor);",
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

    protected function subscription_certificate(
        int $Anagraphical,
        int $Id,
        string $Certificate,
    ): ApiCall
    {
        if (empty($Id) || empty($Anagraphical) || strlen(string: $Certificate) === 0)
        {
            throw new \InvalidArgumentException(message: 'Inspecified Id or Certificate');
        }
        if (!Iscrizione::UpdateCertificato(connection: $this->DB, id: $Id, certificato: $Certificate))
        {
            throw new \Exception(message: 'Cannot update certificate for subscription ' . $Id);
        }

        return new ApiCall(
            query: "SELECT * FROM `anagrafiche_con_iscrizioni_correnti` WHERE `id` = $Anagraphical",
            row_parser: function (array $r): array { return self::managed_anagraphicals_parse_subscription($r); },
        );
    }

    protected function anagraphical(
        string $Name,
        string $Surname,
        string $Taxcode,
        string $Email,

        string $Documentcode,
        string $Documentexpiration,
        int $Documenttype,

        ?string $Birthdate = null,
        ?string $Birthplace = null,
        ?string $Phone = null,
        ?string $Documenturl = null,
        ?int $Id = null,
    ): ApiCall
    {
        $a = ModelsAnagrafica::FromFiscalCode(connection: $this->DB, cf: $Taxcode);
        if (isset($a) && $a->Id !== $Id)
        {
            // Trying to change someone-else's data
            throw new \InvalidArgumentException(message: 'Invalid TaxCode or Id');
        }
        
        $id = ModelsAnagrafica::CreateOrUpdate(
            connection: $this->DB,
            nome: $Name,
            cognome: $Surname,
            compleanno: $Birthdate,
            provenienza: $Birthplace ?? "Sconosciuto",
            tel: $Phone,
            email: $Email,
            cf: $Taxcode,
            doc_type: $Documenttype,
            doc_code: $Documentcode,
            doc_expires: $Documentexpiration,
            nome_file: empty($Documenturl) ? '' : $Documenturl,
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