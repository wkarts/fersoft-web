<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Helpers\Menu;
use App\Http\Middleware\ValidaAcesso;
use App\Models\Empresa;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ContratosListNavigationTest extends TestCase
{
    private const CONNECTION = 'contratos_navigation_test';

    private ?string $originalConnection = null;
    private array $originalServer = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalServer = $_SERVER;
        if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('Este teste requer fixtures SQLite isoladas em memória.');
        }

        $this->originalConnection = config('database.default');
        config(['database.connections.' . self::CONNECTION => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]]);
        DB::setDefaultConnection(self::CONNECTION);

        Schema::connection(self::CONNECTION)->create('usuarios', function (Blueprint $table): void {
            $table->unsignedInteger('id')->primary();
            $table->text('permissao');
        });
        Schema::connection(self::CONNECTION)->create('tributacaos', function (Blueprint $table): void {
            $table->unsignedInteger('id')->primary();
            $table->unsignedInteger('empresa_id');
        });
        DB::connection(self::CONNECTION)->table('usuarios')->insert([
            'id' => 1,
            'permissao' => '[]',
        ]);
        session()->put('user_logged', ['id' => 1, 'super' => 0, 'empresa' => 1]);
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->originalServer;
        if ($this->originalConnection !== null) {
            DB::purge(self::CONNECTION);
            DB::setDefaultConnection($this->originalConnection);
        }
        parent::tearDown();
    }

    public static function listPaths(): array
    {
        return [
            'sem filtros' => ['/contratos/list', []],
            'status' => ['/contratos/list?status=Ativo', ['status' => 'Ativo']],
            'filtros combinados' => [
                '/contratos/list?cliente_id=7&filial_id=matriz&status=Finalizado',
                ['cliente_id' => '7', 'filial_id' => 'matriz', 'status' => 'Finalizado'],
            ],
        ];
    }

    #[DataProvider('listPaths')]
    public function test_authorized_user_keeps_access_and_filters(string $path, array $filters): void
    {
        DB::connection(self::CONNECTION)->table('usuarios')->where('id', 1)
            ->update(['permissao' => '["/contratos"]']);

        $response = $this->handlePath($path, function (Request $request) use ($filters): Response {
            $this->assertSame($filters, $request->query());

            return new Response('allowed', 200);
        });

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('allowed', $response->getContent());
    }

    #[DataProvider('listPaths')]
    public function test_unauthorized_user_is_denied_with_or_without_filters(string $path, array $filters): void
    {
        $response = $this->handlePath($path, function (): Response {
            $this->fail('Usuário sem /contratos não deve alcançar o próximo middleware.');
        });

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/error', parse_url($response->headers->get('Location'), PHP_URL_PATH));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        session()->forget('user_logged');
        $response = $this->handlePath('/contratos/list');
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/login', parse_url($response->headers->get('Location'), PHP_URL_PATH));
    }

    public function test_existing_superuser_access_is_preserved(): void
    {
        session()->put('user_logged', ['id' => 1, 'super' => 1, 'empresa' => 1]);
        $this->assertSame('allowed', $this->handlePath('/contratos/list')->getContent());
    }

    public function test_canonical_route_and_legacy_actions_keep_their_middleware(): void
    {
        $this->assertSame('/contratos/list', route('contratos.index', [], false));

        foreach ([['GET', '/contratos/list', 'list'], ['POST', '/contratos/list', 'filtro'], ['GET', '/contratos', 'list']] as [$method, $path, $action]) {
            $route = app('router')->getRoutes()->match(Request::create($path, $method));
            $this->assertSame('App\\Http\\Controllers\\ContratoEngenhariaController@' . $action, $route->getActionName());
            foreach (['verificaEmpresa', 'validaAcesso', 'verificaContratoAssinado', 'limiteArmazenamento'] as $middleware) {
                $this->assertContains($middleware, $route->middleware());
            }
        }
    }

    public function test_menu_separates_navigation_url_from_existing_permission(): void
    {
        $contract = null;
        foreach ((new Menu())->getMenu() as $section) {
            foreach ($section['subs'] as $item) {
                if ($item['rota'] === '/contratos') {
                    $contract = $item;
                }
            }
        }

        $this->assertNotNull($contract);
        $this->assertSame('/contratos/list', $contract['url']);
        $this->assertTrue(Empresa::validaLink($contract['rota'], ['/contratos']));
        $this->assertFalse(Empresa::validaLink($contract['rota'], []));

        // Render only the link fragment, without unrelated tenant layout composers.
        app('view')->setDispatcher(new Dispatcher($this->app));
        foreach (['menu_lateral', 'menu_superior'] as $layout) {
            $source = file_get_contents(resource_path('views/default/' . $layout . '.blade.php'));
            $href = 'href="{{$i[\'url\'] ?? $i[\'rota\']}}"';
            $this->assertStringContainsString($href, $source);
            $this->assertSame('href="/contratos/list"', Blade::render($href, ['i' => $contract]));
            $this->assertSame('href="/clientes"', Blade::render($href, ['i' => ['rota' => '/clientes']]));
        }
    }

    public function test_list_links_and_return_paths_use_the_canonical_route(): void
    {
        foreach (['list', 'detalhes'] as $view) {
            $source = file_get_contents(resource_path('views/contratos/' . $view . '.blade.php'));
            $this->assertStringContainsString("route('contratos.index', [], false)", $source);
            $this->assertStringNotContainsString('href="/contratos"', $source);
            $this->assertStringNotContainsString('action="/contratos"', $source);
        }

        $controller = file_get_contents(app_path('Http/Controllers/ContratoEngenhariaController.php'));
        $this->assertStringContainsString("\$this->redirectPage = '/contratos';", $controller);
        $this->assertStringContainsString("'actionCancel' => route('contratos.index', [], false)", $controller);
        $this->assertSame(3, substr_count($controller, "redirect()->route('contratos.index')"));
        $this->assertStringNotContainsString("redirect('/contratos')", $controller);
    }

    private function handlePath(string $path, ?\Closure $next = null): Response
    {
        $_SERVER['REQUEST_URI'] = $path;
        $_SERVER['HTTP_HOST'] = 'localhost';
        unset($_SERVER['HTTPS']);
        $request = Request::create('http://localhost' . $path, 'GET');
        $this->app->instance('request', $request);

        return (new ValidaAcesso())->handle($request, $next ?? static function (): Response {
            return new Response('allowed', 200);
        });
    }
}
