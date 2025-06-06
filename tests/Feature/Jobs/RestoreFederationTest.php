<?php

namespace Tests\Feature\Jobs;

use App\Facades\EntityFacade;
use App\Jobs\RestoreFederation;
use App\Models\Entity;
use App\Models\Federation;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class RestoreFederationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_restore_federation_constructor_make_membership()
    {
        Storage::fake('metadata');
        Queue::fake();
        config(['metaman.metadata' => 'metadata']);

        $user = User::factory()->create();
        $federation = Federation::factory()->create();
        $entity = Entity::factory()->create();

        $entity->federations()->attach($federation, [
            'requested_by' => $user->id,
            'explanation' => 'Restored entity test',
            'approved' => 1,
        ]);

        $membership = Membership::find(1);

        $this->assertNotNull($membership);

        $job = new RestoreFederation($membership);
        $this->assertEquals($membership, $job->membership);
    }

    public function test_handle_should_call_fail_if_batch_was_cancelled()
    {
        Storage::fake('metadata');
        Queue::fake();
        config(['metaman.metadata' => 'metadata']);

        $user = User::factory()->create();
        $federation = Federation::factory()->create();
        $entity = Entity::factory()->create();

        $entity->federations()->attach($federation, [
            'requested_by' => $user->id,
            'explanation' => 'Restored entity test',
            'approved' => 1,
        ]);

        $membership = Membership::find(1);

        $this->assertNotNull($membership);
        $job = $this->getMockBuilder(RestoreFederation::class)
            ->setConstructorArgs([$membership])
            ->onlyMethods(['fail', 'batch'])
            ->getMock();

        $fakeBatch = Mockery::mock();
        $fakeBatch->shouldReceive('cancelled')->once()->andReturn(true);
        $job->method('batch')->willReturn($fakeBatch);

        $job->expects($this->once())
            ->method('fail')
            ->with($this->callback(function ($e) {
                return $e instanceof \Exception && $e->getMessage() === 'batch was cancelled';
            }));

        $job->handle();
    }

    public function test_handle_should_save_metadata_to_federation_folder()
    {
        Storage::fake('metadata');
        Queue::fake();
        Bus::fake();
        config(['metaman.metadata' => 'metadata']);

        $user = User::factory()->create();
        $federation = Federation::factory()->create();
        $entity = Entity::factory()->create();

        $entity->federations()->attach($federation, [
            'requested_by' => $user->id,
            'explanation' => 'Restored entity test',
            'approved' => 1,
        ]);

        $membership = Membership::find(1);

        EntityFacade::shouldReceive('saveMetadataToFederationFolder')
            ->once()
            ->with($entity->id, $federation->id);

        $job = $this->getMockBuilder(RestoreFederation::class)
            ->setConstructorArgs([$membership])
            ->onlyMethods(['batch'])
            ->getMock();

        $fakeBatch = Mockery::mock();
        $fakeBatch->shouldReceive('cancelled')->once()->andReturn(false);
        $job->method('batch')->willReturn($fakeBatch);

        $job->handle();

        $this->assertTrue(true);
    }

    public function test_handle_should_save_metadata_to_federation_folder2()
    {
        Storage::fake('metadata');
        Queue::fake();
        Bus::fake();
        config(['metaman.metadata' => 'metadata']);

        $user = User::factory()->create();
        $federation = Federation::factory()->create();
        $entity = Entity::factory()->create();

        $entity->federations()->attach($federation, [
            'requested_by' => $user->id,
            'explanation' => 'Restored entity test',
            'approved' => 1,
        ]);

        $membership = Membership::find(1);

        EntityFacade::shouldReceive('saveMetadataToFederationFolder')
            ->once()
            ->with($entity->id, $federation->id)
            ->andThrow(new \Exception('hello'));

        $job = $this->getMockBuilder(RestoreFederation::class)
            ->setConstructorArgs([$membership])
            ->onlyMethods(['batch', 'fail'])
            ->getMock();

        $fakeBatch = \Mockery::mock();
        $fakeBatch->shouldReceive('cancelled')->once()->andReturn(false);
        $job->method('batch')->willReturn($fakeBatch);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('hello');

        $job->handle();
    }
}
