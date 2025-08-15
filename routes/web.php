<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

//grupo de rotas para usuarios não autenticados
Route::middleware('guest')->group(function(){
     // Rota para a página de

     Route::get('/login',[AuthController::class,'login'])->name('login');
     Route::post('/login',[AuthController::class,'authenticate'])->name('authenticate');
});


//grupo de rotas para usuarios autenticados
//quando o midleware 'auth' , ele vai testar se existe uma cessão preenchida se não tiver ele vai mandar 
//o usuario de volta para a página de login


Route::middleware('auth')->group(function(){
    
    Route::get('/', function () {
       echo 'ola mundo';
    })->name('home');

    Route::get('/logout',[AuthController::class,'logout'])->name('logout');

});



