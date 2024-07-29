<?php

namespace App\Services;

use App\Models\Federation;
use Illuminate\Support\Facades\Storage;

class FederationService
{
    public static function createFederationFolder(Federation $federation): void
    {

        Storage::disk(config('storageCfg.name'))->makeDirectory($federation->xml_id);
    }

    public static function createEdu2EduGainFolder(): void
    {
        Storage::disk(config('storageCfg.name'))->makeDirectory(config('storageCfg.edu2edugain'));
    }

    public static function createFoldersToAllFederation(): void
    {
        $federations = Federation::all();

        foreach ($federations as $fed) {
            if (! Storage::disk(config('storageCfg.name'))->exists($fed['xml_id'])) {
                self::createFederationFolder($fed);
            }
        }
    }

    public static function deleteFederationFolder(Federation $federation): void
    {

        $diskName = config('storageCfg.name');
        $folderName = $federation->xml_id;

        Storage::disk($diskName)->deleteDirectory($folderName);
    }

    public static function getFederationFolder(Federation $federation): string
    {
        $disk = Storage::disk(config('storageCfg.name'));
        $folderPath = $disk->path($federation['xml_id']);

        if ($disk->exists($federation['xml_id'])) {
            return $folderPath;
        } else {
            throw new \Exception('Directory does not exist.');
        }
    }
}
