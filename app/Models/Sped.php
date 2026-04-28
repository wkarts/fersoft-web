<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sped extends BaseModel
{
    use HasFactory;

    public static function motivosInventario(){
        return [
            '' => 'Selecione',
            '01' =>'No final no período',
            '02' =>'Na mudança de forma de tributação da mercadoria (ICMS)',
            '03' =>'Na solicitação da baixa cadastral, paralisação temporária e outras situações',
            '04' =>'Na alteração de regime de pagamento – condição do contribuinte',
            '05' =>'Por determinação dos fiscos.',
        ];
    }
}
