<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\authController;
use App\Http\Controllers\Api\companyController;
use App\Http\Controllers\Api\reviewController;
use App\Http\Controllers\Api\jobController;
use App\Http\Controllers\Api\industryController;
use App\Http\Controllers\Api\jobFunctionController;

Route::post('/register', [authController::class, 'register']);
Route::post('/login', [authController::class, 'login']);

// 1. Public Routes (เส้นทางที่ประชาชนทั่วไปสามารถเข้าถึงได้โดยไม่ต้อง Login เช่น ค้นหาบริษัท, ดูรีวิว, ดูประกาศงาน)
Route::apiResource('companies', companyController::class)->only(['index', 'show']);
Route::apiResource('reviews', reviewController::class)->only(['index', 'show']);
Route::apiResource('jobs', jobController::class)->only(['index', 'show']);
Route::apiResource('industries', industryController::class)->only(['index', 'show']);
Route::apiResource('job-functions', jobFunctionController::class)
    ->only(['index', 'show'])
    ->parameters(['job-functions' => 'jobFunction']);

// 2. Protected Routes (เส้นทางที่ต้องผ่านการยืนยันตัวตนด้วย Laravel Sanctum ก่อน เช่น เขียนรีวิว, เพิ่ม/แก้ไขข้อมูล)
Route::middleware('auth:sanctum')->group(function () {
    // ผู้ใช้ทั่วไปหรือแอดมินสามารถจัดการรีวิวของตัวเองได้ (สร้าง, แก้ไข, ลบ)
    Route::apiResource('reviews', reviewController::class)->except(['index', 'show']);

    // เส้นทางเพิ่มเติมสำหรับจัดการข้อมูลบริษัทหรือประกาศงาน (หากสิทธิ์อนุญาต)
    Route::apiResource('companies', companyController::class)->except(['index', 'show']);
    Route::apiResource('jobs', jobController::class)->except(['index', 'show']);

    // ดึงข้อมูลผู้ใช้ปัจจุบันที่ล็อกอินอยู่
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [authController::class, 'logout']);
});
