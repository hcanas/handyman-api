<?php

use App\Http\Controllers\Admin\BanUserController;
use App\Http\Controllers\Admin\DesignateUserController;
use App\Http\Controllers\Admin\UnbanUserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\SendResetPasswordOtpController;
use App\Http\Controllers\Auth\UpdatePasswordController;
use App\Http\Controllers\Department\DepartmentController;
use App\Http\Controllers\Ticket\AssignTicketController;
use App\Http\Controllers\Ticket\CancelTicketController;
use App\Http\Controllers\Ticket\CloseTicketController;
use App\Http\Controllers\Ticket\Comment\AddCommentController;
use App\Http\Controllers\Ticket\Comment\ListCommentsController;
use App\Http\Controllers\Ticket\CreateTicketController;
use App\Http\Controllers\Ticket\ListTicketsController;
use App\Http\Controllers\Ticket\Log\ShowTicketLogsController;
use App\Http\Controllers\Ticket\RejectTicketResolutionController;
use App\Http\Controllers\Ticket\ResolveTicketController;
use App\Http\Controllers\Ticket\ShowTicketController;
use App\Http\Controllers\Ticket\UpdateTicketController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('register', RegisterController::class)->name('register');
    Route::post('login', LoginController::class)->name('login');
    Route::post('request-otp', SendResetPasswordOtpController::class)->name('request-otp');
    Route::patch('reset-password', ResetPasswordController::class)->name('reset-password');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', LogoutController::class)->name('logout');
        Route::patch('update-password', UpdatePasswordController::class)->name('update-password');

        Route::prefix('user/{user}')->name('user.')->group(function () {
            Route::patch('designate', DesignateUserController::class)->name('designate');
            Route::patch('ban', BanUserController::class)->name('ban');
            Route::patch('unban', UnbanUserController::class)->name('unban');
        });

        Route::apiResource('departments', DepartmentController::class)->except('update');
        Route::patch('departments/{department}', [DepartmentController::class, 'update']);

        Route::prefix('tickets')->group(function () {
            Route::get('/', ListTicketsController::class);
            Route::post('/', CreateTicketController::class);
            Route::get('{ticket}', ShowTicketController::class);
            Route::patch('{ticket}', UpdateTicketController::class);
            Route::patch('{ticket}/assign', AssignTicketController::class);
            Route::patch('{ticket}/resolve', ResolveTicketController::class);
            Route::patch('{ticket}/reject-resolution', RejectTicketResolutionController::class);
            Route::patch('{ticket}/close', CloseTicketController::class);
            Route::patch('{ticket}/cancel', CancelTicketController::class);
            Route::get('{ticket}/comments', ListCommentsController::class);
            Route::post('{ticket}/comments', AddCommentController::class);
            Route::get('{ticket}/logs', ShowTicketLogsController::class);
        });
    });
});
