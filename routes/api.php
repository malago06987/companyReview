<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\JobController;

// 1. Public Routes (ไม่ต้อง Login)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::apiResource('companies', CompanyController::class)->only(['index', 'show']);
Route::apiResource('jobs', JobController::class)->only(['index', 'show']);
Route::apiResource('reviews', ReviewController::class)->only(['index', 'show']);

// 2. Protected Routes (ต้องยืนยันตัวตนด้วย Token ผ่าน Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('companies', CompanyController::class)->except(['index', 'show']);
    Route::apiResource('jobs', JobController::class)->except(['index', 'show']);
    Route::apiResource('reviews', ReviewController::class)->except(['index', 'show']);

    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
