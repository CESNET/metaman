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
use App\Http\Controllers\FederationApprovalController;
use App\Http\Controllers\FederationController;
use App\Http\Controllers\FederationEntityController;
use App\Http\Controllers\FederationJoinController;
use App\Http\Controllers\FederationManagementController;
use App\Http\Controllers\FederationOperatorController;
use App\Http\Controllers\FederationStateController;
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

// Federation group
Route::group(['prefix' => 'federations', 'as' => 'federations.', 'middleware' => ['auth']], function () {
    Route::get('import', [FederationManagementController::class, 'index'])->name('unknown');
    Route::post('import', [FederationManagementController::class, 'store'])->name('import');
    Route::get('refresh', [FederationManagementController::class, 'update'])->name('refresh');

    Route::get('{federation}/entities', [FederationEntityController::class, 'index'])->name('entities')->withTrashed();
    Route::get('{federation}/operators', [FederationOperatorController::class, 'index'])->name('operators')->withTrashed();
    Route::get('{federation}/requests', [FederationJoinController::class, 'index'])->name('requests')->withTrashed();

    Route::patch('{federation}/state', [FederationStateController::class, 'state'])->name('state')->withTrashed();

    Route::post('{federation}/approve', [FederationApprovalController::class, 'store'])->name('approve');
    Route::delete('{federation}/reject', [FederationApprovalController::class, 'destroy'])->name('reject');

    Route::resource('/', FederationController::class)->parameters(['' => 'federation'])->withTrashed();
    Route::get('{federation}', [FederationController::class, 'show'])->name('show')->withTrashed();
    Route::match(['put', 'patch'], '{federation}', [FederationController::class, 'update'])->name('update')->withTrashed();
    Route::delete('{federation}', [FederationController::class, 'destroy'])->name('destroy')->withTrashed();
});

// Entities groups
Route::group(['prefix' => 'entities', 'as' => 'entities.', 'middleware' => ['auth']], function () {
    Route::get('import', [EntityManagementController::class, 'index'])->name('unknown');
    Route::post('import', [EntityManagementController::class, 'store'])->name('import');
    Route::get('refresh', [EntityManagementController::class, 'update'])->name('refresh');

    Route::get('{entity}/operators', [EntityOperatorController::class, 'index'])->name('operators')->withTrashed();
    Route::get('{entity}/federations', [EntityFederationController::class, 'index'])->name('federations')->withTrashed();
    Route::post('{entity}/join', [EntityFederationController::class, 'store'])->name('join');
    Route::post('{entity}/leave', [EntityFederationController::class, 'destroy'])->name('leave');

    Route::post('{entity}/rs', [EntityRsController::class, 'store'])->name('rs');

    Route::get('{entity}/metadata', [EntityMetadataController::class, 'store'])->name('metadata');
    Route::get('{entity}/showmetadata', [EntityMetadataController::class, 'show'])->name('showmetadata');
    Route::get('{entity}/previewmetadata', [EntityPreviewMetadataController::class, 'show'])->name('previewmetadata');

    Route::post('{entity}/organization', [EntityOrganizationController::class, 'update'])->name('organization');

    Route::resource('/', EntityController::class)->parameters(['' => 'entity'])->withTrashed();
    Route::get('{entity}', [EntityController::class, 'show'])->name('show')->withTrashed();
    Route::match(['put', 'patch'], '{entity}', [EntityController::class, 'update'])->name('update')->withTrashed();
    Route::delete('{entity}', [EntityController::class, 'destroy'])->name('destroy')->withTrashed();
});

// Categories group
Route::group(['prefix' => 'categories', 'as' => 'categories.', 'middleware' => ['auth']], function () {
    Route::get('import', [CategoryManagementController::class, 'index'])->name('unknown');
    Route::post('import', [CategoryManagementController::class, 'store'])->name('import');
    Route::get('refresh', [CategoryManagementController::class, 'update'])->name('refresh');

    Route::resource('/', CategoryController::class)->parameters(['' => 'category'])->withTrashed();
});
// Groups group
Route::group(['prefix' => 'groups', 'as' => 'groups.', 'middleware' => ['auth']], function () {
    Route::get('import', [GroupManagementController::class, 'index'])->name('unknown');
    Route::post('import', [GroupManagementController::class, 'store'])->name('import');
    Route::get('refresh', [GroupManagementController::class, 'update'])->name('refresh');

    Route::resource('/', GroupController::class)->parameters(['' => 'group'])->withTrashed();
});

Route::resource('users', UserController::class)->except('edit', 'destroy');

Route::resource('memberships', MembershipController::class)->only('update', 'destroy');

Route::get('statistics', EduidczStatisticController::class);

Route::get('login', [ShibbolethController::class, 'create'])->name('login')->middleware('guest');
Route::get('auth', [ShibbolethController::class, 'store'])->middleware('guest');
Route::get('logout', [ShibbolethController::class, 'destroy'])->name('logout')->middleware('auth');
