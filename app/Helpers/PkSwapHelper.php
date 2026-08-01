<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Schema;

class PkSwapHelper
{
    public static function isIntegerColumn(string $table, string $column): bool
    {
        if (!Schema::hasColumn($table, $column)) {
            return false;
        }

        return in_array(Schema::getColumnType($table, $column), ['integer', 'bigint']);
    }
}
