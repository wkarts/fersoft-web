<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\TicketMensagem;

class TicketSuperController extends Controller
{
	public function index(){
		$tickets = Ticket::
		orderBy('estado')
		->orderBy('id')
		->get();

		return view('tickets_super/list')
		->with('tickets', $tickets)
		->with('title', 'Tickets');
	}

	public function filtro(Request $request){
		$tickets = Ticket::
		orderBy('estado')
		->select('tickets.*')
		->orderBy('id');

		if($request->empresa){
			$tickets->join('empresas', 'empresas.id', '=', 'tickets.empresa_id');
			$tickets->where('empresas.nome', 'like', '%'.$request->empresa.'%');
		}

		if($request->estado){
			$tickets->where('tickets.estado', $request->estado);
		}

		if($request->departamento){
			$tickets->where('tickets.departamento', $request->departamento);
		}

		$tickets = $tickets->get();

		return view('tickets_super/list')
		->with('tickets', $tickets)
		->with('empresa', $request->empresa)
		->with('estado', $request->estado)
		->with('departamento', $request->departamento)
		->with('title', 'Tickets');
	}

	public function view($id){
		$ticket = Ticket::findOrFail($id);
		return view('tickets_super/view')
		->with('ticket', $ticket)
		->with('contratoJs', true)
		->with('title', 'Ticket TCK-'.$ticket->id);
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
			
			$ticket->estado = 'respondida';
			$ticket->save();

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
		return redirect('/ticketsSuper/view/'.$request->ticket_id);
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

		return redirect('/ticketsSuper/view/'.$request->ticket_id);
	}

	public function finalizar($id){
		$ticket = Ticket::findOrFail($id);

		return view('tickets_super/finalizar')
		->with('ticket', $ticket)
		->with('title', 'Finalizar TCK-'.$ticket->id);
		
	}
}
