<?php

namespace Amichiamoci\Models\Templates;

interface DbEntity
{

    /** Lists the records exposed by the implementation. */
    public static function All(\mysqli $connection) : array;

    /** Looks up a record by ID and returns null when it cannot be found. */
    public static function ById(\mysqli $connection, int $id) : ?self;
}