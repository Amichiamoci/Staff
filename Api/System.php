<?php

declare(strict_types=1);

namespace Amichiamoci\Api;

use InvalidArgumentException;
use mysqli;

use Richie314\SimpleVirtualFS\VirtualFilesystem;

use Amichiamoci\Models\{Edizione, Settings, Taglia};
use Amichiamoci\Utils\Security;

final class System
{
    public function __construct(
        private mysqli $db, 
        private VirtualFilesystem $files,
    ) {  }

    /** Returns current edition details and registration choices, fields, and storage settings. */
    public function details(): array
    {
        $edition = Edizione::Current($this->db);
        return [
            'edition' => $edition === null ? null : [
                'id' => $edition->Id, 
                'year' => $edition->Year, 
                'motto' => $edition->Motto,
            ],
            'subscriptionsOpen' => $edition !== null && Settings::Read($this->db)['subscriptions_enabled'],
            'parishes' => $this->parishes(),
            'documentTypes' => $this->rows('SELECT `id`, `label`
                FROM `tipi_documento`
                ORDER BY `id`'),
            'shirtSizes' => Taglia::All(),
            'fields' => $this->fields(),
            'storage' => self::configuration(),
        ];
    }

    private function parishes(): array
    {
        return $this->rows(
            'SELECT
                 `id`,
                 `nome`      AS "name",
                 `indirizzo` AS "address",
                 `website`
             FROM `parrocchie`
             ORDER BY `nome`'
             );
    }

    private function configuration(): array
    {
        $config = [
            'sources' => [
                'app:///' => [
                    'type' => 'built-in'
                ],
            ],
            'preferred' => 'app:///',
        ];
        
        if (in_array('drive', array_column($this->files->listMounts(), 'scheme'), true)) {
            $key = trim(Security::LoadEnvironmentOrFromFile(var: 'VFS_DRIVE_BASE_KEY') ?? '');
            $config['sources']['drive:///'] = [
                'type' => 'google-drive',
                'config' => [
                    'folderId' => Security::LoadEnvironmentOrFromFile(var: 'VFS_DRIVE_FOLDER_ID')
                ],
                'encryptionKey' => $key === '' ? null : $key,
            ];
        }

        return $config;
    }

    /** Lists fields and coordinates for the match calendar. */
    private function fields(): array
    {
        return $this->rows(
            'SELECT
                 `id`,
                 `nome`            AS "name",
                 `indirizzo`       AS "address",
                 ST_Y(`posizione`) AS "latitude",
                 ST_X(`posizione`) AS "longitude"
             FROM `campi`
             ORDER BY `nome`, `id`'
        );
    }

    private function rows(string $query): array
    {
        return $this->db->execute_query($query)->fetch_all(MYSQLI_ASSOC);
    }
}
