<?php

namespace App\Models;

use App\Models\Scopes\FilialScope;

trait MultiEmpresaTrait 
{
    protected static function bootMultiEmpresaTrait()
    {
        static::addGlobalScope(new FilialScope);
    }
}