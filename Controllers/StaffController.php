<?php

namespace Amichiamoci\Controllers;

use Amichiamoci\Models\Anagrafica;
use Amichiamoci\Models\AnagraficaConIscrizione;
use Amichiamoci\Models\Templates\Anagrafica as AnagraficaBase;
use Amichiamoci\Models\Message;
use Amichiamoci\Models\MessageType;
use Amichiamoci\Models\Parrocchia;
use Amichiamoci\Models\Staff;
use Amichiamoci\Models\StaffBase;
use Amichiamoci\Models\TesseramentoCSI;
use Amichiamoci\Models\TipoDocumento;
use Amichiamoci\Utils\File;

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
            data: ['staffs' => StaffBase::All(connection: $this->DB)],
            title: 'Staffisti di sempre'
        );
    }

    public function current(): int {
        $this->RequireStaff();
        return $this->Render(
            view: 'Staff/all',
            data: ['staffs' => Staff::All(connection: $this->DB)],
            title: 'Staffisti ' . date(format: "Y")
        );
    }

    public function anagrafiche(?int $year = null): int {
        $this->RequireLogin();
        if (!isset($year) || $year === 0) {    
            return $this->Render(
                view: 'Staff/anagrafiche',
                title: 'Tutte le anagrafiche',
                data: ['anagrafiche' => AnagraficaConIscrizione::All(connection: $this->DB)],
            );
        }
        return $this->Render(
            view: 'Staff/anagrafiche',
            title: 'Iscritti per il ' . $year,
            data: ['anagrafiche' => AnagraficaConIscrizione::FromYear(connection: $this->DB, year: $year)],
        );
    }

    public function csi(): int {
        $this->RequireLogin();
        return $this->Render(
            view: 'Staff/csi',
            title: 'Tesseramenti CSI',
            data: ['iscrizioni' => TesseramentoCSI::All(connection: $this->DB)]
        );
    }

    public function me(
        ?int $anagrafica = null,
        ?int $parrocchia = null,
    ): int {
        $user = $this->RequireLogin();
        
        if ($this->IsPost() && !empty($parrocchia)) {
            if (isset($user->IdStaff)) {
                // Update existing record

                $res = Staff::ChangeParrocchia(
                    connection: $this->DB, 
                    staff: $user->IdStaff, 
                    parrocchia: $parrocchia
                );
                if ($res) {
                    $staff = $this->RequireStaff();
                    $staff->Parrocchia->Id = $parrocchia;
                }
            } else {
                // Create new staff

                $staff_id = Staff::Create(
                    connection: $this->DB, 
                    id_anagrafica: $anagrafica, 
                    user: $user->Id, 
                    parrocchia: $parrocchia
                );
                $res = isset($staff_id);
                if ($res) {
                    // Load the new data in the existing session
                    $user->IdStaff = $staff_id;
                    $user->IdAnagrafica = $anagrafica;
                    $user->RealName = Anagrafica::NomeDaId(connection: $this->DB, id: $anagrafica);
                    $user->PutAdditionalInSession();
                    $this->RequireStaff();
                }
            }

            if ($res) {
                $this->Message(
                    message: new Message(
                        type: MessageType::Success, 
                        content: 'Dati inseriti/modificati correttamente'));
            } else {
                $this->Message(
                    message: new Message(
                        type: MessageType::Error, 
                        content: 'Non è stato possibile inserire o modificare i dati'));
            }
        }

        return $this->Render(
            view: 'Staff/me',
            title: 'Account STAFF',
            data: [
                'anagrafiche' => AnagraficaBase::All(connection: $this->DB),
                'parrocchie' => Parrocchia::All(connection: $this->DB),
            ]
        );
    }

    public function new_anagrafica(
        // Required parameters
        string $nome = '',
        string $cognome = '',
        string $cf = '',
        int $doc_type = 1,
        string $doc_code = '',
        string $doc_expires = '',
        string $email = '',
        string $compleanno = '',
        string $provenienza= '',

        // Optional arguements
        ?string $telefono = null,

        // Is editing?
        ?int $id = null,
    ): int {
        $this->RequireLogin();

        $types = TipoDocumento::All(connection: $this->DB);

        if (self::IsPost()) {
            if (
                empty($nome) ||
                empty($cognome) ||
                empty($cf) ||
                empty($doc_code) ||
                empty($doc_expires) ||
                empty($email) ||
                empty($provenienza) ||
                empty($compleanno)
            ) {
                return $this->BadRequest();
            }

            if (!empty($id)) {
                //
                // Edit an existing record
                //

                $this->Message(message: Message::Success(content: 'Dati correttamente modificati'));
                return $this->edit_anagrafica(id: $id);
            }

            //
            // Create new record
            //

            // TODO: Handle file submission
            $files = File::UploadingFiles(form_name: 'doc');
            $files = array_filter(array: $files, callback: function(array $file): bool {
                return File::IsUploadOk(file: $file);
            });
            if (count(value: $files) === 0) {
                return $this->BadRequest();
            }
            $target_file_name = 
                "documenti" . DIRECTORY_SEPARATOR . 
                "documento_" . str_replace(search: '.', replace: '', subject: uniqid(more_entropy: true));
            $actual_path = File::UploadDocumentsMerge(files: $files, final_name: $target_file_name);
            if (empty($actual_path)) {
                // Error
            }


            $id = Anagrafica::Create(
                connection: $this->DB,
                nome: $nome,
                cognome: $cognome,
                provenienza: $provenienza,
                compleanno: $compleanno,
                tel: $telefono,
                email: $email,
                cf: $cf,

                doc_type: $doc_type,
                doc_code: $doc_code,
                doc_expires: $doc_expires,
                nome_file: File::AbsoluteToDbPath($actual_path)
            );

            if (isset($id))
            {
                // Everything went ok
                return $this->Render(
                    view: 'Staff/created-anagrafica',
                    title: $nome . ' correttamente aggiunto',
                    data: [
                        'id' => $id,
                        'nome' => $nome,
                    ]
                );
            }

            $this->Message(message: Message::Error(content: 'Non è stato possibile registrare i dati!'));
        }


        return $this->Render(
            view: 'Staff/new-anagrafica',
            title: 'Registra persona',
            data: [
                'tipi_documento' => $types,
                'anagrafica' => null,
            ]
        );
    }

    public function edit_anagrafica(
        ?int $id = null
    ): int {
        $this->RequireLogin();
        if (!isset($id))
            return $this->BadRequest();
        
        $a = Anagrafica::ById(connection: $this->DB, id: $id);
        if (!isset($a))
            return $this->NotFound();

        $types = TipoDocumento::All(connection: $this->DB);

        return $this->Render(
            view: 'Staff/new-anagrafica',
            title: 'Modifica '. $a->Nome,
            data: [
                'tipi_documento' => $types,
                'anagrafica' => $a,
            ]
        );
    }
}