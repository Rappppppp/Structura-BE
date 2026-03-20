<?php

use App\Http\Controllers\Api\Admin\ChatRoomAdminController;
use App\Http\Controllers\Api\Admin\ClientAdminController;
use App\Http\Controllers\Api\Admin\InvoiceAdminController;
use App\Http\Controllers\Api\Admin\ProjectAdminController;
use App\Http\Controllers\Api\Admin\TaskAdminController;
use App\Http\Controllers\Api\Admin\TimelineEventAdminController;
use App\Http\Controllers\Api\Admin\UserAdminController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\CommunicationController;
use App\Http\Controllers\Api\ImageGenerationController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ProjectFileController;
use App\Http\Controllers\Api\ProjectTeamController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TaskCommentController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('api')->group(function () {
    Route::get('/', function (Request $request) {
        return response()->json(['status' => 'ok', 'app' => config('app.name')]);
    });

    // Authentication
    Route::controller(AuthController::class)->group(function () {
        Route::post('auth/register', 'register');
        Route::post('auth/login', 'login');
        Route::get('auth/me', 'me')->middleware('auth:api');
        Route::post('auth/logout', 'logout')->middleware('auth:api');
        Route::post('auth/refresh', 'refresh')->middleware('auth:api');
    });

    // Authenticated endpoints
    Route::middleware('auth:api')->group(function () {
        // All read endpoints (require authentication)
        Route::apiResource('clients', ClientController::class)->only(['index', 'show']);
        Route::apiResource('projects', ProjectController::class)->only(['index', 'show']);
        Route::apiResource('invoices', InvoiceController::class)->only(['index', 'show'])->parameters(['invoices' => 'invoice']);
        Route::apiResource('tasks', TaskController::class)->only(['index', 'show']);
        Route::get('projects/analytics/status', [ProjectController::class, 'analyticsStatus']);

        Route::apiResource('communication', CommunicationController::class)->only(['index', 'show']);
        Route::post('communication/rooms', [CommunicationController::class, 'storeRoom']);
        Route::post('communication/{communication}/messages', [CommunicationController::class, 'storeMessage']);
        Route::apiResource('payments', PaymentController::class)->only(['index', 'show']);

        // Attendance management
        Route::get('attendance', [AttendanceController::class, 'index']);
        Route::get('attendance/active', [AttendanceController::class, 'getActive']);
        Route::post('attendance/check-in', [AttendanceController::class, 'checkIn']);
        Route::post('attendance/{attendance}/check-out', [AttendanceController::class, 'checkOut']);
        Route::get('attendance/{attendance}', [AttendanceController::class, 'show']);

        // All write endpoints (require authentication)
        Route::apiResource('clients', ClientController::class)->except(['index', 'show']);
        Route::apiResource('projects', ProjectController::class)->except(['index', 'show']);
        Route::apiResource('invoices', InvoiceController::class)->except(['index', 'show'])->parameters(['invoices' => 'invoice']);
        Route::apiResource('tasks', TaskController::class)->except(['index', 'show']);

        // Project team management
        Route::get('projects/{project}/team', [ProjectTeamController::class, 'index'])
            ->middleware('can:viewProjectTeam,project');
        Route::post('projects/{project}/team', [ProjectTeamController::class, 'store'])
            ->middleware('can:manageProjectTeam,project');
        Route::delete('projects/{project}/team/{user}', [ProjectTeamController::class, 'destroy'])
            ->middleware('can:manageProjectTeam,project');

        // Project files & blueprints
        Route::get('projects/{project}/files', [ProjectFileController::class, 'index']);
        Route::post('projects/{project}/files', [ProjectFileController::class, 'store']);
        Route::delete('projects/{project}/files/{file}', [ProjectFileController::class, 'destroy']);

        // Project images (AI-generated)
        Route::post('projects/{project}/images/generate', [ImageGenerationController::class, 'generateWithOpenAI']);
        Route::get('projects/{project}/images', [ImageGenerationController::class, 'index']);
        Route::post('projects/{project}/images', [ImageGenerationController::class, 'store']);
        Route::delete('projects/{project}/images/{image}', [ImageGenerationController::class, 'destroy']);

        // Task comments
        Route::get('tasks/{task}/comments', [TaskCommentController::class, 'index']);
        Route::post('tasks/{task}/comments', [TaskCommentController::class, 'store']);
        Route::delete('tasks/{task}/comments/{comment}', [TaskCommentController::class, 'destroy']);

        // Users listing for dropdowns (authenticated, not admin-only)
        Route::get('users', function () {
            $users = User::select(['id', 'name', 'email', 'role', 'phone_number'])
                ->orderBy('name')
                ->get();

            return response()->json(['data' => $users, 'message' => 'Users retrieved']);
        });
    });

    // Admin routes - require JWT auth and admin ability
    Route::middleware(['auth:api', 'can:admin'])->prefix('admin')->group(function () {
        Route::apiResource('clients', ClientAdminController::class);
        Route::apiResource('projects', ProjectAdminController::class);
        Route::apiResource('invoices', InvoiceAdminController::class)->parameters(['invoices' => 'invoice']);
        Route::apiResource('tasks', TaskAdminController::class);
        Route::apiResource('users', UserAdminController::class);
        Route::apiResource('chat-rooms', ChatRoomAdminController::class);
        Route::apiResource('timeline-events', TimelineEventAdminController::class);
    });
});
