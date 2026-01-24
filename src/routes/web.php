<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\Auth\LoginController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ルートリダイレクト
Route::get('/', function () {
    return redirect('/login');
});

/*
|--------------------------------------------------------------------------
| ゲスト用ルート（未認証）
|--------------------------------------------------------------------------
*/

Route::middleware(['guest'])->group(function () {
    // 一般ユーザーログイン
    Route::get('/login', function () {
        return view('staff.login');
    })->name('login');
    
    // カスタムログイン処理
    Route::post('/login', [LoginController::class, 'login']);

    // 管理者ログイン
    Route::get('/admin/login', function () {
        return view('attendance.login');
    })->name('admin.login');
});

/*
|--------------------------------------------------------------------------
| 管理者ルート
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->prefix('admin')->group(function () {
    // 勤怠管理
    Route::get('/attendance/list', [AttendanceController::class, 'index'])->name('admin.attendance.list');
    Route::get('/attendance/{id}', [AttendanceController::class, 'detail'])->name('admin.attendance.detail');
    Route::put('/attendance/{id}', [AttendanceController::class, 'update'])->name('admin.attendance.update');
    Route::get('/attendance/staff/{id}', [AttendanceController::class, 'staffDetail'])->name('admin.attendance.staff');
    Route::get('/attendance/staff/{id}/csv', [AttendanceController::class, 'exportCsv'])->name('admin.attendance.csv');
    
    // スタッフ管理
    Route::get('/staff/list', [StaffController::class, 'index'])->name('admin.staff.list');
    
    // 申請管理
    Route::get('/request/{id}', [AttendanceController::class, 'requestDetail'])->name('admin.request.detail');
    Route::put('/request/{id}/approve', [AttendanceController::class, 'approveRequest'])->name('admin.request.approve');
});

/*
|--------------------------------------------------------------------------
| 一般ユーザールート（メール認証必須）
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {
    // 勤怠打刻
    Route::get('/attendance', [StaffController::class, 'attendance'])->name('staff.attendance');
    Route::post('/attendance/clock-in', [StaffController::class, 'clockIn'])->name('staff.clock-in');
    Route::post('/attendance/clock-out', [StaffController::class, 'clockOut'])->name('staff.clock-out');
    Route::post('/attendance/break-start', [StaffController::class, 'breakStart'])->name('staff.break-start');
    Route::post('/attendance/break-end', [StaffController::class, 'breakEnd'])->name('staff.break-end');
    
    // 勤怠管理
    Route::get('/attendance/list', [StaffController::class, 'attendanceList'])->name('staff.attendance.list');
    Route::get('/attendance/detail/{id}', [StaffController::class, 'attendanceDetail'])->name('staff.attendance.detail');
    Route::put('/attendance/detail/{id}', [StaffController::class, 'updateAttendance'])->name('staff.attendance.update');
    Route::get('/attendance/monthly', function () {
        return view('staff.monthly');
    })->name('staff.attendance.monthly');
});

/*
|--------------------------------------------------------------------------
| 共通ルート（一般ユーザー・管理者共通）
|--------------------------------------------------------------------------
*/

// 申請一覧（一般ユーザー・管理者共通パス、ミドルウェアで区別）
Route::middleware(['auth'])->group(function () {
    Route::get('/stamp_correction_request/list', [AttendanceController::class, 'requestsListUnified'])->name('requests.list');
});