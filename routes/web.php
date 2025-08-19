<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MainController;
use Illuminate\Support\Facades\Route;

//grupo de rotas para usuarios não autenticados
Route::middleware('guest')->group(function(){

     // Rota para a página do formulario de login
     Route::get('/login',[AuthController::class,'login'])->name('login');

     //rota para fazer os login dentro do sistema 
     Route::post('/login',[AuthController::class,'authenticate'])->name('authenticate');

     //rota para o usuario se cadastrar, para que ele possa acessar
     //se essa rota receber um get ela retorna o formulario 
     //se receber um post ela grava os dados do usuario
        
     Route::get('/register',[AuthController::class,'register'])->name('register');

     Route::post('/register',[AuthController::class,'store_user'])->name('store_user');

     //confimação de novo usuario essa rota sera ativada de dentro do emial do novo usuario

     Route::get('/new_user_confirmation/{token}',[AuthController::class,'new_user_confirmation'])->name('new_user_confirmation');


});


//grupo de rotas para usuarios autenticados
//quando o midleware 'auth' , ele vai testar se existe uma cessão preenchida se não tiver ele vai mandar 
//o usuario de volta para a página de login


Route::middleware('auth')->group(function(){
    
    Route::get('/', [MainController::class,'home'])->name('home');

    Route::get('/logout',[AuthController::class,'logout'])->name('logout');

    Route::get('/profile',[AuthController::class,'profile'])->name('profile');

    Route::post('/profile',[AuthController::class,'change_password'])->name('change_password');

    




});



