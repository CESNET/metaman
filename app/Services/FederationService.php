<?php

namespace App\Services;

use App\Models\Federation;
use Illuminate\Support\Facades\Storage;

class FederationService
{
    public static function createFederationFolder(Federation $federation): void
    {
        Storage::disk(config('metaman.metadata'))->makeDirectory($federation->xml_id);
    }

    public static function createEdu2EduGainFolder(): void
    {
        Storage::disk(config('metaman.metadata'))->makeDirectory(config('metaman.eduid2edugain'));
    }

    public static function createFoldersToAllFederation(): void
    {
        $federations = Federation::all();

        foreach ($federations as $fed) {
            if (! Storage::disk(config('metaman.metadata'))->exists($fed['xml_id'])) {
                self::createFederationFolder($fed);
            }
        }
    }

    public static function deleteFederationFolderByXmlId(string $xmlId): void
    {
        Storage::disk(config('metaman.metadata'))->deleteDirectory($xmlId);
    }

    /**
     * @throws \Exception no folder found
     */
    public static function getFederationFolder(Federation $federation): string
    {
        return self::getFederationFolderByXmlId($federation->xml_id);
    }

    /**
     * @throws \Exception
     */
    public static function getFederationFolderById(int $federationId): string
    {
        $federation = Federation::withTrashed()->find($federationId);
        if (! $federation) {
            throw new \Exception('Federation does not exist');
        }

        return self::getFederationFolderByXmlId($federation->xml_id);
    }

    /**
     * @throws \Exception
     */
    public static function getFederationFolderByXmlId(string $xmlId): string
    {
        $disk = Storage::disk(config('metaman.metadata'));

        if ($disk->exists($xmlId)) {
            return $disk->path($xmlId);
        } else {
            throw new \Exception("Directory {$xmlId} does not exist.");
        }
    }
}
