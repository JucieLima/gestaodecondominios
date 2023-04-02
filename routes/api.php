<?php

use App\Http\Controllers\Api\AreaController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BilletController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\LostAndFoundController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\UnitController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WallController;
use App\Http\Controllers\Api\WarningController;
use Illuminate\Support\Facades\Route;

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
Route::get('/401', [AuthController::class, 'unauthorized'])->name('login');

Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

Route::middleware('auth:api')->group(function(){
    Route::post('/auth/validate', [AuthController::class,'validateToken']);
    Route::post('/auth/logout', [AuthController::class,'logout']);

    //Mural de Avisos
    Route::get('/walls', [WallController::class,'getAll']);
    Route::post('/walls/{id}/like', [WallController::class,'like']);

    //Documentos
    Route::get('/documents', [DocumentController::class,'getAll']);

    //Livro de Ocorrências
    Route::get('/warnings', [WarningController::class,'getMyWarnings']);
    Route::post('/warning', [WarningController::class,'setWarning']);
    Route::post('/warning/file', [WarningController::class,'addWarningFile']);

    //Boletos
    Route::get('/billets/{unit}', [BilletController::class,'getAll']);

    //Achados e perdidos
    Route::get('/lost-and-found', [LostAndFoundController::class,'getAll']);
    Route::post('/lost-and-found', [LostAndFoundController::class,'insert']);
    Route::post('/lost-and-found/{id}', [LostAndFoundController::class,'update']);
    Route::delete('/lost-and-found/{id}', [LostAndFoundController::class,'destroy']);
    Route::post('/lost-and-found/{id}/claim', [LostAndFoundController::class,'claim']);

    //Unidade
    Route::get('unit/{id}', [UnitController::class, 'getInfo']);
    Route::post('unit/{id}/add-person', [UnitController::class, 'addPerson']);
    Route::delete('unit/{id}/remove-person', [UnitController::class, 'removePerson']);
    Route::get('unit/{id}/vehicles', [UnitController::class, 'vehicles']);
    Route::post('unit/{id}/add-vehicle', [UnitController::class, 'addVehicle']);
    Route::post('unit/{id}/update-vehicle', [UnitController::class, 'updateVehicle']);
    Route::delete('unit/{id}/remove-vehicle', [UnitController::class, 'removeVehicle']);
    Route::get('unit/{id}/pets', [UnitController::class, 'pets']);
    Route::post('unit/{id}/add-pet', [UnitController::class, 'addPet']);
    Route::post('unit/{id}/update-pet', [UnitController::class, 'updatePet']);
    Route::delete('unit/{id}/remove-pet', [UnitController::class, 'removePet']);

    //Areas
    Route::get('areas',[AreaController::class, 'index']);
    Route::get('areas/{id}',[AreaController::class, 'show']);
    Route::post('areas/create',[AreaController::class, 'store']);
    Route::post('areas/{id}',[AreaController::class, 'update']);
    Route::delete('areas/{id}',[AreaController::class, 'destroy']);
    Route::get('areas/{id}/disabled-dates',[AreaController::class, 'getDisabledDates']);

    //Reservas
    Route::post('reservation/{id}',[ReservationController::class, 'setReservation']);
    Route::get('my-reservations',[ReservationController::class, 'getMyReservations']);
    Route::delete('delete-reservation/{id}',[ReservationController::class, 'deleteMyReservation']);
    Route::get('reservation/{id}/times',[ReservationController::class, 'getTimes']);
    Route::resource('user', UserController::class);
});
