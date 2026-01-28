<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\Usuario;
use App\Models\TicketMensagem;
use Illuminate\Support\Str;

class TicketController extends Controller
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
		$tickets = Ticket::
		where('empresa_id', $this->empresa_id)
		->orderBy('created_at', 'desc')
		->get();

		return view('tickets/list')
		->with('tickets', $tickets)
		->with('title', 'Tickets');
	}

	public function new(){
		return view('tickets/register')
		->with('contratoJs', true)
		->with('title', 'Novo Ticket');
	}

	public function save(Request $request){
		$this->_validate($request);
		try{
			$data = [
				'empresa_id' => $this->empresa_id,
				'estado' => 'aberto',
				'departamento' => $request->departamento, 
				'assunto' => $request->assunto,
				'mensagem_finalizar' => ''
			];

			$ticket = Ticket::create($data);

			$fileName = "";
			if($request->hasFile('file')){
				$file = $request->file('file');

				$extensao = $file->getClientOriginalExtension();
				$fileName = Str::random(25) . ".".$extensao;
				$file->move(public_path('ticket_img'), $fileName);

			}
			$data = [
				'mensagem' => $request->mensagem,
				'imagem' => $fileName,
				'ticket_id' => $ticket->id,
				'usuario_id' => get_id_user()
			];
			TicketMensagem::create($data);

			session()->flash('mensagem_sucesso', 'Ticket criado, aguarde nosso suporte!');
		}catch(\Exception $e){
			session()->flash('mensagem_erro', 'Algo deu errado!');
		}
		return redirect('/tickets');
	}

	private function _validate(Request $request){
		$rules = [
			'departamento' => 'required',
			'assunto' => 'required|max:100',
			'mensagem' => 'required|min:10',
			'file' => 'max:700',
		];

		$messages = [
			'departamento.required' => 'O campo departamento é obrigatório.',
			'assunto.required' => 'O campo nome é obrigatório.',
			'assunto.max' => 'Máximo de 100 caracteres.',
			'mensagem.required' => 'O campo mensagem é obrigatório.',
			'mensagem.min' => 'Mínimo de 10 caracteres.',
			'file.max' => 'Arquivo muito grande maximo 300 Kb',
		];

		$this->validate($request, $rules, $messages);
	}

	public function view($id){
		$ticket = Ticket::findOrFail($id);
		if(valida_objeto($ticket)){
			return view('tickets/view')
			->with('ticket', $ticket)
			->with('contratoJs', true)
			->with('title', 'Ticket TCK-'.$ticket->id);
		}else{
			return redirect('/403');
		}
	}

	public function finalizar($id){
		$ticket = Ticket::findOrFail($id);
		if(valida_objeto($ticket)){
			return view('tickets/finalizar')
			->with('ticket', $ticket)
			->with('title', 'Finalizar TCK-'.$ticket->id);
		}else{
			return redirect('/403');
		}
	}

	public function novaMensagem(Request $request){
		$this->_validate2($request);
		try{

			$ticket = Ticket::findOrFail($request->ticket_id);

			$fileName = "";
			if($request->hasFile('file')){
				$file = $request->file('file');

				$extensao = $file->getClientOriginalExtension();
				$fileName = Str::random(25) . ".".$extensao;
				$file->move(public_path('ticket_img'), $fileName);
			}
			$usuario = Usuario::find(get_id_user());

			if(isSuper($usuario->login)){
				$ticket->estado = 'respondida';
				$ticket->save();
			}

			$data = [
				'mensagem' => $request->mensagem,
				'imagem' => $fileName,
				'ticket_id' => $ticket->id,
				'usuario_id' => get_id_user()
			];
			TicketMensagem::create($data);

			session()->flash('mensagem_sucesso', 'Mensagem adicionada ao ticket!');
		}catch(\Exception $e){
			session()->flash('mensagem_erro', 'Algo deu errado!');
		}
		return redirect('/tickets/view/'.$request->ticket_id);
	}

	private function _validate2(Request $request){
		$rules = [
			'mensagem' => 'required|min:10',
			'file' => 'max:700',
		];

		$messages = [
			'mensagem.required' => 'O campo mensagem é obrigatório.',
			'mensagem.min' => 'Mínimo de 10 caracteres.',
			'file.max' => 'Arquivo muito grande maximo 300 Kb',
		];

		$this->validate($request, $rules, $messages);
	}

	public function finalizarPost(Request $request){
		$ticket = Ticket::findOrFail($request->ticket_id);
		$ticket->mensagem_finalizar = $request->mensagem;
		$ticket->estado = 'finalizado';
		$ticket->save();
		session()->flash('mensagem_sucesso', 'Ticket finalizado!');

		return redirect('/tickets/view/'.$request->ticket_id);
	}
}
