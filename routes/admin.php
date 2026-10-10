<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\TrackingLinkController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::prefix('admin')->group(function () {

     Route::controller(AdminController::class)->group(function () {
                Route::get('login', 'adminLogin')->name('adminLogin');
                Route::get('load/forgetpass', 'loadForgetMyPass')->name('loadForgetMyPass');
                Route::post('find/user', 'findUser')->name('findUser');
                Route::post('validate/login', 'adminValidateLogin')->name('adminValidateLogin');
                Route::post('update/password', 'updatePassword')->name('updatePassword');
                Route::post('validate/otp', 'validateOtp')->name('validateOtp');


        //     Route::post('update/{survey:uuid}', 'update');
        //     Route::get('show/{survey:uuid}', 'show');
        //     Route::get('cheak/status/{survey:uuid}', 'cheakStatus');
         });
         Route::match(['get', 'post'], 'load/otp', [AdminController::class, 'otpLoad'])->name('otpLoad');

    Route::middleware(['auth:admin'])->group(function () {

        Route::controller(AdminController::class)->group(function () {
            Route::get('dashboard', 'dashboard')->name('admin.dashboard');
            Route::get('logout', 'logout')->name('admin.logout');

        });

        Route::controller(TrackingLinkController::class)->prefix('tracking-links')->group(function () {
            Route::get('/', 'index')->name('admin.trackingLinks.index');
            Route::post('/', 'store')->name('admin.trackingLinks.store');
            Route::get('/{trackingLink}', 'show')->name('admin.trackingLinks.show');
            Route::delete('/{trackingLink}', 'destroy')->name('admin.trackingLinks.destroy');
        });

    });

});
