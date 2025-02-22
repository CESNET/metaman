<?php

namespace App\Http\Controllers;

use App\Facades\HfdTag;
use App\Http\Requests\StoreEntity;
use App\Ldap\CesnetOrganization;
use App\Ldap\EduidczOrganization;
use App\Models\Category;
use App\Models\Entity;
use App\Models\Federation;
use App\Models\User;
use App\Notifications\EntityDestroyed;
use App\Notifications\EntityRequested;
use App\Traits\DumpFromGit\EntitiesHelp\DeleteFromEntity;
use App\Traits\DumpFromGit\EntitiesHelp\UpdateEntity;
use App\Traits\ValidatorTrait;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class EntityController extends Controller
{
    use DeleteFromEntity, UpdateEntity, ValidatorTrait;

    /**
     * @throws AuthorizationException
     */
    public function index(): Factory|Application|View
    {
        $this->authorize('viewAny', Entity::class);

        return view('entities.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @throws AuthorizationException
     */
    public function create(): Factory|Application|View
    {
        $this->authorize('create', Entity::class);

        return view('entities.create', [
            'federations' => Federation::orderBy('name')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @throws AuthorizationException
     */
    public function store(StoreEntity $request): Application|Redirector|RedirectResponse
    {
        $this->authorize('create', Entity::class);

        $validated = $request->validated();

        $metadata = $this->getMetadata($request);
        if (! $metadata) {
            return redirect()
                ->route('entities.create')
                ->with('status', __('entities.metadata_couldnt_be_read'))
                ->with('color', 'red');
        }

        $result = json_decode($this->validateMetadata($metadata), true);
        $new_entity = json_decode($this->parseMetadata($metadata), true);

        if (array_key_exists('result', $new_entity) && ! is_null($new_entity['result'])) {
            return redirect()
                ->back()
                ->with('status', __('entities.no_metadata').' '.$result['error'])
                ->with('color', 'red');
        }

        $existing = Entity::whereEntityid($new_entity['entityid'])->first();
        if ($existing) {
            return redirect()
                ->route('entities.show', $existing)
                ->with('status', __('entities.existing_already'))
                ->with('color', 'yellow');
        }

        switch ($result['code']) {
            case '0':
                $federation = Federation::findOrFail($validated['federation']);
                $entity = DB::transaction(function () use ($new_entity, $federation) {
                    $new_entity = array_merge($new_entity, ['xml_file' => $this->deleteTags($new_entity['metadata'])]);

                    if ($new_entity['type'] === 'idp') {
                        $new_entity = array_merge($new_entity, ['hfd' => true]);
                        $new_entity['xml_file'] = HfdTag::createFromXml($new_entity['xml_file']);
                    }

                    $entity = Entity::create($new_entity);
                    $entity->operators()->attach(Auth::id());
                    $entity->federations()->attach($federation, [
                        'explanation' => request('explanation'),
                        'requested_by' => Auth::id(),
                    ]);

                    return $entity;
                });

                $admins = User::activeAdmins()->select('id', 'email')->get();
                $admins = $admins->merge($federation->operators);
                Notification::send($admins, new EntityRequested($entity, $federation));

                return redirect('entities')
                    ->with('status', __('entities.entity_requested', ['name' => $entity->entityid]).' '.$result['message']);

                break;

            case '1':
                return redirect()
                    ->back()
                    ->with('status', "{$result['error']} {$result['message']}")
                    ->with('color', 'red');
                break;

            default:
                return back()
                    ->with('status', __('entities.unknown_error_while_registration'))
                    ->with('color', 'red');
                break;
        }
    }

    /**
     * @throws AuthorizationException
     */
    public function show(Entity $entity): Factory|Application|View
    {
        $this->authorize('view', $entity);

        if (! app()->environment('testing')) {
            if ($entity->type->value === 'idp' && ! $entity->hfd) {
                $eduidczOrganization = EduidczOrganization::whereEntityIDofIdP($entity->entityid)->first();
                $cesnetOrganization = CesnetOrganization::find($eduidczOrganization?->getFirstAttribute('oPointer'));
                $cesnetOrganizations = is_null($cesnetOrganization) ? CesnetOrganization::select('o')->get() : null;
            }
        }

        return view('entities.show', [
            'entity' => $entity,
            'categories' => Category::orderBy('name')->get(),
            'eduidczOrganization' => $eduidczOrganization ?? null,
            'cesnetOrganization' => $cesnetOrganization ?? null,
            'cesnetOrganizations' => $cesnetOrganizations ?? null,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @throws AuthorizationException
     */
    public function edit(Entity $entity): Factory|View|Application
    {
        $this->authorize('update', $entity);

        return view('entities.edit', compact('entity'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @throws AuthorizationException
     */
    public function update(Request $request, Entity $entity): RedirectResponse
    {
        $this->authorize('update', $entity);

        $validated = $request->validate([
            'metadata' => 'nullable|string',
            'file' => 'required_without:metadata|file',
        ]);

        $metadata = $this->getMetadata($request);
        if (! $metadata) {
            return redirect()
                ->back()
                ->with('status', __('entities.metadata_couldnt_be_read'))
                ->with('color', 'red');
        }

        $result = json_decode($this->validateMetadata($metadata), true);
        $updated_entity = json_decode($this->parseMetadata($metadata), true);

        if (array_key_exists('result', $updated_entity) && ! is_null($updated_entity['result'])) {
            return redirect()
                ->back()
                ->with('status', __('entities.no_metadata'))
                ->with('color', 'red');
        }

        if ($entity->entityid !== $updated_entity['entityid']) {
            return redirect()
                ->back()
                ->with('status', __('entities.different_entityid'))
                ->with('color', 'red');
        }

        switch ($result['code']) {
            case '0':

                $xml_file = $this->deleteTags($updated_entity['metadata']);

                DB::transaction(function () use ($entity, $updated_entity, $xml_file) {
                    $updateData = [
                        'name_en' => $updated_entity['name_en'],
                        'name_cs' => $updated_entity['name_cs'],
                        'description_en' => $updated_entity['description_en'],
                        'description_cs' => $updated_entity['description_cs'],
                        'cocov1' => $updated_entity['cocov1'],
                        'sirtfi' => $updated_entity['sirtfi'],
                        'metadata' => $updated_entity['metadata'],
                        'xml_file' => $xml_file,
                    ];

                    if ($entity->type->value === 'idp') {
                        $updateData['rs'] = $updated_entity['rs'];
                    }

                    $entity->update($updateData);
                });

                if (! $entity->wasChanged()) {
                    return redirect()
                        ->back()
                        ->with('status', __('entities.not_changed'));
                }

                return redirect()
                    ->route('entities.show', $entity)
                    ->with('status', __('entities.entity_updated')." {$result['message']}");

            case '1':
                return redirect()
                    ->back()
                    ->with('status', "{$result['error']} {$result['message']}")
                    ->with('color', 'red');

            default:
                return redirect()
                    ->back()
                    ->with('status', __('entities.unknown_error_while_registration'))
                    ->with('color', 'red');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws AuthorizationException
     */
    public function destroy(Entity $entity): Application|Redirector|RedirectResponse
    {
        $this->authorize('forceDelete', $entity);

        $locale = app()->getLocale();
        $name = $entity->{"name_$locale"} ?? $entity->entityid;

        if (! app()->environment('testing')) {
            if ($entity->type->value === 'idp' && ! $entity->hfd) {
                $eduidczOrganization = EduidczOrganization::whereEntityIDofIdP($entity->entityid)->first();
                if (! is_null($eduidczOrganization)) {
                    $eduidczOrganization->delete();
                }
            }
        }

        $entity->forceDelete();

        $admins = User::activeAdmins()->select('id', 'email')->get();
        Notification::send($admins, new EntityDestroyed($name));

        return redirect('entities')
            ->with('status', __('entities.destroyed', ['name' => $name]));
    }
}
