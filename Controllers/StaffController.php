<?php

namespace Amichiamoci\Controllers;

use Amichiamoci\Models\Anagrafica;
use Amichiamoci\Models\AnagraficaConIscrizione;
use Amichiamoci\Models\Commissione;
use Amichiamoci\Models\Edizione;
use Amichiamoci\Models\Iscrizione;
use Amichiamoci\Models\Templates\Anagrafica as AnagraficaBase;
use Amichiamoci\Models\Message;
use Amichiamoci\Models\MessageType;
use Amichiamoci\Models\Parrocchia;
use Amichiamoci\Models\ProblemaIscrizione;
use Amichiamoci\Models\PunteggioParrocchia;
use Amichiamoci\Models\Staff;
use Amichiamoci\Models\StaffBase;
use Amichiamoci\Models\TesseramentoCSI;
use Amichiamoci\Models\TipoDocumento;
use Amichiamoci\Models\Taglia;
use Amichiamoci\Utils\File;

class StaffController extends Controller
{
    public function index(
        ?int $church = null,
        ?int $year = null,
    ): int {
        if (empty($year))
        {
            $year = (int)date(format: "Y");
        }
        
        $staff = $this->RequireStaff();
        if (!isset($church) && isset($staff))
        {
            $church = $staff->Parrocchia->Id;
        }
        if (empty($church))
        {
            return $this->BadRequest();
        }

        $church_object = Parrocchia::ById(connection: $this->DB, id: $church);
        if ($church_object === null)
        {
            return $this->NotFound();
        }

        return $this->Render(
            view: 'Staff/index',
            title: 'Portale Staff',
            data: [
                'iscritti_problemi' => ProblemaIscrizione::Parrocchia(
                    connection: $this->DB, 
                    year: $year, 
                    parrocchia: $church
                ),
                'iscritti' => AnagraficaConIscrizione::FromChurchId(
                    connection: $this->DB, 
                    church_id: $church,
                ),
                'id_parrocchia' => $church,
                'nome_parrocchia' => $church_object->Nome,
                'anno' => $year,
                'parrocchie' => Parrocchia::All(connection: $this->DB),
                'edizioni' => Edizione::All(connection: $this->DB),
            ]
        );
    }

