<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Category;
use App\Models\Entity;
use App\Models\Federation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatisticControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_produces_statistics(): void
    {
        User::factory(2)->create();
        Federation::factory(10)->hasAttached(
            Entity::factory()->count(10),
            [
                'requested_by' => User::get()->last()->id,
                'approved_by' => User::get()->first()->id,
                'explanation' => fake()->sentence(),
            ]
        )->create();
        Category::factory(10)->create();

        $this->assertCount(2, User::all());
        $this->assertCount(10, Federation::all());
        $this->assertCount(100, Entity::all());
        $this->assertCount(10, Category::all());

        $entities = Entity::select('type', 'entityid', 'edugain', 'hfd', 'rs', 'cocov1', 'sirtfi')->get();
        $idps = $entities->filter(fn ($e) => $e->type->value === 'idp');
        $sps = $entities->filter(fn ($e) => $e->type->value === 'sp');

        foreach ($idps as $idp) {
            Entity::whereEntityid($idp->entityid)
                ->first()
                ->category()
                ->associate(
                    Category::findOrFail(random_int(1, Category::count()))
                )->save();
        }

        foreach (Category::select('name')->withCount('entities as count')->get() as $category) {
            $idp_category[$category->name] = $category->count;
        }

        $this
            ->get(route('api:statistics'))
            ->assertOk()
            ->assertJson([
                'federations' => [
                    'all' => Federation::count(),
                ],
                'entities' => [
                    'all' => Entity::count(),
                    'edugain' => $entities->filter(fn ($e) => $e->edugain)->count(),
                    'hfd' => $entities->filter(fn ($e) => $e->hfd)->count(),
                    'rs' => $entities->filter(fn ($e) => $e->rs)->count(),
                    'cocov1' => $entities->filter(fn ($e) => $e->cocov1)->count(),
                    'sirtfi' => $entities->filter(fn ($e) => $e->sirtfi)->count(),
                    'idp' => [
                        'all' => $idps->count(),
                        'category' => $idp_category,
                        'hfd' => $idps->filter(fn ($e) => $e->hfd)->count(),
                        'edugain' => $idps->filter(fn ($e) => $e->edugain)->count(),
                        'rs' => $idps->filter(fn ($e) => $e->rs)->count(),
                        'cocov1' => $idps->filter(fn ($e) => $e->cocov1)->count(),
                        'sirtfi' => $idps->filter(fn ($e) => $e->sirtfi)->count(),
                    ],
                    'sp' => [
                        'all' => $sps->count(),
                        'edugain' => $sps->filter(fn ($e) => $e->edugain)->count(),
                        'rs' => $sps->filter(fn ($e) => $e->rs)->count(),
                        'cocov1' => $sps->filter(fn ($e) => $e->cocov1)->count(),
                        'sirtfi' => $sps->filter(fn ($e) => $e->sirtfi)->count(),
                    ],
                ],
            ]);
    }
}
