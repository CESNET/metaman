<?php
namespace App\Traits;

use App\Models\Federation;
use Illuminate\Support\Facades\Storage;

trait FederationTrait{

    public function createFederationFolder(string $name): void
    {
        Storage::disk('metadata')->makeDirectory($name);
    }
    public function updateFederationFolders(): void
    {
        $federations = Federation::select('name')->get();

        foreach ($federations as $fed) {
            if(!Storage::disk('metadata')->exists($fed['name'])){
              $this->createFederationFolder($fed['name']);
            }
        }
    }


}
