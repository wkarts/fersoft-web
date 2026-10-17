<?php

namespace App\Services;

use App\Models\Usuario;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class UserAvatarService
{
    /**
     * The existing mobile application reads usuarios.img from foto_usuario.
     */
    public function store(Usuario $usuario, UploadedFile $file): string
    {
        $directory = public_path('foto_usuario');
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new \RuntimeException('Não foi possível preparar a pasta da foto.');
        }

        $filename = Str::uuid()->toString() . '.' . $file->extension();
        $previous = (string) $usuario->img;
        $file->move($directory, $filename);

        try {
            $usuario->img = $filename;
            if (!$usuario->save()) {
                throw new \RuntimeException('Não foi possível salvar a foto do usuário.');
            }
        } catch (\Throwable $e) {
            @unlink($directory . DIRECTORY_SEPARATOR . $filename);
            $usuario->img = $previous;
            throw $e;
        }

        // Só descarta a foto anterior depois de persistir a substituta.
        if ($previous !== 'user.png' && $this->safeFilename($previous)) {
            $oldPath = $directory . DIRECTORY_SEPARATOR . $previous;
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

        return $filename;
    }

    public function url(?string $filename): ?string
    {
        if (!$this->safeFilename((string) $filename)) {
            return null;
        }

        return is_file(public_path('foto_usuario/' . $filename))
            ? asset('foto_usuario/' . rawurlencode($filename))
            : null;
    }

    /**
     * Existing sessions predate the photo field; hydrate just once after deploy.
     */
    public function currentUserUrl(): ?string
    {
        $logged = session('user_logged');
        if (!is_array($logged) || !isset($logged['id'], $logged['empresa'])) {
            return null;
        }
        if (!array_key_exists('img', $logged)) {
            $usuario = Usuario::whereKey($logged['id'])
                ->where('empresa_id', $logged['empresa'])->first();
            $logged['img'] = $usuario ? (string) $usuario->img : '';
            session()->put('user_logged.img', $logged['img']);
        }

        return $this->url((string) $logged['img']);
    }

    private function safeFilename(string $filename): bool
    {
        return (bool) preg_match('/\\A[A-Za-z0-9_-]+\\.(?:jpe?g|png|webp)\\z/iD', $filename);
    }
}
