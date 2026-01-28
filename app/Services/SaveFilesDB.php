<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SaveFilesDB
{
    /**
     * Salva uma imagem no diretório especificado e armazena o nome no campo especificado do modelo.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $directory Caminho completo do diretório para salvar a imagem
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string $field Nome do campo onde será armazenado o nome do arquivo
     * @return string Nome do arquivo salvo
     */
    public function SaveFile($file, $directory, $model, $field)
    {
        // Gera um nome único para a nova imagem com a extensão original
        $filename = Str::random(25) . '.' . $file->getClientOriginalExtension();

        // Exclui a imagem antiga, se existir
        if ($model->$field && file_exists($directory . '/' . $model->$field)) {
            unlink($directory . '/' . $model->$field);
        }

        // Move o arquivo para o diretório especificado
        $file->move($directory, $filename);

        // Atualiza o campo do modelo com o novo nome de arquivo
        $model->$field = $filename;
        $model->save();

        return $filename;
    }
}
