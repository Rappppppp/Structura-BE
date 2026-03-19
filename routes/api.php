<?php

use App\Http\Controllers\Api\Admin\ChatRoomAdminController;
use App\Http\Controllers\Api\Admin\ClientAdminController;
use App\Http\Controllers\Api\Admin\InvoiceAdminController;
use App\Http\Controllers\Api\Admin\ProjectAdminController;
use App\Http\Controllers\Api\Admin\TaskAdminController;
use App\Http\Controllers\Api\Admin\TeamMemberAdminController;
use App\Http\Controllers\Api\Admin\TimelineEventAdminController;
use App\Http\Controllers\Api\Admin\UserAdminController;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\CommunicationController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TeamController;

Route::middleware('api')->group(function () {
    Route::get('/', function (Request $request) {
        return response()->json(['status' => 'ok', 'app' => config('app.name')]);
    });

    // Public endpoints
    Route::get('projects/analytics/status', [ProjectController::class, 'analyticsStatus']);
    Route::apiResource('clients', ClientController::class)->only(['index', 'show']);
    Route::apiResource('projects', ProjectController::class)->only(['index', 'show']);
    Route::apiResource('invoices', InvoiceController::class)->only(['index', 'show'])->parameters(['invoices' => 'invoice']);
    Route::apiResource('tasks', TaskController::class)->only(['index', 'show']);

    // Authenticated endpoints
    Route::middleware('auth:api')->group(function () {
        Route::apiResource('teams', TeamController::class)->only(['index', 'show']);
        Route::apiResource('communication', CommunicationController::class)->only(['index', 'show']);
        Route::post('communication/{communication}/messages', [CommunicationController::class, 'storeMessage']);
        Route::apiResource('payments', PaymentController::class)->only(['index', 'show']);

        Route::apiResource('clients', ClientController::class)->except(['index', 'show']);
        Route::apiResource('projects', ProjectController::class)->except(['index', 'show']);
        Route::apiResource('invoices', InvoiceController::class)->except(['index', 'show'])->parameters(['invoices' => 'invoice']);
        Route::apiResource('tasks', TaskController::class)->except(['index', 'show']);
    });

    // Authentication
    Route::controller(AuthController::class)->group(function () {
        Route::post('auth/register', 'register');
        Route::post('auth/login', 'login');
        Route::get('auth/me', 'me')->middleware('auth:api');
        Route::post('auth/logout', 'logout')->middleware('auth:api');
        Route::post('auth/refresh', 'refresh')->middleware('auth:api');
    });

    // Admin routes - require JWT auth and admin ability
    Route::middleware(['auth:api', 'can:admin'])->prefix('admin')->group(function () {
        Route::apiResource('clients', ClientAdminController::class);
        Route::apiResource('projects', ProjectAdminController::class);
        Route::apiResource('invoices', InvoiceAdminController::class)->parameters(['invoices' => 'invoice']);
        Route::apiResource('tasks', TaskAdminController::class);
        Route::apiResource('users', UserAdminController::class);
        Route::apiResource('team-members', TeamMemberAdminController::class);
        Route::apiResource('chat-rooms', ChatRoomAdminController::class);
        Route::apiResource('timeline-events', TimelineEventAdminController::class);
    });
});
