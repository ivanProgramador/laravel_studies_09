<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\NewUserConfirmation;
use Carbon\Carbon;

use function Laravel\Prompts\password;

class AuthController extends Controller
{
    public function login():View
    {
       return view('auth.login');
    }

    public function authenticate(Request $request):RedirectResponse
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

       ]);
       
       
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

  public function logout():RedirectResponse
    {
       //logout é uma função que ja existe na classe Auth então eu não preciso coloacar uma codigo mais complexo pra fazer isso 
       Auth::logout();
       return redirect()->route('login'); 
    }


   public function register():View
   {
      return view('auth.register');
   }

   public function store_user(Request $request)
   {

      $request->validate([
          'username' => 'required|string|min:3|max:30|unique:users,user_name',
          'email' => 'required|email|max:255|unique:users,email',
          'password' => [
          'required',
          'string',
          'min:4',
          'max:32',
          'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d@$!%*?&]{8,32}$/',
          ],
      ], [
          'name.required' => 'O campo nome é obrigatório.',
          'name.max' => 'O campo nome deve ter no máximo :max caracteres.',
          'username.required' => 'O campo usuário é obrigatório.',
          'username.min' => 'O campo usuário deve ter no mínimo :min caracteres.',
          'username.max' => 'O campo usuário deve ter no máximo :max caracteres.',
          'username.unique' => 'Este nome de usuário já está em uso.',
          'email.required' => 'O campo email é obrigatório.',
          'email.email' => 'Informe um email válido.',
          'email.max' => 'O campo email deve ter no máximo :max caracteres.',
          'email.unique' => 'Este email já está em uso. Só pode haver um email associado a uma conta.',
          'password.required' => 'O campo senha é obrigatório.',
          'password.min' => 'A senha deve ter no mínimo :min caracteres.',
          'password.max' => 'A senha deve ter no máximo :max caracteres.',
          'password.regex' => 'A senha deve conter pelo menos uma letra maiúscula, uma letra minúscula e um número.',
          'password.confirmed' => 'As senhas não estão iguais',
      ]);

      
      //definindo um novov usuario criando um topken de email
      
      $user = new User();
      $user->user_name = $request->username;
      $user->email = $request->email;
      $user->password = bcrypt($request->password);
      $user->token = Str::random(64); 

      //gerando o link de confirmação
      
      $confirmation_link = route("new_user_confirmation",['token' => $user->token]);

      //enviando o email
      $result = Mail::to($user->email)->send(new NewUserConfirmation($user->username, $confirmation_link));
      
      //testando se deu certo enviar o email
      
      if(!$result){
         return back()->withInput()->with([
            'server_error'=>'Ocorreu um erro ao enviar o emial de confirmação'
         ]);
      }

      //criando o usuario na base
      
      $user->save();

      //mostrar a view informando que o email foi enviado com sucesso
      
      return view('auth.email_sent',['email'=>$user->email]);

       


   } 


   public function new_user_confirmation($token)
   {
      //verificando se o token recebido é valido
      //aqui eu faço um select que busca por um token igual esse 
      //dentro da base de dados porque sele conseguiu cehgar na fase de confirmar o amail
      //o cadastro existe

      $user = User::where('token',$token)->first(); 
      
       //se não existir não vem nenhum usuario e eo cliente vai voltar parar o login 

       if(!$user){
           return redirect()->route('login');
       }

       //se sim eu tenho que preencehr a data/hora de verificação
       
       $user->email_verified_at = Carbon::now(); 

       //e apagar o toekn que ele usou pra confirmar 
       
       $user->token = null;

       //ativar o usuario 

       $user->active = true;
       
       //salvando o usuario com as informações atualizadas
       
       $user->save();

       //logando o usuario no sistema 

       Auth::login($user);

       //view de boas vindas 

       return view('auth.new_user_confirmation');


           
   }


   public function profile():view
   {

      return view('auth.profile');
       
   }

   public function change_password(Request $request ){

        $request->validate([
        'current_password' => 'required',
        'new_password' => [
            'required',
            'string',
            'min:8',
            'max:32',
            'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d@$!%*?&]{8,32}$/',
            'confirmed',
            'different:current_password',
        ],
    ], [
        'current_password.required' => 'Informe sua senha atual.',
        'new_password.required' => 'Informe a nova senha.',
        'new_password.min' => 'A nova senha deve ter no mínimo :min caracteres.',
        'new_password.max' => 'A nova senha deve ter no máximo :max caracteres.',
        'new_password.regex' => 'A nova senha deve conter letras maiúsculas, minúsculas e números.',
        'new_password.confirmed' => 'A confirmação da nova senha não confere.',
        'new_password.different' => 'A nova senha deve ser diferente da atual.',
    ]);

    //testando se a senha atual esta correta
    //o susurio esta logado e gerou uma sessão por issso não preciso consultar a base
    //pra testar a senha atual  
    
    if(!password_verify($request->current_password,Auth::user()->password)){
      return back()->with([
         'server_error'=>'A senha atual está errada'
      ]);
   }

   //agora qque ele passou pela validação e a senha atual esta certa
   //eu vou atualizar a senha dele
   
   $user = User::find(Auth::id());
   $user->password = bcrypt($request->new_password);
   $user->save();

   //ataualizar a senha na sessão porque se o usuarrio queiser alterara denovo enquanto esta logado 
   //o profile vai diezer qua senha esta errada 


   Auth::user()->password = $request->new_password;

   

   //avisando aoa usuario que a senha foi alterada com sucesso 

   return redirect()->route('profile')->with([
      'success'=>'A senha foi ataualizada com sucesso !'
   ]);




   
   


    







       
   }




    

   
  

}






