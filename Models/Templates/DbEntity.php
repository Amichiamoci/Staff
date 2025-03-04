<?php

namespace Amichiamoci\Models\Templates;

interface DbEntity
{
    public static function All(\mysqli $connection) : array;

    public static function ById(\mysqli $connection, int $id) : ?self;
}