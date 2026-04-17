<?php

namespace App\Imports\Traits;

use App\Models\Scopes\FilialScope;

trait MultiEmpresaTrait
{
    protected static function bootMultiEmpresaTrait()
    {
        static::addGlobalScope(new FilialScope);
    }
}
