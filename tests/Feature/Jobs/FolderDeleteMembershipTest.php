<?php

namespace Tests\Feature\Jobs;

use App\Facades\EntityFacade;
use App\Jobs\FolderDeleteMembership;
use App\Models\Entity;
use App\Models\Federation;
use App\Models\Membership;
use App\Models\User;
use App\Notifications\MembershipRejected;
use App\Services\FederationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FolderDeleteMembershipTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_get_federation_returns_federation()
    {
        $entity = Entity::factory()->create();
        $federation = Federation::factory()->create();

        $job = new FolderDeleteMembership($entity, $federation);
        $this->assertEquals($federation, $job->getFederation());
    }

    public function test_get_entity_returns_federation()
    {
        $entity = Entity::factory()->create();
        $federation = Federation::factory()->create();

        $job = new FolderDeleteMembership($entity, $federation);
        $this->assertEquals($entity, $job->getEntity());
    }

    public function test_handle_should_call_fail_if_federation_folder_fails()
    {
        Storage::fake('metadata');
        Queue::fake();
        config(['metaman.metadata' => 'metadata']);

        $entity = Entity::factory()->create();
        $federation = Federation::factory()->create();

        $this->mock(FederationService::class, function ($mock) use ($federation) {
            $mock->shouldReceive('getFederationFolder')
                ->with($federation)
                ->andThrow(new \Exception('Simulated failure'));
        });

        Cache::shouldReceive('lock')->andReturn(new class
        {
            public function block() {}

            public function owner()
            {
                return 'owner';
            }

            public function isOwnedByCurrentProcess()
            {
                return true;
            }

            public function release() {}
        });

        Log::shouldReceive('warning');

        $job = $this->getMockBuilder(FolderDeleteMembership::class)
            ->setConstructorArgs([$entity, $federation])
            ->onlyMethods(['fail'])
            ->getMock();

        $job->expects($this->once())
            ->method('fail')
            ->with($this->callback(function ($e) {
                return $e instanceof \Exception;
            }));
        $job->handle();
    }

    public function test_handle_should_send_reject_where_not_in_file()
    {
        Storage::fake('metadata');
        Queue::fake();
        Notification::fake();
        config(['metaman.metadata' => 'metadata']);

        $user = User::factory()->create();
        $entity = Entity::factory()->create();
        $federation = Federation::factory()->create();
        $operator = User::factory()->create();
        $entity->operators()->attach($operator);
        $entity->federations()->attach($federation, [
            'requested_by' => $user->id,
            'explanation' => 'Test explanation',
            'approved' => 1,
        ]);

        Storage::disk('metadata')->makeDirectory($federation->xml_id);
        $path = FederationService::getFederationFolderByXmlId($federation->xml_id);
        $this->assertDirectoryExists($path);

        $this->assertNotNull(Membership::find(1));

        Cache::shouldReceive('lock')->andReturn(new class
        {
            public function block() {}

            public function owner()
            {
                return 'owner';
            }

            public function isOwnedByCurrentProcess()
            {
                return true;
            }

            public function release() {}
        });

        $job = new FolderDeleteMembership($entity, $federation);
        $job->handle();
        Notification::assertSentTo([$operator], MembershipRejected::class);
    }

    public function test_handle_should_delete_file_from_federation_folder()
    {
        Storage::fake('metadata');
        Queue::fake();
        Notification::fake();
        config(['metaman.metadata' => 'metadata']);

        $xml_document = <<<'XML'
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata" entityID="">
  <md:IDPSSODescriptor protocolSupportEnumeration="">
  </md:IDPSSODescriptor>
</md:EntityDescriptor>
XML;

        $user = User::factory()->create();
        $entity = Entity::factory()->create([
            'xml_file' => $xml_document,
        ]);
        $federation = Federation::factory()->create();
        $operator = User::factory()->create();
        $entity->operators()->attach($operator);
        $entity->federations()->attach($federation, [
            'requested_by' => $user->id,
            'explanation' => 'Test explanation',
            'approved' => 1,
        ]);

        Storage::disk('metadata')->makeDirectory($federation->xml_id);
        $path = FederationService::getFederationFolderByXmlId($federation->xml_id);
        $this->assertDirectoryExists($path);

        $this->assertNotNull(Membership::find(1));

        $relativePath = $federation->xml_id.'/'.$entity->file;
        Storage::disk('metadata')->put($relativePath, $entity->xml_file);

        Cache::shouldReceive('lock')->andReturn(new class
        {
            public function block() {}

            public function owner()
            {
                return 'owner';
            }

            public function isOwnedByCurrentProcess()
            {
                return true;
            }

            public function release() {}
        });

        EntityFacade::shouldReceive('deleteEntityMetadataFromFolder')->once();
        $job = new FolderDeleteMembership($entity, $federation);
        $job->handle();
    }

    public function test_handle_should_return_warning_where_lock_owner_is_null()
    {
        Storage::fake('metadata');
        Queue::fake();
        Notification::fake();
        config(['metaman.metadata' => 'metadata']);

        $xml_document = <<<'XML'
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata" entityID="">
  <md:IDPSSODescriptor protocolSupportEnumeration="">
  </md:IDPSSODescriptor>
</md:EntityDescriptor>
XML;

        $user = User::factory()->create();
        $entity = Entity::factory()->create([
            'xml_file' => $xml_document,
        ]);
        $federation = Federation::factory()->create();
        $operator = User::factory()->create();
        $entity->operators()->attach($operator);
        $entity->federations()->attach($federation, [
            'requested_by' => $user->id,
            'explanation' => 'Test explanation',
            'approved' => 1,
        ]);

        Storage::disk('metadata')->makeDirectory($federation->xml_id);
        $path = FederationService::getFederationFolderByXmlId($federation->xml_id);
        $this->assertDirectoryExists($path);

        $this->assertNotNull(Membership::find(1));

        $relativePath = $federation->xml_id.'/'.$entity->file;
        Storage::disk('metadata')->put($relativePath, $entity->xml_file);

        Cache::shouldReceive('lock')->andReturn(new class
        {
            public function block() {}

            public function owner()
            {
                return null;
            }

            public function isOwnedByCurrentProcess()
            {
                return true;
            }

            public function release() {}
        });

        EntityFacade::shouldReceive('deleteEntityMetadataFromFolder')->once();
        Log::shouldReceive('warning')->once();
        $job = new FolderDeleteMembership($entity, $federation);
        $job->handle();
    }

    public function test_handle_should_return_warning_where_lock_now_own_by_process()
    {
        Storage::fake('metadata');
        Queue::fake();
        Notification::fake();
        config(['metaman.metadata' => 'metadata']);

        $xml_document = <<<'XML'
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata" entityID="">
  <md:IDPSSODescriptor protocolSupportEnumeration="">
  </md:IDPSSODescriptor>
</md:EntityDescriptor>
XML;

        $user = User::factory()->create();
        $entity = Entity::factory()->create([
            'xml_file' => $xml_document,
        ]);
        $federation = Federation::factory()->create();
        $operator = User::factory()->create();
        $entity->operators()->attach($operator);
        $entity->federations()->attach($federation, [
            'requested_by' => $user->id,
            'explanation' => 'Test explanation',
            'approved' => 1,
        ]);

        Storage::disk('metadata')->makeDirectory($federation->xml_id);
        $path = FederationService::getFederationFolderByXmlId($federation->xml_id);
        $this->assertDirectoryExists($path);

        $this->assertNotNull(Membership::find(1));

        $relativePath = $federation->xml_id.'/'.$entity->file;
        Storage::disk('metadata')->put($relativePath, $entity->xml_file);

        Cache::shouldReceive('lock')->andReturn(new class
        {
            public function block() {}

            public function owner()
            {
                return 'owner';
            }

            public function isOwnedByCurrentProcess()
            {
                return false;
            }

            public function release() {}
        });

        EntityFacade::shouldReceive('deleteEntityMetadataFromFolder')->once();
        Log::shouldReceive('warning')->once();
        $job = new FolderDeleteMembership($entity, $federation);
        $job->handle();
    }
}
