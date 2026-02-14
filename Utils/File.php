<?php
namespace Amichiamoci\Utils;

use Mpdf\Mpdf;
use Richie314\SimpleMvc\Utils\File as BaseFile;

class File
extends BaseFile
{
    const APP = 'app:///';
    const STAFF = 'staff:///';

    const Documents = self::STAFF . 'documenti';
    const Certificates = self::STAFF . 'certificati';

    const VolumeName = 'data';

    /**
     * If the current installation is in a subfolder of the document root, 
     * returns the path to that subfolder.
     * @throws \Exception if the current position is not within the document root
     * @return string 
     */
    public static function getInstallationPath(): string
    {
        $documentRoot = $_SERVER['DOCUMENT_ROOT'];
        $currentPosition = dirname(path: __DIR__, levels: 1);

        if ($documentRoot === $currentPosition)
            return '';

        if (!str_starts_with(haystack: $currentPosition, needle: $documentRoot))
            throw new \Exception(message: "Current position is not within the document root");

        return substr(string: $currentPosition, offset: strlen(string: $documentRoot));
    }

    /**
     * Returns the physical (local) path for the volume to use as storage
     * for the current system
     * @return string The physical path to the storage volume.
     *                It will be a subfolder of `$_SERVER['DOCUMENT_ROOT']`
     */
    public static function getVolumePhysicalPath(): string
    {
        return 
            $_SERVER['DOCUMENT_ROOT'] . 
            self::getInstallationPath() . 
            DIRECTORY_SEPARATOR . 
            self::VolumeName;
    }

    /**
     * Checks if the given path refers to an external file: 
     * accessible via a network protocol like ftp, http, https, or app schema.
     * @param string $filename The file path to check
     * @return bool True if the file is external, false otherwise
     */
    public static function IsExternalFile(string $filename): bool
    {
        $protocols = [
            'http://', 'https://', 
            'ftp://', 'ftps://',
            self::APP,
        ];

        return array_any(
            array: $protocols, 
            callback: function (string $protocol) use ($filename): bool {
                return str_starts_with(haystack: $filename, needle: $protocol);
        });
    }

    /**
     * Checks if the given path uses staff:///, app:/// or another network protocol
     * @param string $path The path to check
     * @return bool true if the path is a virtual path, false otherwise
     */
    public static function IsVirtualPath(string $path): bool
    {
        return 
            str_starts_with(haystack: $path, needle: self::STAFF) ||
            self::IsExternalFile(filename: $path);
    }
    
    /**
     * Converts a path of the type staff:/// and maps it to the appropriate local volume
     * @param string $virtual_path The virtual path to convert
     * @return bool|string The mapped path if the input is a valid virtual path, false otherwise
     */
    public static function PhysicalPath(string $virtual_path): string|false
    {
        if (strlen(string: $virtual_path) === 0 || 
            self::IsExternalFile(filename: $virtual_path)
        )
            return false;

        if (str_starts_with(haystack: $virtual_path, needle: self::STAFF))
            $virtual_path = substr(string: $virtual_path, offset: strlen(string: self::STAFF));

        if (!str_starts_with(haystack: $virtual_path, needle: DIRECTORY_SEPARATOR))
            $virtual_path = DIRECTORY_SEPARATOR . $virtual_path;

        $volumePhysicalPath = self::getVolumePhysicalPath();
        if (!str_starts_with(haystack: $virtual_path, needle: $volumePhysicalPath))
            $virtual_path = $volumePhysicalPath . $virtual_path;

        return realpath(path: $virtual_path);
    }

    /**
     * Converts a (local) physical path to its virtual representation using the
     * staff:/// schema
     * @param string $physical_path The physical path to convert
     * @return bool|string The virtual path if the input is a valid physical path, false otherwise
     */
    public static function VirtualPath(string $physical_path): string|false
    {
        $server_upload_path = self::getVolumePhysicalPath();
        
        if (strlen(string: $physical_path) === 0 ||
            self::IsExternalFile(filename: $physical_path) ||
            !str_starts_with(haystack: $physical_path, needle: $server_upload_path)
        )
            return false;

        if (str_starts_with(haystack: $physical_path, needle: $server_upload_path))
            $physical_path = substr(string: $physical_path, offset: strlen(string: $server_upload_path));
        
        while (str_starts_with(haystack: $physical_path, needle: DIRECTORY_SEPARATOR))
            $physical_path = substr(string: $physical_path, offset: 1);

        return self::STAFF . $physical_path;
    }

    /**
     * Returns a valid physical path to store temporary files
     * @return string
     */
    public static function getTemporaryDir(): string
    {
        return 
            self::PhysicalPath(virtual_path: self::STAFF . 'tmp') ?: 
            sys_get_temp_dir();
    }

    /**
     * Returns a physical path to store logs
     * @return string
     */
    public static function getLogDir(): string
    {
        return 
            self::PhysicalPath(virtual_path: self::STAFF . 'log');
    }

    /**
     * Checks if a file exists.
     * External files (web or app:/// protocols) are simply assumed to exist, without any actual check
     * @param string $file_path The path of the file to check
     * @return bool true if the file exists or is an external file, false otherwise
     */
    public static function Exists(string $file_path): bool
    {
        if (self::IsExternalFile(filename: $file_path))
            return true;

        $physical_path = self::PhysicalPath(virtual_path: $file_path);
        if (!$physical_path) 
            return false;

        return parent::Exists(file_path: $physical_path);
    }

    public static function Delete(string $file_path): bool
    {
        if (self::IsExternalFile(filename: $file_path))
            return false;

        $physical_path = self::PhysicalPath(virtual_path: $file_path);
        if (!$physical_path) 
            return false;

        return parent::Delete(file_path: $physical_path);
    }

    public static function Size(string $file_path): string
    {
        if (self::IsExternalFile(filename: $file_path))
            return '';

        $physical_path = self::PhysicalPath(virtual_path: $file_path);
        if (!$physical_path) 
            return '';

        return parent::Size(file_path: $physical_path);
    }

    public static function ListDirectory(string $dir): array
    {
        if (self::IsExternalFile(filename: $dir))
            return [];
        
        $physical_path = self::PhysicalPath(virtual_path: $dir);
        if (!$physical_path) 
            return [];

        return parent::ListDirectory(dir: $physical_path);
    }

    private static function ListDirectoryFlattened(string $dir): array
    {
        $unflatted = self::ListDirectory(dir: $dir);

        $files = array_filter(
            array: $unflatted,
            callback: function (string $key, string|array $value): bool {
                return is_string(value: $value) && $key === $value;
        }, mode: ARRAY_FILTER_USE_BOTH);

        return array_map(callback: function (string $key) use($dir): string {
            return $dir . DIRECTORY_SEPARATOR . $key;
        }, array: array_keys($files));
    }

    public static function SavedDocuments(): array
    {
        return self::ListDirectoryFlattened(dir: self::Documents);
    }

    public static function SavedCertificates(): array
    {
        return self::ListDirectoryFlattened(dir: self::Certificates);
    }

    public static function GetExportUrl(string $path): string
    {
        if (self::IsExternalFile(filename: $path))
        {
            if (str_starts_with(haystack: $path, needle: 'app:///'))
            {
                // File is on a separate server. The server that hosts the app specifically.
                // TODO: conver schema app:/// to the app's export url
            }
            return $path;
        }
        return self::getInstallationPath() . "/file?name=$path";
    }

    public static array $ALLOWED_EXT = [
        "jpg", 
        "jpeg", 
        "png",
        "webp",
        "heic",
        "heif",
        "pdf",        
        "doc",
        "docx",
    ];
    public static function ALLOWED_EXT_DOTS(): string
    {
        return join(
            separator: ', ',
            array: array_map(
                callback: function(string $s): string { return ".$s"; }, 
                array: self::$ALLOWED_EXT
            )
        );
    } 

    public static function MAX_SIZE(): int
    {
        $str = Security::LoadEnvironmentOfFromFile(var: 'FILE_MAX_SIZE');

        if (!empty($str) && is_numeric(value: $str))
            return (int)$str;

        return 10 * 1024 * 1024; // Default size limit of 10MB
    }

    public static function IsUploadError(mixed $file): bool
    {
        return 
            array_key_exists(key: 'error', array: $file) && 
            (int)$file['error'] !== UPLOAD_ERR_OK;
    }

    public static function IsUploadValidFileSize(mixed $file): bool
    {
        return
            array_key_exists(key: 'size', array: $file) &&
            (int)$file['size'] <= self::MAX_SIZE(); 
    }

    public static function IsAllowedExtension(mixed $file): bool
    {
        if (!array_key_exists(key: 'name', array: $file))
            return false;

        $ext = pathinfo(path: $file['name'], flags: PATHINFO_EXTENSION);
        return in_array(needle: $ext, haystack: self::$ALLOWED_EXT);
    }

    public static function IsUploadOk(mixed $file): bool
    {
        return 
            !self::IsUploadError(file: $file) &&
            self::IsUploadValidFileSize(file: $file) &&
            self::IsAllowedExtension(file: $file);
    }

    public static function CombinePdfs(array $file_names, string $final_name): bool
    {
        if (count(value: $file_names) === 0)
            return false;

        try {
            if (count(value: $file_names) === 1)
                return copy(from: $file_names[0], to: $final_name);

            $pdf = new Mpdf(config: [
                'mode' => 'utf-8',
                'tempDir' => self::getTemporaryDir(),
            ]);

            // Filter out non-existing files
            $file_names = array_filter(array: $file_names, callback: function (string $file): bool {
                return is_file(filename: $file) && str_ends_with(haystack: $file, needle: '.pdf');
            });

            for ($file_index = 0; $file_index < count(value: $file_names); $file_index++)
            {
                $pages_count = $pdf->SetSourceFile(file: $file_names[$file_index]);
                for ($page_index = 1; $page_index <= $pages_count; $page_index++)
                {
                    $tplId = $pdf->ImportPage(pageNumber: $page_index);
                    $pdf->UseTemplate(tpl: $tplId);

                    if ($file_index < count(value: $file_names) - 1 || 
                        $page_index !== $pages_count
                    )
                        $pdf->WriteHTML(html: '<pagebreak />');
                }
            }

            $pdf->Output(name: $final_name, dest: 'F');
            return true;

        } catch (\Throwable) {
            return false;
        }
    }

    public static function ImageToPdf(string $img): ?string
    {
        if (!is_file(filename: $img))
            return null;

        try {
            $pdf = new Mpdf(config: [
                'mode' => 'utf-8',
                'tempDir' => self::getTemporaryDir(),
            ]);
            
            $pdf->imageVars['img'] = file_get_contents(filename: $img);
            $pdf->WriteHtml(html: '<img src="var:img" style="width: 100%;" />');

            $pdf->Output(name: $img . '.pdf', dest: 'F');
            return $img . '.pdf';
        } catch (\Exception) {
            return null;
        }
    }

    public static function UploadDocumentsMerge(array $files, string $final_name): ?string
    {
        if (empty($final_name))
            $final_name = uniqid(more_entropy: true);

        if (!str_starts_with(haystack: $final_name, needle: DIRECTORY_SEPARATOR))
            $final_name = DIRECTORY_SEPARATOR . $final_name;

        $final_name = self::PhysicalPath(virtual_path: $final_name);

        if (count(value: $files) === 0)
            return null;

        function isWordDocument($file): bool
        {
            $extension = pathinfo(path: $file['name'], flags: PATHINFO_EXTENSION);
            return $extension === 'docx' || $extension === 'doc';
        }

        if (array_any(array: $files, callback: function ($f): bool { return isWordDocument(file: $f); }))
        {
            // Upload the first docx file

            $file = array_filter(array: $files, callback: function ($f): bool { return isWordDocument(file: $f); })[0];
            $extension = pathinfo(path: $file['name'], flags: PATHINFO_EXTENSION);
            $final_name .= ".$extension";
            
            if (!move_uploaded_file(from: $file['tmp_name'], to: $final_name))
                // Error in saving the file
                return null;

            return $final_name;
        }

        // All pdfs or images -> combine them in a single pdf

        $final_name .= ".pdf";
        // Merge all files into a pdf
        $paths = [];
        foreach ($files as $file)
        {
            $extension = pathinfo(path: $file['name'], flags: PATHINFO_EXTENSION);
            $pre_merge_name = 
                self::getTemporaryDir() . DIRECTORY_SEPARATOR .
                uniqid(more_entropy: true) . "." . $extension;
            
            if (!move_uploaded_file(from: $file['tmp_name'], to: $pre_merge_name))
                // Error in saving the file
                return null;

            if ($extension !== 'pdf')
            {
                // Convert image to pdf
                $pdf_path = self::ImageToPdf(img: $pre_merge_name);
                if (empty($pdf_path))
                    // Could not convert to pdf
                    return null;

                self::Delete(file_path: $pre_merge_name);
                $pre_merge_name = $pdf_path;
            }

            $paths[] = $pre_merge_name;
        }

        if (!self::CombinePdfs(
            file_names: $paths, 
            final_name: $final_name)
        ) 
            return null;

        // Delete temporary files
        foreach ($paths as $tmps)
            self::Delete(file_path: $tmps);

        return $final_name;
    }
}