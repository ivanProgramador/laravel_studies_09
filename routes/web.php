<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

//grupo de rotas para usuarios não autenticados
Route::middleware('guest')->group(function(){
     // Rota para a página inicial

     Route::get('/login',[AuthController::class,'login'])->name('login');

     });
