<?php
/** CI-only rendering fixtures from the actual Blade files; no production route is installed. */
declare(strict_types=1);
require dirname(__DIR__, 2) . '/vendor/autoload.php';
$app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (!$app->environment('testing')) throw new RuntimeException('UI fixtures must run only under APP_ENV=testing.');
if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
    throw new RuntimeException('Fixture database must be isolated SQLite in memory.');
}
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Database\Schema\Blueprint;
use App\Support\UiThemePalette;

// Remove database-bound production view composers ONLY in this testing process.
$app['events']->forget('composing: *');
config(['app.url' => 'http://127.0.0.1:8765', 'app.name' => 'ERP de teste', 'ui_theme.active' => true, 'ui_theme.name' => 'refined', 'login_presentation.page' => 'access']);
URL::forceRootUrl('http://127.0.0.1:8765');
$app['session']->start();
$app['request']->setLaravelSession($app['session']->driver());
session(['user_logged' => ['id' => 1, 'empresa' => 1, 'nome' => 'Operador de teste', 'empresa_nome' => 'EMPRESA DE TESTE', 'ambiente' => 'Homologação', 'super' => 0, 'tipo_representante' => 0], 'alerta_tarefas_visto' => true]);
Schema::create('filials', function (Blueprint $table) { $table->increments('id'); $table->integer('empresa_id'); $table->integer('status'); $table->string('descricao'); $table->timestamps(); });
Schema::create('usuarios', function (Blueprint $table) { $table->increments('id'); $table->integer('empresa_id'); $table->text('locais'); $table->integer('local_padrao'); $table->timestamps(); });
DB::table('usuarios')->insert(['id'=>1,'empresa_id'=>1,'locais'=>'["-1","2"]','local_padrao'=>-1]);
DB::table('filials')->insert(['id'=>2,'empresa_id'=>1,'status'=>1,'descricao'=>'Filial de teste']);
// Real menu helper, real permission filtering, test-only in-memory rows.
Schema::create('tributacaos', function (Blueprint $t) { $t->increments('id'); $t->integer('empresa_id'); $t->integer('regime')->default(1); });
Schema::create('empresas', function (Blueprint $t) { $t->increments('id'); $t->string('nome'); $t->text('permissao')->nullable(); });
Schema::create('planos', function (Blueprint $t) { $t->increments('id'); $t->string('nome'); });
Schema::create('plano_empresas', function (Blueprint $t) { $t->increments('id'); $t->integer('empresa_id'); $t->integer('plano_id'); });
DB::table('tributacaos')->insert(['id'=>1,'empresa_id'=>1,'regime'=>1]);
DB::table('planos')->insert(['id'=>1,'nome'=>'Plano de teste']);
DB::table('plano_empresas')->insert(['id'=>1,'empresa_id'=>1,'plano_id'=>1]);
$fixtureAllowedTitles=['Cadastros','ClassTrib','e-DOC','Atendimento','Documentos Eletrônicos','Entradas','Gestão Pessoal','Controle de Ponto','Estoque','Kardex (Beta)','Vendas','Contratos','MTR 3.0','Emissão de NFSe','Vendas Balcão','Financeiro'];
$fixturePermissions=[];
foreach ((new \App\Helpers\Menu())->getMenu() as $group) {
    if (in_array($group['titulo'], $fixtureAllowedTitles, true)) {
        foreach ($group['subs'] as $link) if (!empty($link['rota'])) $fixturePermissions[]=$link['rota'];
    }
}
DB::table('empresas')->insert(['id'=>1,'nome'=>'EMPRESA DE TESTE','permissao'=>json_encode($fixturePermissions)]);

