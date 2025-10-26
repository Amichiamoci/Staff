<?php
namespace Amichiamoci\Utils;

class Database
{
    public static function GetSchema(): string
    {
        $output = '';

        $queries = dirname(path: __DIR__) . DIRECTORY_SEPARATOR . "queries";

        // Helper to append text
        $append = function (string $text) use (&$output): void {
            $output .= $text;
        };

        $file = function (string $name) use ($queries): string {
            if (!file_exists(filename: $queries . DIRECTORY_SEPARATOR . $name)) {
                throw new \Exception(message: "File '$name' not found in '$queries' folder");
            }
            return file_get_contents(
                filename: $queries . DIRECTORY_SEPARATOR . $name);
        };

        $directory = function (string $name) use ($append, $file, $queries): void {
            $append(text: PHP_EOL . "START TRANSACTION;" . PHP_EOL);

            $pattern = $queries . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . '*.sql';
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