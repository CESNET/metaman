<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EntityController;
use App\Http\Controllers\FakeController;
use App\Http\Controllers\FederationController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\NotificationController;
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
    Route::match(['get', 'post'], '/fakelogin/{id?}', [FakeController::class, 'login']);
    Route::get('fakelogout', [FakeController::class, 'logout']);
}

Route::get('dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');

// Route::get('notifications', [NotificationController::class, 'index'])->name('notifications');
// Route::patch('notifications/{id}', [NotificationController::class, 'update'])->name('notifications.update');
// Route::delete('notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

Route::get('federations/import', [FederationController::class, 'unknown'])->name('federations.unknown');
Route::post('federations/import', [FederationController::class, 'import'])->name('federations.import');
Route::get('federations/refresh', [FederationController::class, 'refresh'])->name('federations.refresh');
Route::get('federations/{federation}/operators', [FederationController::class, 'operators'])->name('federations.operators')->withTrashed();
Route::get('federations/{federation}/entities', [FederationController::class, 'entities'])->name('federations.entities')->withTrashed();
Route::get('federations/{federation}/requests', [FederationController::class, 'requests'])->name('federations.requests')->withTrashed();
Route::post('federations/{federation}/request', [FederationController::class, 'request'])->name('federations.request');
Route::resource('federations', FederationController::class);
Route::get('federations/{federation}', [FederationController::class, 'show'])->name('federations.show')->withTrashed();
Route::match(['put', 'patch'], 'federations/{federation}', [FederationController::class, 'update'])->name('federations.update')->withTrashed();
Route::delete('federations/{federation}', [FederationController::class, 'destroy'])->name('federations.destroy')->withTrashed();

Route::get('entities/import', [EntityController::class, 'unknown'])->name('entities.unknown');
Route::post('entities/import', [EntityController::class, 'import'])->name('entities.import');
Route::get('entities/refresh', [EntityController::class, 'refresh'])->name('entities.refresh');
Route::get('entities/{entity}/operators', [EntityController::class, 'operators'])->name('entities.operators')->withTrashed();
Route::get('entities/{entity}/federations', [EntityController::class, 'federations'])->name('entities.federations')->withTrashed();
Route::post('entities/{entity}/join', [EntityController::class, 'join'])->name('entities.join');
Route::post('entities/{entity}/leave', [EntityController::class, 'leave'])->name('entities.leave');
Route::post('entities/{entity}/rs', [EntityController::class, 'rs'])->name('entities.rs');
Route::get('entities/{entity}/metadata', [EntityController::class, 'metadata'])->name('entities.metadata');
Route::get('entities/{entity}/showmetadata', [EntityController::class, 'showmetadata'])->name('entities.showmetadata');
Route::post('entities/{entity}/organization', [EntityController::class, 'organization'])->name('entities.organization');
Route::resource('entities', EntityController::class);
Route::get('entities/{entity}', [EntityController::class, 'show'])->name('entities.show')->withTrashed();
Route::match(['put', 'patch'], 'entities/{entity}', [EntityController::class, 'update'])->name('entities.update')->withTrashed();
Route::delete('entities/{entity}', [EntityController::class, 'destroy'])->name('entities.destroy')->withTrashed();

Route::get('categories/import', [CategoryController::class, 'unknown'])->name('categories.unknown');
Route::post('categories/import', [CategoryController::class, 'import'])->name('categories.import');
Route::get('categories/refresh', [CategoryController::class, 'refresh'])->name('categories.refresh');
Route::resource('categories', CategoryController::class);

Route::get('groups/import', [GroupController::class, 'unknown'])->name('groups.unknown');
Route::post('groups/import', [GroupController::class, 'import'])->name('groups.import');
Route::get('groups/refresh', [GroupController::class, 'refresh'])->name('groups.refresh');
Route::resource('groups', GroupController::class);

Route::resource('users', UserController::class)->except('edit', 'destroy');

Route::resource('memberships', MembershipController::class)->only('update', 'destroy');

Route::get('login', [ShibbolethController::class, 'create'])->name('login')->middleware('guest');
Route::get('auth', [ShibbolethController::class, 'store'])->middleware('guest');
Route::get('logout', [ShibbolethController::class, 'destroy'])->name('logout')->middleware('auth');
