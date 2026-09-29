```php
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\Api\IndustryController;
use App\Http\Controllers\Api\JobFunctionController;


// 1. Public Routes
// ไม่ต้อง Login

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);


// Companies
Route::apiResource('companies', CompanyController::class)
    ->only(['index', 'show']);


// Jobs
Route::apiResource('jobs', JobController::class)
    ->only(['index', 'show']);


// Reviews
Route::apiResource('reviews', ReviewController::class)
    ->only(['index', 'show']);


// Industries
Route::apiResource('industries', IndustryController::class)
    ->only(['index', 'show']);


// Job Functions
Route::apiResource('job-functions', JobFunctionController::class)
    ->only(['index', 'show']);


// 2. Protected Routes
// ต้อง Login ด้วย Laravel Sanctum

Route::middleware('auth:sanctum')->group(function () {

    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);


    // Companies
    Route::apiResource('companies', CompanyController::class)
        ->except(['index', 'show']);


    // Jobs
    Route::apiResource('jobs', JobController::class)
        ->except(['index', 'show']);


    // Reviews
    Route::apiResource('reviews', ReviewController::class)
        ->except(['index', 'show']);


    // Industries
    Route::apiResource('industries', IndustryController::class)
        ->except(['index', 'show']);


    // Job Functions
    Route::apiResource('job-functions', JobFunctionController::class)
        ->except(['index', 'show']);


    // Current User
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
