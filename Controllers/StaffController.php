<?php

namespace Amichiamoci\Controllers;

use Richie314\SimpleMvc\Controllers\Attributes\RequireLogin;
use Richie314\SimpleMvc\Http\StatusCode;
use Richie314\SimpleVirtualFS\{UploadedFiles, Media, VirtualFilesystem};

use Amichiamoci\Models\{
    Anagrafica,
    AnagraficaConIscrizione,
    Commissione,
    Edizione,
    Iscrizione,
    Message,
    Parrocchia,
    ProblemaIscrizione,
    PunteggioParrocchia,
    Settings,
    Staff,
    StaffBase,
    TesseramentoCSI,
    TipoDocumento,
    Taglia,
    User,
};
use Amichiamoci\Models\Templates\Anagrafica as AnagraficaBase;
use Amichiamoci\Controllers\Attributes\RequireStaff;
use Amichiamoci\Utils\{Authorization, Security};


class StaffController
extends Controller
{
    #[RequireStaff]
    public function index(
        ?int $church = null,
        ?int $year = null,
    ): StatusCode
    {
        if (empty($year))
            $year = (int)date(format: "Y");
        
        if (!isset($church) && $this->Staff() !== null)
            $church = $this->Staff()->Parrocchia->Id;

        if (empty($church))
            return $this->BadRequest();

        $church_object = Parrocchia::ById(connection: $this->DB, id: $church);
        if ($church_object === null)
            return $this->NotFound();

        $admin = $this->getUser()->Admin;
        $subscriptionsEnabled = $admin || Settings::Read($this->DB)['subscriptions_enabled'];

        return $this->Render(
            view: 'Staff/index',
            title: 'Portale Staff',
            data: [
                'subscriptions_enabled' => $subscriptionsEnabled,
                'certificate_upload_enabled' => !$subscriptionsEnabled,
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

    #[RequireStaff]
    public function problems(?int $church = null, ?int $year = null): StatusCode
    {
        if (empty($year))
            $year = (int)date(format: "Y");
        
        if (!isset($church) && $this->Staff() !== null)
            $church = $this->Staff()->Parrocchia->Id;

        if (empty($church))
            return $this->BadRequest();

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

    #[RequireLogin(requireAdmin: true)]
    public function double_subscriptions(): StatusCode
    {
        return $this->Json(object: 
            array_values(array: array_map(
                callback: function (AnagraficaConIscrizione $a): array {
                    return [
                        'id' => $a->Id,
                        'name' => $a->Nome . ' ' . $a->Cognome,
                        'church' => $a->Iscrizione->Parrocchia->Nome,
                    ];
                }, 
                array: AnagraficaConIscrizione::DoubleSubscriptions(connection: $this->DB)
            ))
        );
    }

    #[RequireStaff]
    public function all(): StatusCode
    {
        return $this->Render(
            view: 'Staff/all',
            data: [
                'staffs' => StaffBase::All(connection: $this->DB),
                'current_staff' => array_column(Staff::All(connection: $this->DB), null, 'Id'),
                'current_only' => false,
            ],
            title: 'Staffisti di sempre'
        );
    }

    #[RequireStaff]
    public function current(): StatusCode
    {
        return $this->Render(
            view: 'Staff/all',
            data: [
                'staffs' => Staff::All(connection: $this->DB),
                'current_only' => true,
            ],
            title: 'Staffisti ' . date(format: "Y")
        );
    }

    #[RequireLogin]
    public function view(?int $id): StatusCode
    {
        if (empty($id))
            return $this->BadRequest();

        $target = Staff::ById($this->DB, $id);
        $staff = $this->Staff();
        if ($target === null && ($this->User->Admin || $staff !== null && $staff->Id === $id)) {
            $target = StaffBase::ById($this->DB, $id);
        }
        if ($target === null) 
            return $this->NotFound();

        return $this->Render(
            view: 'Staff/view',
            title: $target->Nome,
            data: ['target' => $target],
        );
    }

    #[RequireLogin]
    public function anagrafiche(?int $year = null): StatusCode
    {
        $anagrafiche = empty($year)
            ? AnagraficaConIscrizione::All(connection: $this->DB)
            : AnagraficaConIscrizione::FromYear(connection: $this->DB, year: $year);

        return $this->Render(
            view: 'Staff/anagrafiche',
            title: empty($year) ? 'Tutte le anagrafiche' : 'Iscritti per il ' . $year,
            data: [
                'anagrafiche' => $anagrafiche,
            ],
        );
    }

    #[RequireLogin]
    public function csi(): StatusCode
    {
        return $this->Render(
            view: 'Staff/csi',
            title: 'Tesseramenti CSI',
            data: ['iscrizioni' => TesseramentoCSI::All(connection: $this->DB)]
        );
    }

    #[RequireLogin]
    public function me(?int $parrocchia = null, ?string $csrf_token = null): StatusCode
    {
        $staff = $this->Staff();
        if ($this->IsPost() && !$this->ValidAccountToken($csrf_token)) {
            return $this->BadRequest();
        }

        if ($this->IsPost() && $staff !== null && !empty($parrocchia)) {
            $res = Staff::ChangeParrocchia($this->DB, $staff->Id, $parrocchia);
            if ($res) {
                $this->ViewBag['staff'] = Staff::ById($this->DB, $staff->Id);
            }
            $this->Message($res
                ? Message::Success('Dati inseriti/modificati correttamente')
                : Message::Error('Non è stato possibile modificare i dati'));
        }

        return $this->Render(
            view: 'Staff/me',
            title: 'Account STAFF',
            data: [
                'csrf_token' => $this->AccountToken(),
                'parrocchie' => Parrocchia::All($this->DB),
            ],
        );
    }

    #[RequireLogin(requireAdmin: true)]
    public function link_account(
        ?int $account = null,
        ?int $anagrafica = null,
        ?int $parrocchia = null,
        ?string $csrf_token = null,
    ): StatusCode
    {
        $target = $account === null ? null : User::ById($this->DB, $account);
        if ($target === null) {
            return $this->NotFound();
        }
        $target->LoadAdditionalData($this->DB);
        if ($target->HasAdditionalData()) {
            return $this->Redirect($this->PathPrefix . '/user/view?id=' . $target->Id);
        }

        if ($this->IsPost()) {
            if (!$this->ValidAccountToken($csrf_token) || empty($anagrafica) || empty($parrocchia)) {
                return $this->BadRequest();
            }
            if (Staff::Create($this->DB, $anagrafica, $target->Id, $parrocchia) === null) {
                return $this->BadRequest('Non è stato possibile associare i dati.');
            }
            return $this->Redirect($this->PathPrefix . '/user/view?id=' . $target->Id);
        }

        return $this->Render(
            view: 'Staff/me',
            title: 'Associa account staff',
            data: [
                'target' => $target,
                'csrf_token' => $this->AccountToken(),
                'anagrafiche' => AnagraficaBase::All($this->DB),
                'parrocchie' => Parrocchia::All($this->DB),
            ],
        );
    }

    #[RequireStaff]
    public function get_involved(
        ?int $edition = null,
        array $roles = [],
        ?string $t_shirt = null,
    ): StatusCode
    {
        $current_edition = Edizione::Current(connection: $this->DB);
        if (!isset($current_edition) && !$this->User->Admin)
            return $this->BadRequest();

        if ($this->IsPost())
        {
            if ($this->Staff() === null || empty($edition) || empty($t_shirt))
                return $this->BadRequest();

            $res = Staff::Partecipa(
                connection: $this->DB, 
                staff: $this->Staff()->Id, 
                edizione: $edition, 
                maglia: $t_shirt, 
                commissioni: $roles, 
            );
            if ($res)
                return $this->Redirect(url: $this->PathPrefix . '/');
            
            $this->Message(message: Message::Error(content: 'Qualcosa non ha funzionato :/'));
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

    private function MergeUploads(array $uploads): ?string
    {
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
        ];
        $limit = (int)Security::LoadEnvironmentOrFromFile(var: 'FILE_MAX_SIZE', default: '10485760');
        $mime = new \finfo(FILEINFO_MIME_TYPE);
        $directory = sys_get_temp_dir() . '/staff-upload-' . bin2hex(random_bytes(16));
        if (!mkdir($directory, 0700)) {
            return null;
        }

        $staging = new VirtualFilesystem();
        $temporary = [];
        try {
            $staging->mountLocal('upload', $directory);
            $media = new Media($staging);
            $pdfs = [];
            foreach ($uploads as $index => $upload) {
                $extension = strtolower(pathinfo($upload['name'] ?? '', PATHINFO_EXTENSION));
                if ($upload['error'] !== UPLOAD_ERR_OK || ($upload['size'] ?? PHP_INT_MAX) > $limit
                    || !isset($mimeTypes[$extension]) || !is_uploaded_file($upload['tmp_name'])
                    || $mime->file($upload['tmp_name']) !== $mimeTypes[$extension]
                ) {
                    return null;
                }

                $source = "upload:///$index.$extension";
                $temporary[] = $source;
                $staging->saveUpload($upload, $source);
                if ($extension !== 'pdf') {
                    $pdf = "upload:///$index.pdf";
                    $temporary[] = $pdf;
                    $media->imagesToPdf([$source], $pdf);
                    $source = $pdf;
                }
                $pdfs[] = $source;
            }

            if (count($pdfs) === 1) {
                return $staging->read($pdfs[0]);
            }

            $merged = 'upload:///merged.pdf';
            $temporary[] = $merged;
            $media->mergePdfs($pdfs, $merged);
            return $staging->read($merged);
        } catch (\Throwable) {
            return null;
        } finally {
            foreach ($temporary as $uri) {
                $staging->delete($uri);
            }
            rmdir($directory);
        }
    }

    private function StoreMedia(string $path, #[\SensitiveParameter] string $content): string
    {
        $files = $this->Files();
        $drive = in_array('drive', array_column($files->listMounts(), 'scheme'), true);
        $uri = ($drive ? 'drive:///' : 'staff:///') . $path;
        $files->write($uri, $content);
        return $uri;
    }

    #[RequireLogin]
    public function new_anagrafica(
        // Required parameters
        string $nome = '',
        string $cognome = '',
        string $cf = '',
        int $doc_type = 1,
        string $doc_expires = '',
        string $email = '',
        string $compleanno = '',
        string $provenienza= '',

        // Optional arguements
        ?string $telefono = null,

        // Is editing?
        ?int $id = null,
    ): StatusCode
    {

        $types = TipoDocumento::All(connection: $this->DB);

        if ($this->IsPost())
        {
            if (empty($nome) ||
                empty($cognome) ||
                empty($cf) ||
                empty($doc_expires) ||
                empty($email) ||
                empty($provenienza) ||
                empty($compleanno)
            )
                return $this->BadRequest();

            if (!empty($id) && Anagrafica::FromFiscalCode($this->DB, $cf)?->Id !== $id) {
                return $this->BadRequest('Il codice fiscale non corrisponde alla persona selezionata.');
            }

            $files = array_values(array_filter(UploadedFiles::fromField($_FILES['doc'] ?? null),
                static fn(array $file): bool => (int)$file['error'] !== UPLOAD_ERR_NO_FILE));
            $document = $files === [] ? null : $this->MergeUploads($files);
            if ($files !== [] && $document === null) {
                return $this->BadRequest('Carica soltanto immagini JPEG, PNG, WebP o PDF validi.');
            }
            if (empty($id) && $document === null) {
                return $this->BadRequest('Carica un documento di identità.');
            }

            $already_existed = false;
            $this->DB->begin_transaction();
            try {
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
                    doc_expires: $doc_expires,

                    already_existing: $already_existed,
                    allowStaffUpdate: $this->getUser()->Admin,
                );
                if ($record_id > 0 && $document !== null) {
                    $path = $this->StoreMedia(
                        'documenti/documento_' . bin2hex(random_bytes(16)) . '.pdf',
                        $document,
                    );
                    Anagrafica::ReplaceDocumentPath($this->DB, $record_id, $path);
                }
                $this->DB->commit();
            } catch (\Throwable $exception) {
                $this->DB->rollback();
                return $this->InternalError(ex: $exception);
            }

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
                    $this->Message(message: Message::Warn(
                        content: 'Anagrafica modificata, tuttavia i dati non sembrano essere allineati'
                    ));
                    return $this->edit_anagrafica(id: $id); 
                }

                // All ok
                $this->Message(message: Message::Success(content: 'Dati correttamente modificati'));
                return $this->edit_anagrafica(id: $id);
            }

            // We have created a new record.
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

    #[RequireLogin]
    public function edit_anagrafica(
        ?int $id = null
    ): StatusCode
    {
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

    #[RequireLogin]
    public function iscrivi(
        ?int $id = null, // Anagraphical id
        ?int $id_iscrizione = null, // Are we editing the subscription?

        ?int $tutore = null,
        ?int $parrocchia = null,
        ?string $taglia = null,
        ?int $edizione = null,
        mixed $csrf_token = null,
    ): StatusCode
    {
        if (empty($id))
            return $this->BadRequest();

        $admin = $this->getUser()->Admin;
        $certificateOnly = !$admin && !Settings::Read($this->DB)['subscriptions_enabled'];
        if ($certificateOnly && (!$this->IsPost() || empty($id_iscrizione))) {
            return $this->NotAuthorized('Le nuove iscrizioni sono riservate agli amministratori.');
        }

        $a = AnagraficaBase::ById(connection: $this->DB, id: $id);
        if ($a === null)
            return $this->NotFound();

        if ($this->IsPost())
        {
            $shirt = Taglia::tryFrom($taglia ?? '');
            if (!$certificateOnly && (empty($parrocchia) || $shirt === null))
                return $this->BadRequest();

            $iscrizione = null;
            if (!empty($id_iscrizione)) {
                if (!$admin && !$certificateOnly)
                    return $this->NotAuthorized();

                $iscrizione = Iscrizione::ById($this->DB, $id_iscrizione);
                if ($iscrizione === null || Iscrizione::IdAnagraficaAssociata($this->DB, $id_iscrizione) !== $id)
                    return $this->NotFound();

                if ($certificateOnly && !Authorization::CanAccessCertificate(
                    $this->getUser(), $this->Staff(), $iscrizione->Parrocchia
                )) {
                    return $this->NotAuthorized();
                }
                if ($certificateOnly && (!is_string($csrf_token) || !$this->ValidAccountToken($csrf_token))) {
                    return $this->BadRequest();
                }
            }

            if ($iscrizione !== null) {
                $edizione = Iscrizione::EditionId($this->DB, $iscrizione->Id);
            }
            $edizione ??= Edizione::Current(connection: $this->DB)?->Id;
            if (!$edizione)
                return $this->NotFound();

            //
            // Handle the submitted files
            //
            $files = array_values(array_filter(UploadedFiles::fromField($_FILES['certificato'] ?? null),
                static fn(array $file): bool => (int)$file['error'] !== UPLOAD_ERR_NO_FILE));
            if ($certificateOnly && $files === []) {
                return $this->BadRequest('Carica un certificato medico.');
            }
            $uploaded_file_path = null;
            if ($files !== []) {
                $edition = Edizione::ById($this->DB, $edizione);
                if ($edition === null) {
                    return $this->NotFound();
                }

                $document = $this->MergeUploads($files);
                if ($document === null) {
                    return $this->BadRequest('Carica soltanto immagini JPEG, PNG, WebP o PDF validi.');
                }
                try {
                    $uploaded_file_path = $this->StoreMedia(
                        'certificati/' . $edition->Year . '_' . bin2hex(random_bytes(16)) . '.pdf',
                        $document,
                    );
                } catch (\Throwable $exception) {
                    return $this->InternalError(ex: $exception);
                }
            }

            //
            // Handle the submitted data
            //
            if ($certificateOnly) {
                try {
                    if (!Iscrizione::UpdateCertificato($this->DB, $iscrizione->Id, $uploaded_file_path)) {
                        return $this->InternalError();
                    }
                } catch (\Throwable $exception) {
                    return $this->InternalError(ex: $exception);
                }

                return $this->RedirectWithStatus(
                    $this->PathPrefix . '/staff?church=' . $iscrizione->Parrocchia->Id,
                    StatusCode::SeeOther,
                );
            }

            if ($iscrizione !== null)
            {
                $iscrizione->Parrocchia->Id = $parrocchia;
                $iscrizione->IdTutore = empty($tutore) ? null : $tutore;
                $iscrizione->Taglia = $shirt;
                $res = $iscrizione->Update(connection: $this->DB);

                if ($res && !empty($uploaded_file_path))
                {
                    if (!Iscrizione::UpdateCertificato(
                        connection: $this->DB, 
                        id: $iscrizione->Id, 
                        certificato: $uploaded_file_path)
                    ) {
                        $uploaded_file_path = null; // In order to show warning later
                    }
                }
            } else {
                // We are creating a subscription
                $res = Iscrizione::Create(
                    connection: $this->DB, 
                    id_anagrafica: $id, 
                    tutore: empty($tutore) ? null : $tutore, 
                    certificato: $uploaded_file_path, 
                    parrocchia: $parrocchia, 
                    taglia: $shirt, 
                    edizione: $edizione,
                );
            }

            // How did the handling of the data go?
            if ($res)
            {
                if (!empty($uploaded_file_path)) {
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

                'tutore_suggerito' => AnagraficaConIscrizione::SuggestTutore(connection: $this->DB, anagrafica: $a->Id),
            ]
        );
    }

    #[RequireLogin(requireAdmin: true)]
    public function delete_iscrizione(?int $id = null): StatusCode
    {
        if ($this->IsPost())
        {
            if (!$id)
                return $this->BadRequest();

            $target = Iscrizione::ById(connection: $this->DB, id: $id);
            if ($target === null)
                return $this->NotFound();
            
            if (Iscrizione::Delete(connection: $this->DB, id: $id)) {
                $this->Message(message: Message::Success(content: 'Iscrizione cancellata'));
            } else {
                $this->Message(message: Message::Error(content: 'Non è stato possibile cancellare l\'iscrizione'));
            }
        }

        return $this->anagrafiche();
    }

    #[RequireLogin]
    public function modifica_iscrizione(?int $id = null): StatusCode
    {
        if (empty($id))
            return $this->BadRequest();

        $iscrizione = Iscrizione::ById(connection: $this->DB, id: $id);
        if ($iscrizione === null)
            return $this->NotFound();

        if (!$this->getUser()->Admin) {
            if (Settings::Read($this->DB)['subscriptions_enabled'] || !Authorization::CanAccessCertificate(
                $this->getUser(), $this->Staff(), $iscrizione->Parrocchia
            )) {
                return $this->NotAuthorized();
            }

            $personId = Iscrizione::IdAnagraficaAssociata($this->DB, $iscrizione->Id);
            if ($personId === null) {
                return $this->NotFound();
            }

            return $this->Render(
                view: 'Staff/upload-certificate',
                title: 'Carica certificato medico',
                data: [
                    'subscription_id' => $iscrizione->Id,
                    'person_id' => $personId,
                    'csrf_token' => $this->AccountToken(),
                ],
            );
        }

        $a = AnagraficaBase::ById(
            connection: $this->DB, 
            id: Iscrizione::IdAnagraficaAssociata(connection: $this->DB, id: $id)
        );
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

    #[RequireLogin]
    public function edizione(
        ?int $anno = null,
        ?string $motto = null,
    ): StatusCode
    {
        $all = Edizione::All(connection: $this->DB);

        if ($this->IsPost() && $this->User->Admin)
        {
            if (empty($anno) || empty($motto))
                return $this->BadRequest();

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

    #[RequireLogin]
    public function t_shirts(?int $year = null): StatusCode
    {
        if (empty($year)) 
            $year = (int)date(format: 'Y');

        return $this->Render(
            view: 'Staff/t-shirts',
            title: 'Tutte le Maglie',
            data: [
                'anno' => $year,
                'edizioni' => Edizione::All(connection: $this->DB),

                'riepilogo' => Taglia::List(
                    connection: $this->DB, 
                    year: $year,
                    group: true,
                ),
                'lista_completa' => Taglia::List(
                    connection: $this->DB, 
                    year: $year,
                ),
            ],
        );
    }

    #[RequireLogin]
    public function church_leaderboard(?int $year = null): StatusCode
    {
        if (empty($year))
            $year = (int)date(format: 'Y');

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

    #[RequireLogin(requireAdmin: true)]
    public function church_leaderboard_edit(
        ?int $edition,
        ?int $church,
        ?string $score,
    ): StatusCode
    {
        if (!$this->IsPost()) {
            $this->PostRequired();
        }

        if (!PunteggioParrocchia::Insert(
            connection: $this->DB, 
            edizione: $edition, 
            parrocchia: $church, 
            punteggio: $score)
        ) {
            return $this->Json(object: [
                'message' => 'Non è stato possibile aggiornare il punteggio',
            ], statusCode: StatusCode::ServerError);
        }

        return $this->Json(object: [
            'message' => 'Punteggio inserito/modificato correttamente',
        ]);
    }
}