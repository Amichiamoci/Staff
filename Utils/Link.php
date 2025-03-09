<?php
namespace Amichiamoci\Utils;
class Link
{
    public static function Geo(string|float|null $lat, string|float|null $lon): string
    {
        if (!isset($lat) || !isset($lon))
            return "";

        //$link = "geo:0,0?q=$lat,$lon";
        return "geo:$lat,$lon";
    }
    public static function Address2Maps(?string $addr): string
    {
        if (!isset($addr))
            return "";

        return "https://www.google.com/maps/place/" . str_replace(search: " ", replace: "+", subject: $addr);
    }

    public static function Number2WhatsApp(string|int|null $number): string
    {
        if (!isset($number))
            return "";
        $number = (string)$number;
        if (str_starts_with(haystack: $number, needle: "+39")) {
            $number = substr(string: $number, offset: 3);
        }
        return "https://wa.me/$number";
    }
}