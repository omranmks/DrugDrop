<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DrugController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\OperationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\UserController;

use Illuminate\Support\Facades\Route;

use Pusher\Pusher;


Route::post('test', [UserController::class, 'test']);
Route::post('test2', [UserController::class, 'ReciveRestorationCode']);

Route::group(['middleware' => 'auth:sanctum'], function () {
    
    Route::group(['middleware' => 'Warhouse'], function () {
        
        Route::put('admin/drug/create', [DrugController::class, 'Store']);
        Route::patch('admin/drug/update/{id}', [DrugController::class, 'UpdateDrug']);
        Route::delete('admin/drug/delete/{id}', [DrugController::class, 'DeleteDrug']);
        
        Route::get('admin/order/get', [OperationController::class, 'GetOrders']);
        Route::get('admin/order/get/{id}', [OperationController::class, 'GetOrder']);
        Route::patch('admin/order/update/{id}', [OperationController::class, 'ChangeState']);
        
        Route::get('admin/tag/get',[TagController::class, 'GetTags']);
        Route::get('admin/category/get', [CategoryController::class, 'GetAllCategories']);

        Route::get('admin/drug/get/category/{id}',[DrugController::class,'GetDrugsByCategoryAdmin']);

        Route::put('admin/report/create/bestsellings', [ReportController::class,'CreateBestSellingsReport']);
        Route::put('admin/report/create/orders', [ReportController::class,'CreateOrdersByYear']);
    });

    Route::group(['middleware' => 'Pharmacy'], function () {

        Route::get('drug/get/bestsellings', [SalesController::class, 'GetBestSelling']);
        Route::get('drug/get/category/{id}', [DrugController::class, 'GetDrugsByCategory']);
        Route::get('drug/get/category/{aw}/tag/{id}', [TagController::class, 'GetDrugsByTag']);

        Route::get('category/get', [CategoryController::class, 'GetCategories']);

        Route::put('order/create', [OperationController::class, 'PlaceOrder']);
        Route::get('order/get/done', [OperationController::class, 'GetDone']);
        Route::get('order/get/undone', [OperationController::class, 'GetUnDone']);
        Route::get('order/get/{id}', [OperationController::class, 'GetUserOrder']);

        Route::post('favorite/add/{id}', [FavoriteController::class, 'AddToFavorite']);
        Route::post('favorite/delete/{id}', [FavoriteController::class, 'DeleteFromFavorite']);
        Route::get('favorite/get', [FavoriteController::class, 'GetAllFavoriteDrugs']);

        Route::get('notification/get', [NotificationsController::class, 'GetNotification']);

        Route::post('broadcasting/auth', function () {
            try{
                $pusher = new Pusher('1029384756', '123', '333');
            }catch(TypeError){
                return 'hi';
            }
            $channel = request()->channel_name . '.' . request()->user()->id;
            try {

                $token = json_decode($pusher->socket_auth($channel, request()->socket_id), false);
            } catch (Exception $e) {
                return response([
                    'Status' => 'Failed',
                    'Error' => 'please provide valid socket id and channel name.'
                ], 400);
            } catch (TypeError $e) {
                return response([
                    'Status' => 'Failed',
                    'Error' => 'please provide valid socket id and channel name.'
                ], 400);
            }
            return response([
                'Status' => 'Success',
                'Message' => 'User has been authenticated successfuly.',
                'Data' => [
                    'channel_name' => $channel,
                    'token' => $token->auth
                ]
            ], 200);
        });
    });
    Route::post('user/logout', [UserController::class, 'Logout']);

    Route::get('drug/get', [DrugController::class, 'GetDrugs']);
    Route::get('drug/get/{id}', [DrugController::class, 'GetDrug']);

    Route::get('search/drug', [SearchController::class, 'SearchDrugs']);
    Route::get('search/category', [SearchController::class, 'SearchCategory']);
});

Route::put('user/create', [UserController::class, 'Store']);

Route::post('user/login', [UserController::class, 'Login'])->middleware('Pharmacy');
Route::post('admin/user/login', [UserController::class, 'Login'])->middleware('Warhouse');

Route::post('user/recive_verification_code', [UserController::class, 'ReciveVerificationCode']);
