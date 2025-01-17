<?php
namespace Amichiamoci\Utils;
class Link
{
    public static function Geo(string|float $lat, string|float $lon, ?string $text = null) : string
    {
        if (!isset($lat) || !isset($lon) || is_array(value: $lat) || is_array(value: $lon))
            return "";

        if (!isset($text) || is_array(value: $text) || strlen(string: $text) === 0)
            $text = "Apri in Mappe";

        //$link = "geo:0,0?q=$lat,$lon";
        $link = "geo:$lat,$lon";
        return "<a href=\"$link\" title=\"Apri in Mappe\" class=\"link\">$text</a>";
    }
    public static function Address2Maps(string $addr) : string
    {
        if (!isset($addr)|| is_array(value: $addr))
            return "";

        $display = htmlentities(string: $addr);
        $maps = "https://www.google.com/maps/place/" . str_replace(search: " ", replace: "+", subject: $addr);
        return "<a class=\"link\" href=\"$maps\" target=\"_blank\" title=\"Apri in Google Maps\">$display</a>";
    }

    public static function Number2WhatsApp(string|int $number) : string
    {
        if (!isset($number) || is_array(value: $number))
            return "";
        $number = (string)$number;
        if (str_starts_with(haystack: $number, needle: "+39")) {
            $number = substr(string: $number, offset: 3);
        }
        return "https://wa.me/$number";
    }
}