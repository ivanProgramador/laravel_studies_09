<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login():View
    {
       return view('auth.login');
    }

    
     public function authenticate()
    {
       //validação do formulario

       $credentials = request()->validate([
           'username' => 'required|min:3|max:30',
           'password' => 'required|min:8|max:32|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d@$!%*?&]{8,32}$/',
       ],
       [
           'username.required' => 'O campo usuário é obrigatório.',
           'username.min' => 'O campo usuário deve ter no mínimo :min caracteres',
             'username.max' => 'O campo usuário deve ter no máximo :max caracteres',
             'password.required' => 'O campo senha é obrigatório.',
             'password.min' => 'O campo senha deve ter no mínimo :min caracteres',
             'password.max' => 'O campo senha deve ter no máximo :max caracteres',  
             'password.regex' => 'A senha deve conter pelo menos uma letra maiúscula, uma letra minúscula e um número.',

       ]
            
      );
       
       //verificar se o usuário existe
       if(Auth::attempt($credentials)) {
           $request -> session()->regenerate();
             return redirect()->route('home');
            
       } 
       //verificar se a senha está correta

       //atualizar o ultimo login 

       //redirecionar para a página inicial 

      
    }

    

   
}
