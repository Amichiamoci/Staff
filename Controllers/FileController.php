<?php

namespace Amichiamoci\Controllers;

use finfo;
use InvalidArgumentException;
use RuntimeException;

use Richie314\SimpleMvc\Controllers\Attributes\RequireLogin;
use Richie314\SimpleMvc\Http\StatusCode;

use Amichiamoci\Models\{Anagrafica, Iscrizione};
use Amichiamoci\Utils\Authorization;

class FileController extends Controller
{
    #[RequireLogin]
    public function document(?int $id = null): StatusCode
    {
        if (empty($id) || !Authorization::CanAccessIdentityDocument($this->getUser()))
        {
            return $this->NotFound();
        }

        $anagrafica = Anagrafica::ById($this->DB, $id);
        if ($anagrafica === null) {
            return $this->NotFound();
        }

        return $this->SendDownload($anagrafica->DocumentFileName);
    }

    #[RequireLogin]
    public function certificate(?int $id = null): StatusCode
    {
        if (empty($id)) {
            return $this->NotFound();
        }

        $iscrizione = Iscrizione::ById($this->DB, $id);
        if ($iscrizione === null) {
            return $this->NotFound();
        }

        $user = $this->getUser();
        $staff = $this->Staff();

        if (!Authorization::CanAccessCertificate($user, $staff, $iscrizione->Parrocchia)) {
            return $this->NotFound();
        }

        return $this->SendDownload($iscrizione->Certificato);
    }


    #[RequireLogin(requireAdmin: true)]
    public function download(?string $name = null): StatusCode
    {
        if (empty($name)) {
            return $this->NotFound();
        }
        return $this->SendDownload($name, 'application/octet-stream');
    }

    private function SendDownload(?string $name, ?string $mime = null): StatusCode
    {
        if ($name === null || $name === '') {
            return $this->NotFound();
        }

        $files = $this->Files();
        try {
            $content = $files->read($name);
            $mime ??= (new finfo(FILEINFO_MIME_TYPE))->buffer($content) ?: 'application/octet-stream';
        } catch (InvalidArgumentException | RuntimeException) {
            return $this->NotFound();
        }

        header("Content-Disposition: attachment; filename*=UTF-8''" . rawurlencode(basename($name)));
        return $this->Content($mime, $content);
    }

    #[RequireLogin(requireAdmin: true)]
    public function list(?string $source = null, string $folder = ''): StatusCode
    {
        $files = $this->Files();
        $sources = [];
        foreach ($files->listMounts() as $mount) {
            $sources[$mount['scheme'] . ':///'] = $mount['name'];
        }
        if ($source !== null && !isset($sources[$source])) {
            return $this->NotFound();
        }

        $entries = [];
        $available = $source !== 'app:///';
        if ($source !== null && $available) {
            try {
                $entries = iterator_to_array($files->listContents($source . $folder)->sortByPath());
            } catch (\Throwable) {
                $available = false;
            }
        }

        return $this->Render(
            view: 'File/list',
            title: 'Sorgenti file',
            data: compact('sources', 'source', 'folder', 'entries', 'available'),
        );
    }
}
