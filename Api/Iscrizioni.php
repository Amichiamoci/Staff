<?php

declare(strict_types=1);

namespace Amichiamoci\Api;

use DomainException;
use InvalidArgumentException;
use mysqli;
use mysqli_sql_exception;
use RuntimeException;
use Throwable;

use Amichiamoci\Models\{Anagrafica, Edizione, Iscrizione, Taglia};

final class Iscrizioni
{
    private const SUBSCRIPTION_COLUMNS = '`id`, `personId`, `editionId`, `parishId`, `shirtSize`, `guardianId`, `hasCertificate`';

    public static function CanAccess(string $personId): string
    {
        return "(CAST(LOWER(TRIM(a.`email`)) AS BINARY) = ? OR EXISTS (
            SELECT 1
            FROM `api_guardian_access` g
            WHERE g.`personId` = $personId AND g.`email` = ?
        ))";
    }

    public function __construct(private mysqli $db, private string $email)
    {
    }

    /** Lists records linked to the verified email directly or through a current guardian link. */
    public function people(): array
    {
        $rows = $this->db->execute_query(
            'SELECT a.*
             FROM `api_people` a
             WHERE ' . self::CanAccess('a.`id`') . '
             ORDER BY a.`lastName`, a.`firstName`, a.`id`',
            [$this->email, $this->email],
        )->fetch_all(MYSQLI_ASSOC);
        return array_map(self::person(...), $rows);
    }

    /** Lists current registrations for people managed by the verified email. */
    public function subscriptions(): array
    {
        $rows = $this->db->execute_query(
            'SELECT ' . self::SUBSCRIPTION_COLUMNS . '
             FROM `api_subscriptions` a
             WHERE a.`anno` = YEAR(CURRENT_DATE) AND ' . self::CanAccess('a.`personId`') . '
             ORDER BY a.`id`',
            [$this->email, $this->email],
        )->fetch_all(MYSQLI_ASSOC);
        return array_map(self::subscription(...), $rows);
    }

    /** Creates a profile for the verified email or updates an accessible profile while preserving its identity. */
    public function savePerson(
        bool $drive,
        string $firstName,
        string $lastName,
        string $birthDate,
        string $birthPlace,
        string $taxCode,
        int $documentTypeId,
        string $documentExpiry,
        ?int $id = null,
        ?string $phone = null,
        ?string $documentUri = null,
    ): array {
        foreach ([
            'firstName' => [$firstName, 128],
            'lastName' => [$lastName, 256],
            'birthPlace' => [$birthPlace, 256],
            'phone' => [$phone, 16],
            'taxCode' => [$taxCode, 64],
        ] as $field => [$value, $limit]) {
            if ($value !== null && (trim($value) === '' || mb_strlen($value) > $limit || preg_match('/\p{C}/u', $value) !== 0)) {
                throw new InvalidArgumentException("$field must be a nonempty string of at most $limit characters.");
            }
        }
        foreach (['birthDate' => $birthDate, 'documentExpiry' => $documentExpiry] as $field => $value) {
            if (!preg_match('/\A([1-9][0-9]{3})-([0-9]{2})-([0-9]{2})\z/', $value, $parts)
                || !checkdate((int)$parts[2], (int)$parts[3], (int)$parts[1])) {
                throw new InvalidArgumentException("$field must be a valid date in YYYY-MM-DD format.");
            }
        }
        $taxCode = strtoupper(trim($taxCode));
        if (!preg_match('/\A[A-Z0-9]{1,64}\z/', $taxCode) || $birthDate > date('Y-m-d')) {
            throw new InvalidArgumentException('Invalid tax code or birth date.');
        }
        $reference = System::reference($documentUri, 'documentUri', 'documenti', $drive, optional: $id !== null);
        if (!$this->db->execute_query('SELECT 1
            FROM `tipi_documento`
            WHERE `id` = ?', [$documentTypeId])->fetch_row()) {
            throw new InvalidArgumentException('Unknown document type.');
        }

        $this->db->begin_transaction();
        try {
            $email = $this->email;
            $current = $id === null ? null : $this->lockPerson($id);
            if ($current !== null) {
                if (strtoupper($current['codice_fiscale']) !== $taxCode) {
                    throw new InvalidArgumentException('The profile tax code must stay the same.');
                }
                $email = $current['email'];
            }
            $saved = Anagrafica::CreateOrUpdate(
                $this->db, trim($firstName), trim($lastName), $birthDate, trim($birthPlace), $phone === null ? null : trim($phone),
                $email, $taxCode, $documentTypeId, doc_expires: $documentExpiry,
                nome_file: $reference, abort_if_existing: $id === null,
            );
            if ($saved === 0 || ($id !== null && $saved !== $id)) {
                throw new DomainException('This profile could not be saved.');
            }
            $row = $this->db->execute_query(
                'SELECT *
                 FROM `api_people`
                 WHERE `id` = ?', [$saved],
            )->fetch_assoc();
            $result = self::person($row);
            $this->db->commit();
            return $result;
        } catch (Throwable $error) {
            $this->db->rollback();
            if ($error instanceof mysqli_sql_exception && $error->getCode() === 1062) {
                throw new DomainException('This profile could not be saved.');
            }
            throw $error;
        }
    }

    /** Creates or updates a current registration while sign-ups are open. */
    public function subscribe(
        bool $drive,
        int $personId,
        int $editionId,
        int $parishId,
        string $shirtSize,
        ?int $guardianId = null,
        ?string $certificateUri = null,
    ): array {
        $shirt = Taglia::tryFrom(trim($shirtSize));
        $certificate = System::reference($certificateUri, 'certificateUri', 'certificati', $drive, optional: true);
        if ($shirt === null || mb_strlen($shirtSize) > 8 || preg_match('/\p{C}/u', $shirtSize) !== 0) {
            throw new InvalidArgumentException('Unknown shirt size.');
        }

        $this->db->begin_transaction();
        try {
            $enabled = $this->db->query("SELECT `enabled`
                FROM `settings`
                WHERE `name` = 'subscriptions_enabled'
                LOCK IN SHARE MODE")->fetch_row();
            $edition = Edizione::Current($this->db);
            if (!$enabled || !(bool)$enabled[0] || $edition === null || $edition->Id !== $editionId) {
                throw new DomainException('Subscriptions are closed for the requested edition.');
            }
            $this->lockPerson($personId);
            if ($guardianId !== null && ($guardianId === $personId || !$this->db->execute_query(
                'SELECT 1
                 FROM `anagrafiche`
                 WHERE `id` = ? AND CAST(LOWER(TRIM(`email`)) AS BINARY) = ?
                 LOCK IN SHARE MODE',
                [$guardianId, $this->email],
            )->fetch_row())) {
                throw new InvalidArgumentException('Select a different guardian profile linked directly to your email.');
            }
            if (!$this->db->execute_query('SELECT 1
                FROM `parrocchie`
                WHERE `id` = ?', [$parishId])->fetch_row()) {
                throw new InvalidArgumentException('Unknown parish.');
            }

            // Locking the person serializes Web App submissions even for older editions with duplicate rows.
            $existing = $this->db->execute_query(
                'SELECT `id`
                 FROM `iscritti`
                 WHERE `dati_anagrafici` = ? AND `edizione` = ?
                 ORDER BY `id`
                 LIMIT 2
                 FOR UPDATE',
                [$personId, $editionId],
            )->fetch_all(MYSQLI_NUM);
            if (count($existing) > 1) {
                throw new DomainException('Contact the event staff to resolve duplicate subscriptions.');
            }
            if ($existing === []) {
                if (!Iscrizione::Create($this->db, $personId, $guardianId, $certificate, $parishId, $shirt, $editionId)) {
                    throw new RuntimeException('Subscription could not be created.');
                }
                $subscription = $this->db->insert_id;
            } else {
                $subscription = $existing[0][0];
                $this->db->execute_query(
                    'UPDATE `iscritti`
                     SET `parrocchia` = ?, `taglia_maglietta` = ?, `tutore` = COALESCE(?, `tutore`),
                         `certificato_medico` = COALESCE(?, `certificato_medico`)
                     WHERE `id` = ?',
                    [$parishId, $shirt->value, $guardianId, $certificate, $subscription],
                );
            }
            $row = $this->db->execute_query(
                'SELECT ' . self::SUBSCRIPTION_COLUMNS . '
                 FROM `api_subscriptions`
                 WHERE `id` = ?', [$subscription],
            )->fetch_assoc();
            $result = self::subscription($row);
            $this->db->commit();
            return $result;
        } catch (Throwable $error) {
            $this->db->rollback();
            throw $error;
        }
    }

    private function lockPerson(int $id): array
    {
        $person = $this->db->execute_query(
            'SELECT a.`email`, a.`codice_fiscale`
             FROM `anagrafiche` a
             WHERE a.`id` = ? AND ' . self::CanAccess('a.`id`') . '
             FOR UPDATE',
            [$id, $this->email, $this->email],
        )->fetch_assoc();
        if (!$person) {
            throw new DomainException('This profile is unavailable.');
        }
        return $person;
    }

    private static function person(array $row): array
    {
        $row['hasDocument'] = (bool)$row['hasDocument'];
        return $row;
    }

    private static function subscription(array $row): array
    {
        $row['hasCertificate'] = (bool)$row['hasCertificate'];
        return $row;
    }
}
