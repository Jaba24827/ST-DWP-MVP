<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\MfaController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DirectoryController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SL-DWP routes
|--------------------------------------------------------------------------
| Read the middleware stack on each group as the authorisation statement for
| that area: auth (who), mfa (proved twice), perm:<key> (allowed to).
*/

Route::middleware('guest')->group(function () {
    Route::get('/login',  [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    // Reachable with the password alone; nothing else is.
    Route::get('/mfa',         [MfaController::class, 'show'])->name('mfa.show');
    Route::post('/mfa/send',   [MfaController::class, 'send'])->middleware('throttle:5,1')->name('mfa.send');
    Route::post('/mfa/verify', [MfaController::class, 'verify'])->middleware('throttle:10,1')->name('mfa.verify');
    Route::post('/logout',     [LoginController::class, 'destroy'])->name('logout');
});

Route::middleware(['auth','mfa'])->group(function () {

    Route::get('/', fn () => redirect()->route('dashboard'));

    Route::get('/dashboard', DashboardController::class)
        ->middleware('perm:dashboard.view')->name('dashboard');

    Route::get('/directory', [DirectoryController::class, 'index'])
        ->middleware('perm:directory.view')->name('directory.index');

    Route::get('/notices', [AnnouncementController::class, 'index'])
        ->middleware('perm:announcements.view')->name('notices.index');
    Route::post('/notices', [AnnouncementController::class, 'store'])
        ->middleware('perm:announcements.publish')->name('notices.store');
    Route::delete('/notices/{announcement}', [AnnouncementController::class, 'destroy'])
        ->middleware('perm:announcements.publish')->name('notices.destroy');

    Route::get('/documents', [DocumentController::class, 'index'])
        ->middleware('perm:documents.view')->name('documents.index');
    Route::post('/documents', [DocumentController::class, 'store'])
        ->middleware('perm:documents.upload')->name('documents.store');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])
        ->middleware('perm:documents.view')->name('documents.download');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])
        ->middleware('perm:documents.delete')->name('documents.destroy');

    Route::middleware('perm:users.manage')->group(function () {
        Route::get('/users',          [UserController::class, 'index'])->name('users.index');
        Route::post('/users',         [UserController::class, 'store'])->name('users.store');
        Route::patch('/users/{user}', [UserController::class, 'update'])->name('users.update');
    });

    Route::middleware('perm:roles.manage')->group(function () {
        Route::get('/roles',          [RoleController::class, 'index'])->name('roles.index');
        Route::patch('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    });

    Route::get('/audit', [AuditController::class, 'index'])
        ->middleware('perm:audit.view')->name('audit.index');

    Route::middleware('perm:account.security')->group(function () {
        Route::get('/account/security',   [PasswordController::class, 'edit'])->name('account.password.edit');
        Route::put('/account/password',   [PasswordController::class, 'update'])->name('account.password.update');
    });
});
