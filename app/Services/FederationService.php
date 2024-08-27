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

    public static function deleteFederationFolderByXmlId(string $xmlId): void
    {
        Storage::disk(config('storageCfg.name'))->deleteDirectory($xmlId);
    }

    /**
     * @throws \Exception no folder found
     */
    public static function getFederationFolder(Federation $federation): string
    {
        return self::getFederationFolderByXmlId($federation->xml_id);
    }

    public static function getFederationFolderByXmlId(string $xmlId): string
    {
        $disk = Storage::disk(config('storageCfg.name'));

        if ($disk->exists($xmlId)) {
            return $disk->path($xmlId);
        } else {
            throw new \Exception('Directory does not exist.');
        }
    }
}
