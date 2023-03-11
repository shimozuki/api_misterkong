<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\FormController;
use App\Http\Controllers\API\ScoreController;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });
Route::group(['middleware' => 'auth:sanctum'], function(){
    // //crud student
    // Route::post('/create', [FormController::class, 'create']);
    // Route::get('/edit/{id}', [FormController::class, 'edit']);
    // Route::post('/edit/{id}', [FormController::class, 'update']);
    // Route::get('/delete/{id}', [FormController::class, 'delete']);

    // //crud score with relation to student
    // Route::post('/create-score-student', [ScoreController::class, 'create']);

    // Route::get('/logout', [AuthController::class, 'logout']);
});

Route::post('/login', [AuthController::class, 'login']);
Route::post('/terdekat', [AuthController::class, 'terdekat']);
Route::post('/terlaris', [AuthController::class, 'terlaris']);
Route::post('/terbaru', [AuthController::class, 'terbaru']);
Route::post('/favorite', [AuthController::class, 'favorite']);
Route::post('/detail', [AuthController::class, 'storeDetail']);
Route::post('/populer', [AuthController::class, 'populer']);
Route::post('/menu', [AuthController::class, 'menu']);
Route::post('/cari', [AuthController::class, 'cari']);
Route::post('/rider', [AuthController::class, 'kongjek']);
Route::post('/info_rider', [AuthController::class, 'info_rider']);
Route::post('/chekout', [AuthController::class, 'chekout']);
Route::post('/getongkir', [AuthController::class, 'getongkir']);
Route::post('/getinfo_rider', [AuthController::class, 'getRiderInfo']);
Route::post('/penolakan', [AuthController::class, 'penolakan']);
Route::post('/no_penagihan', [AuthController::class, 'no_penagihan']);
Route::post('/pecah', [AuthController::class, 'pecah']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/kirim_otp', [AuthController::class, 'kirim_otp']);
Route::post('/kirim_notif', [AuthController::class, 'notifOrder']);
Route::post('/up_driver', [AuthController::class, 'up_driver']);
Route::post('/pembatalan', [AuthController::class, 'pembatalan']);


