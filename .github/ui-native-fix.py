from pathlib import Path
p=Path('tests/ui/render-native.php');s=p.read_text()
block=r'''// Real menu helper, real permission filtering, test-only in-memory rows.
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
'''
if "Schema::create('tributacaos'" not in s:
 assert s.count('$base = [') == 1
 s=s.replace('$base = [',block+'\n$base = [')
 start=s.index("$labels=['Auditoria'");end=s.index('// Reuse the application',start)
 s=s[:start]+s[end:]
 p.write_text(s)
p=Path('tests/ui/native-browser.py');s=p.read_text();s=s.replace("assert page.locator('#ui-navigation').get_by_text('NÃO AUTORIZADO').count()==0", "assert page.locator('#ui-navigation a[href=\"/logs\"]').count()==0")
p.write_text(s)
