<?php

use App\Http\Controllers\School\ActivityController;
use App\Http\Controllers\School\AttendanceController;
use App\Http\Controllers\School\ClassroomController;
use App\Http\Controllers\School\CommunicationController;
use App\Http\Controllers\School\DashboardController;
use App\Http\Controllers\School\EmployeeController;
use App\Http\Controllers\School\ExcuseController;
use App\Http\Controllers\School\GradeController;
use App\Http\Controllers\School\GuardianController;
use App\Http\Controllers\School\GuardianPortalController;
use App\Http\Controllers\School\NoteController;
use App\Http\Controllers\School\NotificationController;
use App\Http\Controllers\School\ReportController;
use App\Http\Controllers\School\SchoolController;
use App\Http\Controllers\School\StudentController;
use Illuminate\Support\Facades\Route;

Route::prefix('school')->name('school.')->middleware(['auth', 'verified', 'can:school.access'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
    Route::get('/notifications/{id}', [NotificationController::class, 'open'])->name('notifications.open');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');

    Route::post('/switch', [SchoolController::class, 'switch'])->middleware('can:schools.manage')->name('switch');
    Route::get('/schools', [SchoolController::class, 'index'])->middleware('can:schools.manage')->name('schools.index');
    Route::post('/schools', [SchoolController::class, 'store'])->middleware('can:schools.manage')->name('schools.store');
    Route::put('/schools/{school}', [SchoolController::class, 'update'])->middleware('can:schools.manage')->name('schools.update');
    Route::delete('/schools/{school}', [SchoolController::class, 'destroy'])->middleware(['can:schools.manage', 'password.confirm'])->name('schools.destroy');
    Route::get('/settings', [SchoolController::class, 'settings'])->middleware('can:school.settings')->name('settings');
    Route::put('/settings', [SchoolController::class, 'saveSettings'])->middleware('can:school.settings')->name('settings.save');

    Route::middleware('can:grades.manage')->group(function () {
        Route::get('/grades', [GradeController::class, 'index'])->name('grades.index');
        Route::post('/grades', [GradeController::class, 'store'])->name('grades.store');
        Route::put('/grades/{grade}', [GradeController::class, 'update'])->name('grades.update');
        Route::delete('/grades/{grade}', [GradeController::class, 'destroy'])->name('grades.destroy');
    });
    Route::middleware('can:classes.manage')->group(function () {
        Route::get('/classes', [ClassroomController::class, 'index'])->name('classes.index');
        Route::post('/classes', [ClassroomController::class, 'store'])->name('classes.store');
        Route::put('/classes/{classroom}', [ClassroomController::class, 'update'])->name('classes.update');
        Route::delete('/classes/{classroom}', [ClassroomController::class, 'destroy'])->name('classes.destroy');
    });

    Route::get('/students', [StudentController::class, 'index'])->middleware('can:students.view')->name('students.index');
    Route::get('/students/import', [StudentController::class, 'importForm'])->middleware('can:students.import')->name('students.import');
    Route::get('/students/import/template', [StudentController::class, 'template'])->middleware('can:students.import')->name('students.template');
    Route::post('/students/import', [StudentController::class, 'import'])->middleware('can:students.import')->name('students.import.store');
    Route::post('/students/move', [StudentController::class, 'move'])->middleware('can:students.manage')->name('students.move');
    Route::get('/students/create', [StudentController::class, 'create'])->middleware('can:students.manage')->name('students.create');
    Route::post('/students', [StudentController::class, 'store'])->middleware('can:students.manage')->name('students.store');
    Route::get('/students/{student}', [StudentController::class, 'show'])->middleware('can:students.view')->name('students.show');
    Route::get('/students/{student}/edit', [StudentController::class, 'edit'])->middleware('can:students.manage')->name('students.edit');
    Route::put('/students/{student}', [StudentController::class, 'update'])->middleware('can:students.manage')->name('students.update');
    Route::delete('/students/{student}', [StudentController::class, 'destroy'])->middleware('can:students.manage')->name('students.destroy');

    Route::get('/attendance', [AttendanceController::class, 'index'])->middleware('can:attendance.view')->name('attendance.index');
    Route::get('/attendance/classes', [AttendanceController::class, 'classes'])->middleware('can:attendance.view')->name('attendance.classes');
    Route::get('/attendance/classes/{classroom}', [AttendanceController::class, 'take'])->middleware('can:attendance.view')->name('attendance.take');
    Route::post('/attendance/classes/{classroom}', [AttendanceController::class, 'save'])->middleware('can:attendance.take')->name('attendance.save');

    Route::get('/excuses', [ExcuseController::class, 'index'])->middleware('can:excuses.view')->name('excuses.index');
    Route::post('/excuses', [ExcuseController::class, 'store'])->middleware('can:excuses.review')->name('excuses.store');
    Route::patch('/excuses/{excuse}', [ExcuseController::class, 'review'])->middleware('can:excuses.review')->name('excuses.review');
    Route::get('/excuses/{excuse}/attachment', [ExcuseController::class, 'attachment'])->name('excuses.attachment');

    Route::get('/notes', [NoteController::class, 'index'])->middleware('can:notes.view')->name('notes.index');
    Route::post('/notes', [NoteController::class, 'store'])->middleware('can:notes.manage')->name('notes.store');
    Route::delete('/notes/{note}', [NoteController::class, 'destroy'])->middleware('can:notes.manage')->name('notes.destroy');

    Route::get('/communication', [CommunicationController::class, 'index'])->middleware('can:communication.access')->name('communication.index');
    Route::post('/communication', [CommunicationController::class, 'log'])->middleware('can:communication.access')->name('communication.log');

    Route::get('/reports', [ReportController::class, 'index'])->middleware('can:reports.view')->name('reports.index');
    Route::get('/reports/export/{format}', [ReportController::class, 'export'])->middleware(['can:reports.export', 'throttle:20,1'])->whereIn('format', ['xlsx', 'pdf'])->name('reports.export');

    Route::middleware('can:employees.manage')->group(function () {
        Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
        Route::put('/employees/{user}', [EmployeeController::class, 'update'])->name('employees.update');
        Route::post('/employees/{user}/reset', [EmployeeController::class, 'reset'])->name('employees.reset');
    });

    Route::get('/guardians', [GuardianController::class, 'index'])->middleware('can:guardians.approve')->name('guardians.index');
    Route::post('/guardians/{user}/students/{student}', [GuardianController::class, 'decide'])->middleware('can:guardians.approve')->name('guardians.decide');

    Route::get('/activity', [ActivityController::class, 'index'])->middleware('can:audit.view')->name('activity');

    Route::middleware('can:guardian.portal')->group(function () {
        Route::get('/my-children', [GuardianPortalController::class, 'index'])->name('guardian.index');
        Route::post('/my-children', [GuardianPortalController::class, 'link'])->middleware('throttle:10,1')->name('guardian.link');
        Route::get('/my-children/absences/{record}/excuse', [GuardianPortalController::class, 'excuseForm'])->name('guardian.excuse');
        Route::post('/my-children/absences/{record}/excuse', [GuardianPortalController::class, 'submitExcuse'])->middleware('can:excuses.submit')->name('guardian.excuse.store');
    });
});
