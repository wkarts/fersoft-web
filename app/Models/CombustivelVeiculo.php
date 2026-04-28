<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CombustivelVeiculo extends BaseModel
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

    protected $table = 'combustivel_veiculo';

    protected $fillable = [
        'descricao', 'ativo', 'empresa_id'
    ];

    public $timestamps = false;
}
