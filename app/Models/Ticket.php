<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ticket extends BaseModel
{
	use HasFactory;

	protected $fillable = [
		'empresa_id', 'estado', 'departamento', 'assunto', 'mensagem_finalizar'
	];

	public static function departamentos(){
		return [
			'Suporte',
			'Conta e Vendas'
		];
	}

	public function empresa(){
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

	public function mensagens(){
		return $this->hasMany('App\Models\TicketMensagem', 'ticket_id', 'id')
		->orderBy('id', 'desc');
	} 
}
