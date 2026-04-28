<?php

//namespace App;
namespace App\Models;



class App extends BaseModel
{
    protected $fillable = [
        'value', 'client_id'
    ];

    public function clientJuck(){
        return $this->belongsTo(ClientJuck::class, 'client_id');
    }
}