$base = [
    'title'=>'Dashboard de teste','tema'=>1,'tema_menu'=>1,'logo'=>'','alertas'=>[], 'rotaAtiva'=>'Financeiro','uri'=>'/contasPagar','configCatraca'=>null,'deliveryConfig'=>false,'ifoodConfig'=>false,
    'alerta_sonoro'=>'','tipoMenu'=>'lateral','armazenamento'=>0,'totalParaArmazenar'=>0,'percentualArmazenamento'=>0,'video_url'=>'','ultimoAcesso'=>(object)['created_at'=>'2026-10-14 17:14:47','ip_address'=>'192.0.2.10'],
    'upgrade'=>false,'cor'=>'','casasDecimais'=>2,'casasDecimaisQtd'=>3,'contrato'=>1,
    'appVersionCurrent'=>(object)['version'=>'0.0.0-beta.TEST','commit_hash'=>'fixture'],'appVersionHistory'=>collect(),'appVersionHistoryGrouped'=>collect(),
    'totalClientes'=>43,'totalProdutos'=>156,'totalFaturamento'=>12.60,'ticketMedio'=>12.60,'custoEstoque'=>6479876.12,
    'diasDesc'=>'Últimos 30 dias','periodo'=>'30','filial_id'=>'matriz','localPadraoAtual'=>'matriz','filiais'=>collect([(object)['id'=>2,'nome'=>'Filial de teste']]),
    'produtosMaisVendidos'=>collect(),'topClientes'=>collect(),
    'produtosComEstoque'=>collect([(object)['nome'=>'AVENTAL DESCARTÁVEL MANGA LONGA','quantidade'=>70049,'unidade_venda'=>'UN','valor_venda'=>.04],(object)['nome'=>'SUCATA DE ALUMÍNIO','quantidade'=>31600,'unidade_venda'=>'KG','valor_venda'=>11.84]]),
    'produtosAlerta'=>collect([(object)['nome'=>'PRODUTO TESTE 2','quantidade'=>0,'unidade_venda'=>'UN']]),
    'pagarVencidas'=>122.35,'pagarNoPeriodo'=>1450.10,'receberVencidas'=>99.90,'receberNoPeriodo'=>1876.20,'saldosContas'=>collect([(object)['nome'=>'Conta principal','saldo'=>1234.56]]),
];
// Reuse the application's actual clock implementation, supplied by default.layout in production.
$legacyLayout = file_get_contents(resource_path('views/default/layout.blade.php'));
$clockStart = strpos($legacyLayout, '<script>' . PHP_EOL . '    window.SERVER_OFFSET_MIN');
$clockEnd = strpos($legacyLayout, '</script>', $clockStart);
$clock = substr($legacyLayout, $clockStart, $clockEnd + strlen('</script>') - $clockStart);
$clock = str_replace('{{ Carbon::now()->getOffset() / 60 }}', (string) (\Carbon\Carbon::now()->getOffset() / 60), $clock);
$target = storage_path('app/ui-browser');
if (!is_dir($target)) mkdir($target, 0775, true);
// Prove every render exists; the legacy exception handler can exit CLI with zero.
register_shutdown_function(static function () use ($target): void {
    if (count(glob($target . '/fer-*.html')) !== 12) {
        fwrite(STDERR, 'Rendering incomplete: expected 12 actual Blade fixtures.' . PHP_EOL);
        exit(1);
    }
});
file_put_contents($target . '/native-compiled.php', $app['blade.compiler']->compileString(file_get_contents(resource_path('views/themes/refined/layout.blade.php'))));
$palettes=config('ui_theme.presets');
foreach ($palettes as $slug=>$definition) {
    foreach (['lateral','superior'] as $mode) {
        foreach (['comfortable','compact'] as $density) {
            $theme=array_merge($definition,['slug'=>$slug,'active'=>true,'density'=>$density,'color_mode'=>'follow']);
            $app['request']->attributes->set('fersoft.ui_theme.1:1:0:light',$theme);
            $data=array_merge($base,['tipoMenu'=>$mode]);
            $factory=$app['view'];
            $content=$factory->make('themes.refined.dashboard',$data)->render();
            // Test Bootstrap modal/Select2/KTDatatable under the SAME production shell and bundles.
            $content .= '<section class="ui-panel" id="fixture-legacy"><div class="ui-panel-body"><h2>Componentes operacionais de teste</h2><button type="button" class="btn btn-primary" data-toggle="modal" data-target="#fixture-modal">Abrir modal de teste</button><select id="fixture-select" class="form-control"><option value="1">Item de teste</option><option value="2">Outro item</option></select><table id="fixture-grid" class="table"><thead><tr><th>Produto</th><th>Quantidade</th></tr></thead><tbody><tr><td>Produto de teste</td><td>2</td></tr></tbody></table></div></section><div class="modal fade" id="fixture-modal" tabindex="-1" role="dialog" aria-labelledby="fixture-modal-title"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h2 class="modal-title" id="fixture-modal-title">Modal de teste</h2><button type="button" data-dismiss="modal" class="close" aria-label="Fechar modal">×</button></div><div class="modal-body"><label>Nome<input class="form-control" value="Preservado" id="fixture-name"></label></div></div></div></div>';
            $factory->startSection('content', new \Illuminate\Support\HtmlString($content));
            $html=$factory->make('themes.refined.layout',$data)->render();
            $html=str_replace('</body>', $clock . '<script>window.addEventListener("DOMContentLoaded",function(){jQuery("#fixture-select").select2({width:"100%"});jQuery("#fixture-grid").KTDatatable({data:{saveState:false},layout:{scroll:false,footer:false}});});</script></body>', $html);
            file_put_contents($target.'/'.$slug.'-'.$mode.'-'.$density.'.html',$html);
        }
    }
}
echo 'Rendered 12 native Blade fixtures into '.$target.PHP_EOL;
