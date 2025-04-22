<?php

namespace Amichiamoci\Models\Api\Traits;
use Amichiamoci\Models\Api\Call as ApiCall;

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
                return [
                    'Id' => (int)$r['id'],
                    'Name' => $r['nome'],
                    'Surname' => $r['cognome'],
                    
                    'Phone' => is_string(value: $r['telefono']) && strlen(string: $r['telefono']) > 0 ? $r['telefono'] : null,
                    'Email' => is_string(value: $r['email']) && strlen(string: $r['email']) > 0 ? $r['email'] : null,
                    
                    'TaxCode' => $r['cf'],
                    'BirthDate' => $r['data_nascita_italiana'],
                    
                    'Document' => [
                        'TypeId' => (int)$r['tipo_documento'],
                        'TypeName' => $r['nome_tipo_documento'],
                        'Code' => $r['codice_documento'],
                        'Message' => $r['scadenza_problem'],
                    ],
    
                    'MedicalCertificate' => $r['stato_certificato'],
                    'SubscriptionStatus' => $r['codice_iscrizione'],
                    'ShirtSize' => $r['maglia'],
    
                    'Church' => $r['parrocchia'],
                    'ChurchId' => (int)$r['id_parrocchia'],
                ];
            }
        );
    }
}