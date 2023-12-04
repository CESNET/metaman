<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CategoryManagementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EduidczStatisticController;
use App\Http\Controllers\EntityController;
use App\Http\Controllers\EntityFederationController;
use App\Http\Controllers\EntityManagementController;
use App\Http\Controllers\EntityMetadataController;
use App\Http\Controllers\EntityOperatorController;
use App\Http\Controllers\EntityOrganizationController;
use App\Http\Controllers\EntityPreviewMetadataController;
use App\Http\Controllers\EntityRsController;
use App\Http\Controllers\FakeController;
use App\Http\Controllers\FederationController;
use App\Http\Controllers\FederationEntityController;
use App\Http\Controllers\FederationJoinController;
use App\Http\Controllers\FederationManagementController;
use App\Http\Controllers\FederationOperatorController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\GroupManagementController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\ShibbolethController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/language/{locale}', function ($locale = null) {
    if (isset($locale) && in_array($locale, config('app.locales'))) {
        app()->setLocale($locale);
        session()->put('locale', $locale);
    }

    return redirect()->back();
});

Route::get('/', function () {
    return auth()->user() ? view('dashboard') : view('welcome');
})->name('home');

Route::get('blocked', function () {
    return auth()->user() ? redirect('/') : view('blocked');
});

if (App::environment(['local', 'testing'])) {
    Route::post('fakelogin', [FakeController::class, 'store'])->name('fakelogin');
    Route::get('fakelogout', [FakeController::class, 'destroy'])->name('fakelogout');
}

Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::get('federations/import', [FederationManagementController::class, 'index'])->name('federations.unknown');
Route::post('federations/import', [FederationManagementController::class, 'store'])->name('federations.import');
Route::get('federations/refresh', [FederationManagementController::class, 'update'])->name('federations.refresh');

Route::get('federations/{federation}/entities', [FederationEntityController::class, 'index'])->name('federations.entities')->withTrashed();

Route::get('federations/{federation}/operators', [FederationOperatorController::class, 'index'])->name('federations.operators')->withTrashed();

Route::get('federations/{federation}/requests', [FederationJoinController::class, 'index'])->name('federations.requests')->withTrashed();

Route::resource('federations', FederationController::class);
Route::get('federations/{federation}', [FederationController::class, 'show'])->name('federations.show')->withTrashed();
Route::match(['put', 'patch'], 'federations/{federation}', [FederationController::class, 'update'])->name('federations.update')->withTrashed();
Route::delete('federations/{federation}', [FederationController::class, 'destroy'])->name('federations.destroy')->withTrashed();

Route::get('entities/import', [EntityManagementController::class, 'index'])->name('entities.unknown');
Route::post('entities/import', [EntityManagementController::class, 'store'])->name('entities.import');
Route::get('entities/refresh', [EntityManagementController::class, 'update'])->name('entities.refresh');

Route::get('entities/{entity}/operators', [EntityOperatorController::class, 'index'])->name('entities.operators')->withTrashed();

Route::get('entities/{entity}/federations', [EntityFederationController::class, 'index'])->name('entities.federations')->withTrashed();
Route::post('entities/{entity}/join', [EntityFederationController::class, 'store'])->name('entities.join');
Route::post('entities/{entity}/leave', [EntityFederationController::class, 'destroy'])->name('entities.leave');

Route::post('entities/{entity}/rs', [EntityRsController::class, 'store'])->name('entities.rs');

Route::get('entities/{entity}/metadata', [EntityMetadataController::class, 'store'])->name('entities.metadata');
Route::get('entities/{entity}/showmetadata', [EntityMetadataController::class, 'show'])->name('entities.showmetadata');
Route::get('entities/{entity}/previewmetadata', [EntityPreviewMetadataController::class, 'show'])->name('entities.previewmetadata');

Route::post('entities/{entity}/organization', [EntityOrganizationController::class, 'update'])->name('entities.organization');

Route::resource('entities', EntityController::class);
Route::get('entities/{entity}', [EntityController::class, 'show'])->name('entities.show')->withTrashed();
Route::match(['put', 'patch'], 'entities/{entity}', [EntityController::class, 'update'])->name('entities.update')->withTrashed();
Route::delete('entities/{entity}', [EntityController::class, 'destroy'])->name('entities.destroy')->withTrashed();

Route::get('categories/import', [CategoryManagementController::class, 'index'])->name('categories.unknown');
Route::post('categories/import', [CategoryManagementController::class, 'store'])->name('categories.import');
Route::get('categories/refresh', [CategoryManagementController::class, 'update'])->name('categories.refresh');

Route::resource('categories', CategoryController::class);

Route::get('groups/import', [GroupManagementController::class, 'index'])->name('groups.unknown');
Route::post('groups/import', [GroupManagementController::class, 'store'])->name('groups.import');
Route::get('groups/refresh', [GroupManagementController::class, 'update'])->name('groups.refresh');

Route::resource('groups', GroupController::class);

Route::resource('users', UserController::class)->except('edit', 'destroy');

Route::resource('memberships', MembershipController::class)->only('update', 'destroy');

Route::get('statistics', EduidczStatisticController::class);

Route::get('login', [ShibbolethController::class, 'create'])->name('login')->middleware('guest');
Route::get('auth', [ShibbolethController::class, 'store'])->middleware('guest');
Route::get('logout', [ShibbolethController::class, 'destroy'])->name('logout')->middleware('auth');
