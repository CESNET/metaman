<?php

namespace App\Traits;

use App\Models\Federation;
use Illuminate\Support\Facades\Storage;

trait FederationTrait
{
    public function createFederationFolder(string $name): void
    {

        Storage::disk(config('storageCfg.name'))->makeDirectory($name);
    }

    public function updateFederationFolders(): void
    {
        $federations = Federation::all();

        foreach ($federations as $fed) {
            if (! Storage::disk(config('storageCfg.name'))->exists($fed['xml_id'])) {
                $this->createFederationFolder($fed['xml_id']);
            }
        }
    }
}
