<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class FilialScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->adm) return;

            // 1. Garante que locais seja um array
            $locais = $user->locais;
            if (is_string($locais)) {
                $locais = json_decode($locais, true) ?: [];
            }

            // 2. Cria uma "gaiola" de parênteses no SQL para o filtro não vazar
            $builder->where(function ($query) use ($locais, $user) {
                
                if (is_array($locais) && !empty($locais)) {
                    $query->where(function ($q) use ($locais) {
                        // Filtra pelas filiais numéricas (Ex: 4)
                        $fisicas = array_filter($locais, fn($v) => $v != "-1" && $v != "");
                        if (!empty($fisicas)) {
                            $q->whereIn('filial_id', $fisicas);
                        }

                        // Se tiver Matriz (-1) na lista, permite ver os nulos
                        if (in_array("-1", $locais) || in_array(-1, $locais)) {
                            $q->orWhereNull('filial_id')
                              ->orWhere('filial_id', '<=', 0);
                        }
                    });
                } else {
                    // Se não tem lista, usa o local_padrao de forma protegida
                    if ($user->local_padrao == "-1") {
                        $query->whereNull('filial_id')->orWhere('filial_id', '<=', 0);
                    } else {
                        $query->where('filial_id', $user->local_padrao);
                    }
                }
            });
        }
    }
}