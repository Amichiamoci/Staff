<?php
namespace Amichiamoci\Utils;

class Database
{
    public static function GetSchema(): string
    {
        $output = '';

        $database = dirname(path: __DIR__) . DIRECTORY_SEPARATOR . "Database";

        // Helper to append text
        $append = function (string $text) use (&$output): void {
            $output .= $text;
        };

        $file = function (string $name) use ($database): string {
            if (!file_exists(filename: $database . DIRECTORY_SEPARATOR . $name)) {
                throw new \Exception(message: "File '$name' not found in '$database' folder");
            }
            return file_get_contents(
                filename: $database . DIRECTORY_SEPARATOR . $name);
        };

        $directory = function (string $name) use ($append, $file, $database): void {
            $append(text: PHP_EOL . "START TRANSACTION;" . PHP_EOL);

            $pattern = $database . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . '*.sql';
            foreach (glob(pattern: $pattern) as $f)
            {
                $filename = basename(path: $f);
                $append(text: "--" . PHP_EOL . "-- $name/$filename" . PHP_EOL . "--" . PHP_EOL);
                $append(text: $file(name: $name . DIRECTORY_SEPARATOR . $filename) . PHP_EOL);
            }

            $append(text: "COMMIT;" . PHP_EOL);
        };

        // --- Base schema ---
        $append(text: "--" . PHP_EOL . "-- db-schema.sql" . PHP_EOL . "--" . PHP_EOL);
        $append(text: $file(name: 'db-schema.sql'));

        // --- Functions ---
        $directory(name: 'functions');

        // --- Procedures ---
        $directory(name: 'procedures');

        // --- Views ---
        $directory(name: 'views');

        return $output;
    }
}