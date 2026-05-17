<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;

class SecurityPlaceholderController extends Controller
{
    public function page(string $title)
    {
        return view('security.placeholder', [
            'title' => $title,
            'message' => 'Esta tela faz parte da base estrutural da Segurança de Operações e será implementada na próxima etapa sem bloquear a aplicação atual.',
        ]);
    }

    public function permissions() { return $this->page('Permissões CRUD'); }
    public function protections() { return $this->page('Proteções de Operação'); }
    public function authorizers() { return $this->page('Autorizadores'); }
    public function tokens() { return $this->page('Tokens de Liberação'); }
    public function exportJson() { return $this->page('Exportação JSON de Logs'); }
    public function restoreAudit() { return $this->page('Restauração por Auditoria'); }
    public function companies() { return redirect('/seguranca/admin/painel-global'); }
}
