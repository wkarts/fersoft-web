<?php

namespace App\Http\Controllers\Examples;

use App\Http\Controllers\Concerns\UsesSecurityOperationContext;
use App\Http\Controllers\Controller;
use App\Models\Produto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

/**
 * EXEMPLO DE IMPLEMENTAÇÃO PARA CONTROLLERS QUE NÃO HERDAM BaseController.
 *
 * Este controller NÃO está registrado nas rotas por padrão.
 * Ele serve como modelo seguro para adaptar controllers legados/personalizados
 * que estendem diretamente App\Http\Controllers\Controller.
 *
 * Model usada no exemplo: App\Models\Produto.
 *
 * Pontos importantes:
 * - Usa UsesSecurityOperationContext para publicar contexto ao layout.
 * - Usa assertSecurityOperation() antes de create/edit/delete/restore.
 * - O modal global de autorização continua sendo renderizado pelo layout.
 * - Nenhum HTML de modal precisa ser incluído nas views deste controller.
 */
class SecurityNonBaseCrudExampleController extends Controller
{
    use UsesSecurityOperationContext;

    protected string $modelClass = Produto::class;
    protected string $basePath = '/exemplo-seguranca-produtos';

    /**
     * Listagem: ação view.
     */
    public function index(Request $request)
    {
        $this->shareSecurityOperationContext($this->modelClass, null, 'list', $this->basePath);
        $this->assertSecurityOperation($request, $this->modelClass, 'view');

        $produtos = Produto::query()
            ->where('empresa_id', $this->empresaId($request))
            ->orderByDesc('id')
            ->paginate(20);

        return view('examples.security_non_base.index', compact('produtos'));
    }

    /**
     * Tela de novo cadastro: ação create.
     */
    public function create(Request $request)
    {
        $this->shareSecurityOperationContext($this->modelClass, null, 'create', $this->basePath);
        $this->assertSecurityOperation($request, $this->modelClass, 'create');

        $produto = new Produto();

        return view('examples.security_non_base.form', compact('produto'));
    }

    /**
     * Grava novo registro: ação create.
     */
    public function store(Request $request)
    {
        $this->assertSecurityOperation($request, $this->modelClass, 'create');

        $data = $this->validatedData($request);
        $data['empresa_id'] = $this->empresaId($request);

        Produto::create($data);

        Session::flash('mensagem_sucesso', 'Registro criado com sucesso.');

        return redirect($this->basePath);
    }

    /**
     * Tela de edição: ação edit.
     */
    public function edit(Request $request, int $id)
    {
        $this->shareSecurityOperationContext($this->modelClass, $id, 'edit', $this->basePath);
        $this->assertSecurityOperation($request, $this->modelClass, 'edit', $id);

        $produto = $this->findProduto($request, $id);

        return view('examples.security_non_base.form', compact('produto'));
    }

    /**
     * Atualiza registro: ação edit.
     */
    public function update(Request $request, int $id)
    {
        $this->assertSecurityOperation($request, $this->modelClass, 'edit', $id);

        $produto = $this->findProduto($request, $id);
        $produto->update($this->validatedData($request));

        Session::flash('mensagem_sucesso', 'Registro atualizado com sucesso.');

        return redirect($this->basePath);
    }

    /**
     * Exclui registro: ação delete.
     */
    public function delete(Request $request, int $id)
    {
        $this->assertSecurityOperation($request, $this->modelClass, 'delete', $id);

        $produto = $this->findProduto($request, $id);
        $produto->delete();

        Session::flash('mensagem_sucesso', 'Registro excluído com sucesso.');

        return redirect($this->basePath);
    }

    /**
     * Restaura registro: ação restore.
     */
    public function restore(Request $request, int $id)
    {
        $this->assertSecurityOperation($request, $this->modelClass, 'restore', $id);

        $produto = Produto::query()
            ->where('empresa_id', $this->empresaId($request))
            ->withDeleted()
            ->where('id', $id)
            ->firstOrFail();

        if (method_exists($produto, 'restoreSmart')) {
            $produto->restoreSmart();
        }

        Session::flash('mensagem_sucesso', 'Registro restaurado com sucesso.');

        return redirect($this->basePath);
    }

    protected function findProduto(Request $request, int $id): Produto
    {
        return Produto::query()
            ->where('empresa_id', $this->empresaId($request))
            ->where('id', $id)
            ->firstOrFail();
    }

    protected function empresaId(Request $request): ?int
    {
        $empresaId = $request->input('empresa_id') ?: session('user_logged.empresa');

        return is_numeric($empresaId) ? (int) $empresaId : null;
    }

    protected function validatedData(Request $request): array
    {
        /**
         * Ajuste os campos conforme o controller real.
         * Este exemplo usa campos comuns do cadastro de Produto, mas é apenas modelo.
         */
        return $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'valor_venda' => ['nullable'],
        ]);
    }
}
