<?php

use App\Http\Controllers\Api\ApprovalController;
use App\Http\Controllers\Api\ApprovalRequestController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FormController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WorkflowController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('forms', [FormController::class, 'index']);
    Route::get('forms/{id}', [FormController::class, 'show']);

    Route::post('forms/{form}/requests', [ApprovalRequestController::class, 'store']);
    Route::get('my/requests', [ApprovalRequestController::class, 'index']);
    Route::get('my/requests/{approvalRequest}', [ApprovalRequestController::class, 'show']);

    Route::middleware('role:approver')->prefix('approvals')->group(function () {
        Route::get('pending', [ApprovalController::class, 'pending']);
        Route::get('past', [ApprovalController::class, 'past']);
        Route::post('{approvalRequest}/approve', [ApprovalController::class, 'approve']);
        Route::post('{approvalRequest}/reject', [ApprovalController::class, 'reject']);
    });

    // Show is open to admins, the requester, and assigned approvers — gated inside controller.
    // Declared after the role:approver group so the literal `pending`/`past` routes win.
    Route::get('approvals/{approvalRequest}', [ApprovalController::class, 'show']);

    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('users', [UserController::class, 'index']);

        Route::get('forms', [FormController::class, 'adminIndex']);
        Route::post('forms', [FormController::class, 'store']);
        Route::put('forms/{form}', [FormController::class, 'update']);
        Route::delete('forms/{form}', [FormController::class, 'destroy']);

        Route::get('forms/{form}/workflow', [WorkflowController::class, 'show']);
        Route::post('forms/{form}/workflow', [WorkflowController::class, 'configure']);
    });
});
