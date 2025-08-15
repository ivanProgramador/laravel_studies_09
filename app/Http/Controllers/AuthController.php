<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login():View
    {
       return view('auth.login');
    }

    
     public function authenticate(Request $request)
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
       
       
       //não basta só está cadastrado
       // O usuario precisa estar ativo
       // A data de bloqueio precisa ser maior que a data atual ou ser nula    
       // a data de verificação de email precisa existir 
       // o delete_at precisa ser nulo para o sistema não deixar um usuário deletado acessar o sistema
       //isso são as regras gerais 

       $user = User::where('user_name',$credentials['username'])
                    ->where('active',true)
                    ->where(function($query){
                        $query->whereNull('blocked_until')
                              ->orWhere('blocked_until','<=',now());
                    })
                    ->whereNotNull('email_verified_at')
                    ->whereNull('deleted_at')   
                    ->first();
      
       //verificando se a variavel user trouxe algum cadsstro compativel com a consulta
       //se ele não trouxer o sistema alem de mandar o usuario de volta pra pagina de login manda um aviso
       //que sera mostrado na pagina explicando porque ele não conseguiu entrar  

       if(!$user){
          return back()->withInput()->with([
            'invalid_login' => 'Login Invalido !'
          ]);
       } 

      //verificar se a senha é valida 
      //O motivo de passar uma mensagem generica é porque se eu mando que o passawor está invalido
      //seja quem for qua estiver tentando acessar o aiatema vai saber que o login existe dentro da base
      //então nesse caso não é seguro confirmar isso para um usuario que não tem acesso   

      if(!password_verify($credentials['password'],$user->password)){
          return back()->withInput()->with([
            'invalid_login' => 'Login Invalido !'
          ]);
      }
       
    //registrar a data do login
    
    $user->last_login_at = now();

    //anular a data de bloqueio

    $user->blocked_until= null;

    //gravando os dados
    
    $user->save();

    //logando o usuario, no caso não basta so entrar na pagina 
    //eu tenho que renovar o token de acesso dele 
    
    $request->session()->regenerate();

    //passado os dados do usuario para o auth para ele pode liberar ass rotas poara usuarios 
    //autenticados

    Auth::login($user);

    //redirecionando 

    return redirect()->intended(route('home'));



      
    }

    

   
}