    public function problems(?int $church = null, ?int $year = null): int
    {
        if (empty($year)) {
            $year = (int)date(format: "Y");
        }
        
        $staff = $this->RequireStaff();
        if (!isset($church) && isset($staff)) {
            $church = $staff->Parrocchia->Id;
        }
        if (empty($church)) {
            return $this->BadRequest();
        }

        return $this->Json(object: 
            array_values(array: array_map(
                callback: function (ProblemaIscrizione $p): array {
                    return [
                        'id' => $p->Id,
                        'name' => $p->Nome,
                        'count' => $p->ProblemCount(),
                    ];
                }, 
                array: ProblemaIscrizione::Parrocchia(
                    connection: $this->DB, 
                    year: $year, 
                    parrocchia: $church,
                )
            ))
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

    public function view(?int $id): int
    {
        $this->RequireLogin();
        if (empty($id)) {
            return $this->BadRequest();
        }

        $target = Staff::ById(connection: $this->DB, id: $id);
        if (!isset($target)) {
            return $this->NotFound();
        }

        return $this->Render(
            view: 'Staff/view',
            title: $target->Nome,
            data: ['target' => $target],
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

    public function get_involved(
        ?int $edition = null,
        array $roles = [],
        bool $church_manager = false,
        ?string $t_shirt = null,
    ): int {
        $user = $this->RequireLogin();
        $staff = $this->RequireStaff();

        $current_edition = Edizione::Current(connection: $this->DB);
        if (!isset($current_edition) && !$user->IsAdmin) {
            return $this->BadRequest();
        }

        if (self::IsPost()) {
            if (!isset($staff) || empty($edition) || empty($t_shirt)) {
                return $this->BadRequest();
            }
            $res = Staff::Partecipa(
                connection: $this->DB, 
                staff: $staff->Id, 
                edizione: $edition, 
                maglia: $t_shirt, 
                commissioni: $roles, 
                is_referente: $church_manager
            );
            if ($res) {
                $this->Message(message: Message::Success(content: 'Partecipazione confermata'));
            } else {
                $this->Message(message: Message::Error(content: 'Qualcosa non ha funzionato :/'));
            }
        }
        return $this->Render(
            view: 'Staff/get-involved',
            title: 'Partecipa all\'edizione corrente',
            data: [
                'edizioni' => Edizione::All(connection: $this->DB),
                'edizione_corrente' => $current_edition,
                'taglie' => Taglia::All(),
                'commissioni' => Commissione::All(connection: $this->DB),
            ]
        );
    }

    private function anagrafica_handle_file(): ?string
    {
        $files = File::UploadingFiles(form_name: 'doc');
        $files = array_filter(array: $files, callback: function(array $file): bool {
            return File::IsUploadOk(file: $file);
        });
        if (count(value: $files) === 0)
        {
            return null;
        }

        $target_file_name = 
            "documenti" . DIRECTORY_SEPARATOR . 
            "documento_" . str_replace(search: '.', replace: '', subject: uniqid(more_entropy: true));

        $path = File::UploadDocumentsMerge(files: $files, final_name: $target_file_name);
        if (empty($path))
        {
            return null;
        }

        return File::AbsoluteToDbPath(server_path: $path);
    }

    public function new_anagrafica(
        // Required parameters
        string $nome = '',
        string $cognome = '',
        string $cf = '',
        int $doc_type = 1,
        ?string $doc_code = null,
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

        if (self::IsPost())
        {
            if (
                empty($nome) ||
                empty($cognome) ||
                empty($cf) ||
                // empty($doc_code) ||
                empty($doc_expires) ||
                empty($email) ||
                empty($provenienza) ||
                empty($compleanno)
            ) {
                return $this->BadRequest();
            }

            $document_path = $this->anagrafica_handle_file();
            $already_existed = false;


            if (empty($id) && empty($document_path))
            {
                // If we are creating a new record the document is mandatory
                return $this->BadRequest();
            }

            $record_id = Anagrafica::CreateOrUpdate(
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
                nome_file: $document_path,

                already_existing: $already_existed,
            );

            if (!empty($id))
            {
                // If we have edited a record (an id was provided with the request)

                if (empty($record_id))
                {
                    // Something has gone wrong: the procedure failed.
                    $this->Message(message: Message::Error(content: 'È avvenuto un errore'));
                    return $this->edit_anagrafica(id: $id); 
                }

                if (!$already_existed || $record_id !== $id)
                {
                    // The procedure succeded but the cf was not pointing to this anagrafica
                    $this->Message(message: Message::Warn(content: 'Anagrafica modificata, tuttavia i dati non sembrano essere allineati'));
                    return $this->edit_anagrafica(id: $id); 
                }

                // All ok
                $this->Message(message: Message::Success(content: 'Dati correttamente modificati'));
                return $this->edit_anagrafica(id: $id);
            }

            // We have creted a new record. Hopefully
            if (!empty($record_id))
            {
                // Everything went ok
                return $this->Render(
                    view: 'Staff/created-anagrafica',
                    title: $nome . ' correttamente ' . ($already_existed ? 'modificato' : 'aggiunto'),
                    data: [
                        'id' => $record_id,
                        'nome' => $nome,
                        'already_existed' => $already_existed,
                    ]
                );
            }

            // Something has gone wrong
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

    public function iscrivi(
        ?int $id = null, // Anagraphical id
        ?int $id_iscrizione = null, // Are we editing the subscription?

        ?int $tutore = null,
        ?int $parrocchia = null,
        ?string $taglia = null,
        ?int $edizione = null,
    ): int {
        $this->RequireLogin();
        if (empty($id))
        {
            return $this->BadRequest();
        }

        $a = AnagraficaBase::ById(connection: $this->DB, id: $id);
        if ($a === null)
        {
            return $this->NotFound();
        }

        if (self::IsPost())
        {
            if (empty($parrocchia) || empty($taglia))
            {
                return $this->BadRequest();
            }
            if (empty($edizione))
            {
                $edizione = Edizione::Current(connection: $this->DB)->Id;
            }

            //
            // Handle the submitted files
            //
            $files = File::UploadingFiles(form_name: 'certificato');
            $files = array_filter(array: $files, callback: function (array $file): bool {
                return File::IsUploadOk(file: $file);
            });
            $actual_path = null;
            if (count(value: $files) > 0)
            {
                $year = Edizione::ById(connection: $this->DB, id: $edizione);
                if (!isset($year))
                {
                    return $this->NotFound();
                }

                $target_file_name = 
                    "certificati" . DIRECTORY_SEPARATOR . 
                    $year->Year . '_' . 
                    str_replace(search: '.', replace: '', subject: uniqid(more_entropy: true));
                $actual_path = File::UploadDocumentsMerge(files: $files, final_name: $target_file_name);
            }

            //
            // Handle the submitted data
            //
            if (!empty($id_iscrizione))
            {
                // We are editing a subscription
                $iscrizione = Iscrizione::ById(connection: $this->DB, id: $id_iscrizione);
                if ($iscrizione === null)
                {
                    return $this->NotFound();
                }

                $iscrizione->Parrocchia->Id = $parrocchia;
                $iscrizione->IdTutore = empty($tutore) ? null : $tutore;
                $iscrizione->Taglia = Taglia::from(value: $taglia);
                $res = $iscrizione->Update(connection: $this->DB);

                if ($res && !empty($actual_path))
                {
                    if (!Iscrizione::UpdateCertificato(
                        connection: $this->DB, 
                        id: $iscrizione->Id, 
                        certificato: File::AbsoluteToDbPath(server_path: $actual_path))
                    ) {
                        $actual_path = null; // In order to show warning later
                    }
                }
            } else {
                // We are creating a subscription
                $res = Iscrizione::Create(
                    connection: $this->DB, 
                    id_anagrafica: $id, 
                    tutore: $tutore, 
                    certificato: empty($actual_path) ? 
                        null : 
                        File::AbsoluteToDbPath(server_path: $actual_path), 
                    parrocchia: $parrocchia, 
                    taglia: Taglia::from(value: $taglia), 
                    edizione: $edizione,
                );
            }

            // How did the handling of the data go?
            if ($res) {
                if (isset($actual_path)) {
                    $this->Message(message: Message::Success(
                        content: $a->Nome . ' correttamente iscritto'));
                } else {
                    $this->Message(message: Message::Warn(
                        content: $a->Nome . ' iscritto con RISERVA: aggiungere il certificato medico per completare l\'scrizione'));
                }
                return $this->index();
            }
            $this->Message(message: Message::Error(content: 'Errore durante l\'iscrizione'));
        }

        return $this->Render(
            view: 'Staff/iscrivi',
            title: 'Iscrivi '. $a->Nome . ' ' . $a->Cognome,
            data: [
                'target' => $a,
                'taglie' => Taglia::All(),
                'parrocchie' => Parrocchia::All(connection: $this->DB),
                'adulti' => AnagraficaBase::All(connection: $this->DB, filter: function (AnagraficaBase $a): bool {
                    return $a->Eta >= 18;
                }),
                'edizioni' => Edizione::All(connection: $this->DB),

                'id_iscrizione' => $id_iscrizione,
                'tutore' => $tutore,
                'parrocchia' => $parrocchia,
                'taglia' => $taglia,
            ]
        );
    }

    public function delete_iscrizione(?int $id = null): int {
        $user = $this->RequireLogin();
        $staff = $this->RequireStaff();
        if (self::IsPost())
        {
            $target = Iscrizione::ById(connection: $this->DB, id: $id);
            if (!isset($target))
            {
                return $this->NotFound();
            }
            if (!$user->IsAdmin && $target->Parrocchia->Id !== $staff->Parrocchia->Id)
            {
                return $this->NotAuthorized();
            }
            if (Iscrizione::Delete(connection: $this->DB, id: $id)) {
                $this->Message(message: Message::Success(content: 'Iscrizione cancellata'));
            } else {
                $this->Message(message: Message::Error(content: 'Non è stato possibile cancellare l\'iscrizione'));
            }
        }
        return $this->anagrafiche();
    }

    public function modifica_iscrizione(?int $id = null): int
    {
        $this->RequireLogin();
        if (empty($id))
        {
            return $this->BadRequest();
        }

        $iscrizione = Iscrizione::ById(connection: $this->DB, id: $id);
        if ($iscrizione === null)
        {
            return $this->NotFound();
        }
        $a = AnagraficaBase::ById(connection: $this->DB, id: Iscrizione::IdAnagraficaAssociata(connection: $this->DB, id: $id));
        // assert a !== null;

        return $this->Render(
            view: 'Staff/iscrivi',
            title: 'Iscrivi '. $a->Nome . ' ' . $a->Cognome,
            data: [
                'target' => $a,
                'taglie' => Taglia::All(),
                'parrocchie' => Parrocchia::All(connection: $this->DB),
                'adulti' => AnagraficaBase::All(connection: $this->DB, filter: function (AnagraficaBase $a): bool {
                    return $a->Eta >= 18;
                }),
                'edizioni' => Edizione::All(connection: $this->DB),

                'id_iscrizione' => $id,
                'tutore' => $iscrizione->IdTutore,
                'parrocchia' => $iscrizione->Parrocchia->Id,
                'taglia' => $iscrizione->Taglia->value,
            ]
        );
    }

    public function edizione(
        ?int $anno = null,
        ?string $motto = null,
    ): int {
        $user = $this->RequireLogin();
        $all = Edizione::All(connection: $this->DB);

        if (self::IsPost() && $user->IsAdmin) {
            if (empty($anno) || empty($motto)) {
                return $this->BadRequest();
            }
            $anno = (int)$anno;

            $existing = array_filter(array: $all, callback: function (Edizione $e) use($anno): bool {
                return $e->Year === $anno;
            });
            if (count(value: $existing) > 0) {
                $this->Message(message: Message::Warn(content: "Edizione $anno già esistente: i dati sono stati sovrascritti"));
                $existing[0]->Motto = $motto;
                $existing[0]->Update($this->DB);
            } else {
                $e = Edizione::New(connection: $this->DB, year: $anno, motto: $motto);
                if (!isset($e)) {
                    $this->Message(message: Message::Error(content: 'Impossibile creare edizione ' . $anno));
                } else { 
                    $this->Message(message: Message::Success(content: 'Edizione creata correttamente'));
                    $all[] = $e;
                }
            }
        }

        return $this->Render(
            view: 'Staff/edizioni',
            title: 'Edizioni di ' . SITE_NAME,
            data: [
                'edizioni' => $all,
            ]
        );
    }

    public function t_shirts(?int $year = null): int
    {
        $this->RequireLogin();

        if (empty($year)) {
            $year = (int)date(format: 'Y');
        }

        return $this->Render(
            view: 'Staff/t-shirts',
            title: 'Tutte le Maglie',
            data: [
                'anno' => $year,
                'edizioni' => Edizione::All(connection: $this->DB),

                'riepilogo' => Taglia::Grouped(connection: $this->DB, year: $year),
                'lista_completa' => Taglia::List(connection: $this->DB, year: $year),
            ],
        );
    }

    public function church_leaderboard(?int $year = null): int
    {
        $this->RequireLogin();
        if (!isset($year)) {
            $year = (int)date(format: 'Y');
        }

        return $this->Render(
            view: 'Staff/leaderboard',
            title: 'Classifica parrocchie',
            data: [
                'anno' => $year,
                'edizioni' => Edizione::All(connection: $this->DB),
                'classifica' => PunteggioParrocchia::All(connection: $this->DB, year: $year),
            ]
        );
    }

    public function church_leaderboard_edit(
        ?int $edition,
        ?int $church,
        ?string $score,
    ): int {
        $this->RequireLogin(require_admin: true);

        if (!PunteggioParrocchia::Insert(
            connection: $this->DB, 
            edizione: $edition, 
            parrocchia: $church, 
            punteggio: $score)
        ) {
            return $this->Json(object: [
                'message' => 'Non è stato possibile aggiornare il punteggio',
            ], status_code: 500);
        }

        return $this->Json(object: [
            'message' => 'Punteggio inserito/modificato correttamente',
        ]);
    }
}