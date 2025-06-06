<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\EduidczStatisticController;
use App\Http\Controllers\EntityCategoryController;
use App\Http\Controllers\EntityController;
use App\Http\Controllers\EntityEdugainController;
use App\Http\Controllers\EntityFederationController;
use App\Http\Controllers\EntityGroupController;
use App\Http\Controllers\EntityHfdController;
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

Route::middleware('auth')->group(function () {
    // Dashboard
    Route::view('dashboard', 'dashboard')->name('dashboard');

    // Federations
    Route::post('federations/{federation}/approve', [FederationApprovalController::class, 'store'])->name('federations.approve');
    Route::delete('federations/{federation}/reject', [FederationApprovalController::class, 'destroy'])->name('federations.reject');

    Route::patch('federations/{federation}/state', [FederationStateController::class, 'update'])->name('federations.state')->withTrashed();

    Route::get('federations/{federation}/requests', [FederationJoinController::class, 'index'])->name('federations.requests');

    Route::get('federations/{federation}/operators', [FederationOperatorController::class, 'index'])->name('federations.operators.index')->withTrashed();
    Route::post('federations/{federation}/operators', [FederationOperatorController::class, 'store'])->name('federations.operators.store')->withTrashed();
    Route::delete('federations/{federation}/operators', [FederationOperatorController::class, 'destroy'])->name('federations.operators.destroy')->withTrashed();

    Route::get('federations/{federation}/entities', [FederationEntityController::class, 'index'])->name('federations.entities.index')->withTrashed();
    Route::post('federations/{federation}/entities', [FederationEntityController::class, 'store'])->name('federations.entities.store')->withTrashed();
    Route::delete('federations/{federation}/entities', [FederationEntityController::class, 'destroy'])->name('federations.entities.destroy')->withTrashed();

    Route::resource('federations', FederationController::class)->withTrashed(['show', 'destroy']);

    // Entities
    Route::get('entities/{entity}/previewmetadata', [EntityPreviewMetadataController::class, 'show'])->name('entities.previewmetadata');

    Route::patch('entities/{entity}/category', [EntityCategoryController::class, 'update'])->name('entities.category.update');
    Route::patch('entities/{entity}/hfd', [EntityHfdController::class, 'update'])->name('entities.hfd');
    Route::post('entities/{entity}/organization', [EntityOrganizationController::class, 'update'])->name('entities.organization');

    Route::post('entities/{entity}/rs', [EntityRsController::class, 'store'])->name('entities.rs.store');
    Route::patch('entities/{entity}/rs', [EntityRsController::class, 'update'])->name('entities.rs.state');

    Route::get('entities/{entity}/operators', [EntityOperatorController::class, 'index'])->name('entities.operators.index')->withTrashed();
    Route::post('entities/{entity}/operators', [EntityOperatorController::class, 'store'])->name('entities.operators.store')->withTrashed();
    Route::delete('entities/{entity}/operators', [EntityOperatorController::class, 'destroy'])->name('entities.operators.destroy')->withTrashed();

    Route::get('entities/{entity}/metadata', [EntityMetadataController::class, 'store'])->name('entities.metadata');
    Route::get('entities/{entity}/showmetadata', [EntityMetadataController::class, 'show'])->name('entities.showmetadata');

    Route::get('entities/{entity}/federations', [EntityFederationController::class, 'index'])->name('entities.federations')->withTrashed();
    Route::get('entities/{entity}/group', [EntityGroupController::class, 'index'])->name('entities.groups')->withTrashed();

    Route::middleware('throttle:anti-ddos-limit')->group(function () {
        Route::post('entities/{entity}/join', [EntityFederationController::class, 'store'])->name('entities.join');
        Route::post('entities/{entity}/leave', [EntityFederationController::class, 'destroy'])->name('entities.leave');
        Route::patch('entities/{entity}/state', [EntityStateController::class, 'update'])->name('entities.state')->withTrashed();
        Route::patch('entities/{entity}/edugain', [EntityEdugainController::class, 'update'])->name('entities.edugain');
        Route::match(['put', 'patch'], 'entities/{entity}', [EntityController::class, 'update'])->name('entities.update')->withTrashed();

        Route::post('entities/{entity}/group/join', [EntityGroupController::class, 'store'])->name('entities.group.join');
        Route::delete('entities/{entity}/group/leave', [EntityGroupController::class, 'destroy'])->name('entities.group.leave')->withTrashed();
    });

    Route::resource('entities', EntityController::class)->except('update')->withTrashed(['show', 'destroy']);

    // Categories
    Route::resource('categories', CategoryController::class)->only('index', 'show');

    // Groups
    Route::resource('groups', GroupController::class)->only('index', 'show');

    // Users
    Route::resource('users', UserController::class)->except('edit', 'destroy');

    // Memberships
    Route::resource('memberships', MembershipController::class)->only('update', 'destroy');
});

Route::get('statistics', EduidczStatisticController::class);

Route::get('login', [ShibbolethController::class, 'create'])->name('login')->middleware('guest');
Route::get('auth', [ShibbolethController::class, 'store'])->middleware('guest');
Route::get('logout', [ShibbolethController::class, 'destroy'])->name('logout')->middleware('auth');
