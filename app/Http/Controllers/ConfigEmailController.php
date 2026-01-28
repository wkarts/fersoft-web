<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EmailConfig;
use App\Models\ConfigNota;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use Mail;

class ConfigEmailController extends Controller
{
    protected $empresa_id = null;
    public function __construct(){
        $this->middleware(function ($request, $next) {
            $this->empresa_id = $request->empresa_id;
            $value = session('user_logged');
            if(!$value){
                return redirect("/login");
            }
            return $next($request);
        });
    }

    public function index(){
        $config = EmailConfig::
        where('empresa_id', $this->empresa_id)
        ->first();

        $empresa = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        if($empresa == null){
            session()->flash('mensagem_erro', 'Configure o emitente');
            return redirect('/configNF');
        }

        $title = 'Configurar Email';
        return view('email_config/index', compact('config', 'title', 'empresa'));
    }
    public function save(Request $request){
        $this->_validate($request);

        $config = EmailConfig::
        where('empresa_id', $this->empresa_id)
        ->first();

        $request->merge([
            'smtp_auth' => $request->smtp_auth ? true : false,
            'smtp_debug' => $request->smtp_debug ? true : false,
        ]);

        try{
            if($config == null){
                EmailConfig::create($request->all());
            }else{
                $config->fill($request->all())->save();
            }

            session()->flash("mensagem_sucesso", "Configurado com sucesso!");

        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Aldo deu errado: ' . $e->getMessage());
        }

        return redirect()->back();
    }

    private function _validate(Request $request){
        $rules = [
            'nome' => 'required|max:50',
            'host' => 'required|max:50',
            'email' => 'required|max:50',
            'senha' => 'required|max:50',
            'porta' => 'required|max:10',

        ];

        $messages = [
            'nome.required' => 'Campo obrigatório.',
            'nome.max' => '50 caracteres maximos permitidos.',
            'host.required' => 'Campo obrigatório.',
            'host.max' => '50 caracteres maximos permitidos.',
            'email.required' => 'Campo obrigatório.',
            'email.max' => '50 caracteres maximos permitidos.',
            'senha.required' => 'Campo obrigatório.',
            'senha.max' => '50 caracteres maximos permitidos.',
            'porta.required' => 'Campo obrigatório.',
            'porta.max' => '10 caracteres maximos permitidos.'
        ];

        $this->validate($request, $rules, $messages);
    }

    public function teste(Request $request){
        $empresa = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        $email = $request->email;



        $emailConfig = EmailConfig::
        where('empresa_id', $this->empresa_id)
        ->first();

        if($emailConfig == null){
            session()->flash('mensagem_erro', 'Primeiramente configure o email!');
            return redirect()->back();
        }

        $mail = new PHPMailer(true);

        try {
            if($emailConfig->smtp_debug){
                $mail->SMTPDebug = SMTP::DEBUG_SERVER;   
            }                   
            $mail->isSMTP();                                            
            $mail->Host = $emailConfig->host;                     
            $mail->SMTPAuth = $emailConfig->smtp_auth;                                   
            $mail->Username = $emailConfig->email;                     
            $mail->Password = $emailConfig->senha;                               
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            
            $mail->Port = $emailConfig->porta; 

            $mail->setFrom($emailConfig->email, $emailConfig->nome); 
            $mail->addAddress($email); 

            $mail->isHTML(true);  
            $mail->Subject = 'Teste';
            $mail->Body = 'Email de teste ' . env("APP_NAME");
            $mail->send();
            
            session()->flash('mensagem_sucesso', 'Email enviado');

        } catch (Exception $e) {
            session()->flash('mensagem_erro', 'Aldo deu errado: ' . $mail->ErrorInfo);
        }
        return redirect()->back();

    }
}
