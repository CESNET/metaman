<?php

namespace App\Services;

use App\Models\Federation;
use Illuminate\Support\Facades\Storage;

class FederationService
{
    public static function createFederationFolder(string $name): void
    {

        Storage::disk(config('storageCfg.name'))->makeDirectory($name);
    }

    public static function createFoldersToAllFederation(): void
    {
        $federations = Federation::all();

        foreach ($federations as $fed) {
            if (! Storage::disk(config('storageCfg.name'))->exists($fed['xml_id'])) {
                self::createFederationFolder($fed['xml_id']);
            }
        }
    }
}
