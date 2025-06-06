<?php

namespace Tests\Feature\Http\Services;

use App\Models\Federation;
use App\Services\FederationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FederationServiceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_get_federation_folder_by_xml_id_returns_valid_path_if_folder_exists()
    {
        Storage::fake('metadata');

        config(['metaman.metadata' => 'metadata']);

        $xmlId = 'test-federation-dir';

        Storage::disk('metadata')->makeDirectory($xmlId);

        $path = FederationService::getFederationFolderByXmlId($xmlId);

        $this->assertStringContainsString($xmlId, $path);
        $this->assertDirectoryExists($path);
    }

    public function test_create_eduid_2_edugain_folder()
    {
        Storage::fake('metadata');

        config(['metaman.metadata' => 'metadata']);

        FederationService::createEduid2EdugainFolder();

        $disk = Storage::disk('metadata');
        $path = $disk->path('eduid2edugain');

        $this->assertDirectoryExists($path);
    }

    public function test_create_folders_to_all_federation_creates_folders_for_each_federation()
    {

        Storage::fake('metadata');
        config(['metaman.metadata' => 'metadata']);

        Federation::factory()->create(['xml_id' => 'fed1']);
        Federation::factory()->create(['xml_id' => 'fed2']);

        $this->assertFalse(Storage::disk('metadata')->exists('fed1'));
        $this->assertFalse(Storage::disk('metadata')->exists('fed2'));

        FederationService::createFoldersToAllFederation();

        $this->assertTrue(Storage::disk('metadata')->exists('fed1'));
        $this->assertTrue(Storage::disk('metadata')->exists('fed2'));

        $this->assertDirectoryExists(Storage::disk('metadata')->path('fed1'));
        $this->assertDirectoryExists(Storage::disk('metadata')->path('fed2'));
    }

    public function test_delete_federation_folder_by_xml_id_deletes_existing_directory()
    {

        Storage::fake('metadata');
        config(['metaman.metadata' => 'metadata']);

        $xmlId = 'test-fed-dir';

        Storage::disk('metadata')->makeDirectory($xmlId);

        $this->assertTrue(Storage::disk('metadata')->exists($xmlId));

        FederationService::deleteFederationFolderByXmlId($xmlId);

        $this->assertFalse(Storage::disk('metadata')->exists($xmlId));
    }

    public function test_get_federation_folder_by_id_throws_exception_if_federation_not_found()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Federation does not exist');

        FederationService::getFederationFolderById(999999);
    }

    public function test_get_federation_folder_by_id_returns_path_when_federation_exists()
    {
        Storage::fake('metadata');
        config(['metaman.metadata' => 'metadata']);

        $federation = Federation::factory()->create([
            'xml_id' => 'test-fed-dir',
        ]);

        Storage::disk('metadata')->makeDirectory($federation->xml_id);

        $path = FederationService::getFederationFolderById($federation->id);

        $this->assertStringContainsString($federation->xml_id, $path);
        $this->assertDirectoryExists($path);
    }
}
