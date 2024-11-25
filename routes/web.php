<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\EduidczStatisticController;
use App\Http\Controllers\EntityCategoryController;
use App\Http\Controllers\EntityController;
use App\Http\Controllers\EntityEduGainController;
use App\Http\Controllers\EntityFederationController;
use App\Http\Controllers\EntityHfdController;
use App\Http\Controllers\EntityManagementController;
use App\Http\Controllers\EntityMetadataController;
use App\Http\Controllers\EntityOperatorController;
use App\Http\Controllers\EntityOrganizationController;
use App\Http\Controllers\EntityPreviewMetadataController;
use App\Http\Controllers\EntityRsController;
use App\Http\Controllers\EntityStateController;
use App\Http\Controllers\FakeController;
use App\Http\Controllers\FederationApprovalController;
use App\Http\Controllers\FederationController;
use App\Http\Controllers\FederationEntityController;
use App\Http\Controllers\FederationJoinController;
use App\Http\Controllers\FederationManagementController;
use App\Http\Controllers\FederationOperatorController;
use App\Http\Controllers\FederationStateController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\LanguageController;
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

Route::get('language/{language}', LanguageController::class);

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

// Federation group
Route::group(['prefix' => 'federations', 'as' => 'federations.', 'middleware' => ['auth']], function () {

    Route::get('import', [FederationManagementController::class, 'index'])->name('unknown');
    Route::post('import', [FederationManagementController::class, 'store'])->name('import');
    Route::get('refresh', [FederationManagementController::class, 'update'])->name('refresh');

    Route::get('{federation}/requests', [FederationJoinController::class, 'index'])->name('requests')->withTrashed();

    Route::resource('{federation}/operators', FederationOperatorController::class)->only(['index', 'store'])->withTrashed();
    Route::delete('{federation}/operators', [FederationOperatorController::class, 'destroy'])->name('operators.destroy')->withTrashed();

    Route::resource('{federation}/entities', FederationEntityController::class)->only(['index', 'store'])->withTrashed();
    Route::delete('{federation}/entities', [FederationEntityController::class, 'destroy'])->name('entities.destroy')->withTrashed();

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

    Route::middleware('throttle:anti-ddos-limit')->group(function () {
        Route::post('{entity}/join', [EntityFederationController::class, 'store'])->name('join');
        Route::post('{entity}/leave', [EntityFederationController::class, 'destroy'])->name('leave');
        Route::patch('{entity}/state', [EntityStateController::class, 'state'])->name('state')->withTrashed();
        Route::patch('{entity}/edugain', [EntityEduGainController::class, 'edugain'])->name('edugain')->withTrashed();
        Route::match(['put', 'patch'], '{entity}', [EntityController::class, 'update'])->name('update')->withTrashed();
    });

    Route::get('import', [EntityManagementController::class, 'index'])->name('unknown');
    Route::post('import', [EntityManagementController::class, 'store'])->name('import');
    Route::get('refresh', [EntityManagementController::class, 'update'])->name('refresh');

    Route::get('{entity}/federations', [EntityFederationController::class, 'index'])->name('federations')->withTrashed();

    Route::resource('{entity}/operators', EntityOperatorController::class)->only(['index', 'store'])->withTrashed();
    Route::delete('{entity}/operators', [EntityOperatorController::class, 'destroy'])->name('operators.destroy')->withTrashed();

    Route::post('{entity}/rs', [EntityRsController::class, 'store'])->name('rs.store');
    Route::patch('{entity}/rs', [EntityRsController::class, 'rsState'])->name('rs.state')->withTrashed();

    Route::patch('{entity}/category', [EntityCategoryController::class, 'update'])->name('category.update');

    Route::patch('{entity}/hfd', [EntityHfdController::class, 'update'])->name('hfd');

    Route::get('{entity}/metadata', [EntityMetadataController::class, 'store'])->name('metadata');
    Route::get('{entity}/showmetadata', [EntityMetadataController::class, 'show'])->name('showmetadata');

    Route::get('{entity}/previewmetadata', [EntityPreviewMetadataController::class, 'show'])->name('previewmetadata');

    Route::post('{entity}/organization', [EntityOrganizationController::class, 'update'])->name('organization');

    Route::resource('/', EntityController::class)->parameters(['' => 'entity'])->except('update');
    Route::get('{entity}', [EntityController::class, 'show'])->name('show')->withTrashed();
    Route::delete('{entity}', [EntityController::class, 'destroy'])->name('destroy')->withTrashed();
});

Route::middleware('auth')->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::resource('categories', CategoryController::class)->only('index', 'show');
    Route::resource('groups', GroupController::class)->only('index', 'show');
    Route::resource('users', UserController::class)->except('edit', 'destroy');
    Route::resource('memberships', MembershipController::class)->only('update', 'destroy');
});

Route::get('statistics', EduidczStatisticController::class);

Route::get('login', [ShibbolethController::class, 'create'])->name('login')->middleware('guest');
Route::get('auth', [ShibbolethController::class, 'store'])->middleware('guest');
Route::get('logout', [ShibbolethController::class, 'destroy'])->name('logout')->middleware('auth');
