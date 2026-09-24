<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DirectoryController;
use App\Http\Controllers\DocumentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
| The React presentation tier talks to these endpoints over the session
| cookie (Sanctum stateful). They carry exactly the same middleware as the
| Blade routes — the API is not a side door.
*/

Route::middleware(['auth:sanctum','mfa'])->group(function () {

    Route::get('/me', fn (Request $r) => [
        'id'          => $r->user()->id,
        'name'        => $r->user()->name,
        'role'        => $r->user()->role?->name,
        'sector'      => $r->user()->sector?->name,
        'permissions' => $r->user()->permissionKeys(),   // drives the React navigation
    ]);

    Route::get('/dashboard', DashboardController::class)->middleware('perm:dashboard.view');
    Route::get('/documents', [DocumentController::class,'index'])->middleware('perm:documents.view');
    Route::post('/documents',[DocumentController::class,'store'])->middleware('perm:documents.upload');
    Route::get('/directory', [DirectoryController::class,'index'])->middleware('perm:directory.view');
    Route::get('/audit',     [AuditController::class,'index'])->middleware('perm:audit.view');
});
