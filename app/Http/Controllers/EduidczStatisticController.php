<?php

namespace App\Http\Controllers;

use App\Models\Federation;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Cache;

class EduidczStatisticController extends Controller
{
    public function __invoke(): Factory|Application|View
    {
        $cache_time = now('Europe/Prague')->addHour();

        $CACHE_TIME = Cache::remember('CACHE_TIME', $cache_time, function () use ($cache_time) {
            return $cache_time;
        });

        $entities = Cache::remember('entities', $CACHE_TIME, function () {
            return Federation::whereXml_name('https://eduid.cz/metadata')
                ->first()
                ->entities()
                ->get()
                ->filter(fn ($e) => ! $e->hfd);
        });

        // Number of non-HfD entities in eduID.cz
        $eduidcz = $entities->count();

        // Number of eduID.cz entities joined to eduGAIN
        $edugain = $entities->filter(fn ($e) => $e->edugain)->count();

        // Number of SPs in eduID.cz
        $services = $entities->filter(fn ($e) => $e->type->value === 'sp')->count();

        // Number of IdPs in eduID.cz
        $organizations = $entities->filter(fn ($e) => $e->type->value === 'idp')->count();

        return view('statistics', compact('eduidcz', 'edugain', 'services', 'organizations'));
    }
}
