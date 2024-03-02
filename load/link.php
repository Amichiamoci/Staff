<?php
class Link
{
    public static function Geo(string|float $lat, string|float $lon, string $text = null) : string
    {
        if (!isset($lat) || !isset($lon) || is_array($lat) || is_array($lon))
            return "";

        if (!isset($text) || is_array($text) || strlen($text) === 0)
            $text = "Apri in Mappe";

        //$link = "geo:0,0?q=$lat,$lon";
        $link = "geo:$lat,$lon";
        return "<a href=\"$link\" title=\"Apri in Mappe\" class=\"link\">$text</a>";
    }
    public static function Address2Maps(string $addr) : string
    {
        if (!isset($addr)|| is_array($addr))
            return "";

        $display = htmlentities($addr);
        $maps = "https://www.google.com/maps/place/" . str_replace(" ", "+", $addr);
        return "<a class=\"link\" href=\"$maps\" target=\"_blank\" title=\"Apri in Google Maps\">$display</a>";
    }

    public static function Number2WhatsApp(string|int $number) : string
    {
        if (!isset($number) || is_array($number))
            return "";
        return "<a href=\"https://wa.me/$number\" class=\"link\">$number</a>";
    }
}