<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \App\Services\SaveFilesDB
 */
class SaveImagesDB extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Services\SaveFilesDB::class;
    }
}
