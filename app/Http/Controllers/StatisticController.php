<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Entity;
use App\Models\Federation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class StatisticController extends Controller
{
    public function index(): JsonResponse
    {
        $cache_time = now('Europe/Prague')->addHour();

        $CACHE_TIME = Cache::remember('CACHE_TIME', $cache_time, function () use ($cache_time) {
            return $cache_time;
        });

        $federations = Cache::remember('federations', $CACHE_TIME, function () {
            return Federation::count();
        });

        $entity = Cache::remember('entity', $CACHE_TIME, function () {
            return Entity::select('type', 'edugain', 'hfd', 'rs', 'cocov1', 'sirtfi')->get();
        });
        $entities = $entity->count();
        $edugain = $entity->filter(fn ($e) => $e->edugain)->count();
        $hfd = $entity->filter(fn ($e) => $e->hfd)->count();
        $rs = $entity->filter(fn ($e) => $e->rs)->count();
        $cocov1 = $entity->filter(fn ($e) => $e->cocov1)->count();
        $sirtfi = $entity->filter(fn ($e) => $e->sirtfi)->count();

        $idp = $entity->filter(fn ($e) => $e->type->value == 'idp');
        $idps = $idp->count();
        $idps_hfd = $idp->filter(fn ($e) => $e->hfd)->count();
        $idps_edugain = $idp->filter(fn ($e) => $e->edugain)->count();
        $idps_rs = $idp->filter(fn ($e) => $e->rs)->count();
        $idps_cocov1 = $idp->filter(fn ($e) => $e->cocov1)->count();
        $idps_sirtfi = $idp->filter(fn ($e) => $e->sirtfi)->count();

        $categories = Cache::remember('categories', $CACHE_TIME, function () {
            return Category::select('name')->withCount('entities as count')->get();
        });
        foreach ($categories as $c) {
            $idp_category[$c->name] = $c->count;
        }

        $sp = $entity->filter(fn ($e) => $e->type->value == 'sp');
        $sps = $sp->count();
        $sps_edugain = $sp->filter(fn ($e) => $e->edugain)->count();
        $sps_rs = $sp->filter(fn ($e) => $e->rs)->count();
        $sps_cocov1 = $sp->filter(fn ($e) => $e->cocov1)->count();
        $sps_sirtfi = $sp->filter(fn ($e) => $e->sirtfi)->count();

        return response()->json([
            'next_refresh_at' => $CACHE_TIME.' (Europe/Prague)',
            'federations' => [
                'all' => $federations,
            ],
            'entities' => [
                'all' => $entities,
                'edugain' => $edugain,
                'hfd' => $hfd,
                'rs' => $rs,
                'cocov1' => $cocov1,
                'sirtfi' => $sirtfi,
                'idp' => [
                    'all' => $idps,
                    'category' => $idp_category,
                    'hfd' => $idps_hfd,
                    'edugain' => $idps_edugain,
                    'rs' => $idps_rs,
                    'cocov1' => $idps_cocov1,
                    'sirtfi' => $idps_sirtfi,
                ],
                'sp' => [
                    'all' => $sps,
                    'edugain' => $sps_edugain,
                    'rs' => $sps_rs,
                    'cocov1' => $sps_cocov1,
                    'sirtfi' => $sps_sirtfi,
                ],
            ],
        ]);
    }
}
