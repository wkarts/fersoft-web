<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Controllers\Api\WhatsappNfeController;
use App\Http\Controllers\PontoWhatsAppController;
use App\Models\AutomationExecution;
use App\Models\ConnectApiWebhookEvent;
use App\Services\ConnectApi\ConnectApiAutomationDispatcher;
use App\Services\ConnectApi\ConnectApiMessageService;
use App\Services\ConnectApi\ConnectApiWebhookNormalizer;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ConnectApiAutomationDispatcherTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Esta classe não usa o MySQL configurado pelo CI nem as migrations globais.
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');
        Http::preventStrayRequests();
        Http::fake();

        Schema::create('automation_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('empresa_id')->nullable();
            $table->string('trigger');
            $table->boolean('enabled')->default(true);
            $table->integer('priority')->default(100);
            $table->boolean('stop_on_success')->default(false);
            $table->json('conditions')->nullable();
            $table->json('actions')->nullable();
        });
        Schema::create('automation_executions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('automation_rule_id')->nullable();
            $table->unsignedInteger('empresa_id')->nullable();
            $table->string('trigger');
            $table->unsignedBigInteger('connect_api_webhook_event_id')->nullable();
            $table->string('status');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('error')->nullable();
            $table->json('context')->nullable();
            $table->timestamps();
        });
    }

    #[DataProvider('outgoingEvents')]
    public function testOutgoingEventsNeverStartControllersOrRules(
        array $message,
        string $eventType,
        string $directionSource
    ): void {
        $event = $this->event($message, true, $eventType);
        $normalized = $event->normalized_payload;
        $raw = $event->payload;

        if ($directionSource === 'legacy') {
            unset($normalized['from_me']);
        } elseif ($directionSource === 'raw overrides false') {
            $normalized['from_me'] = false;
        } elseif ($directionSource === 'normalized only') {
            unset($raw['data']['key']['fromMe']);
        } elseif ($directionSource === 'normalized overrides false') {
            $raw['data']['key']['fromMe'] = false;
        }
        $event->normalized_payload = $normalized;
        $event->payload = $raw;

        $fiscal = Mockery::mock(WhatsappNfeController::class);
        $fiscal->shouldNotReceive('deveProcessarEvento');
        $fiscal->shouldNotReceive('processarEventoConnectApi');
        $point = Mockery::mock(PontoWhatsAppController::class);
        $point->shouldNotReceive('receberMensagem');
        $this->app->instance(WhatsappNfeController::class, $fiscal);
        $this->app->instance(PontoWhatsAppController::class, $point);

        DB::enableQueryLog();
        DB::flushQueryLog();
        (new ConnectApiAutomationDispatcher())->dispatch($event);

        $this->assertSame([], DB::getQueryLog(), 'O eco deve retornar antes de consultar regras.');
        Http::assertNothingSent();
    }

    public static function outgoingEvents(): array
    {
        $messages = [
            'pesagem text' => ['conversation' => 'Ticket de Pesagem #123'],
            'PDF without text' => ['documentMessage' => ['fileName' => 'relatorio.pdf', 'mimetype' => 'application/pdf']],
            'PDF caption' => ['documentMessage' => ['fileName' => 'relatorio.pdf', 'caption' => 'Ticket de Pesagem']],
            'wrapped document' => ['documentWithCaptionMessage' => ['message' => ['documentMessage' => ['fileName' => 'relatorio.pdf']]]],
            'image caption' => ['imageMessage' => ['caption' => 'Ticket de Pesagem']],
            'location' => ['locationMessage' => ['degreesLatitude' => -12.3, 'degreesLongitude' => -39.1]],
        ];
        $cases = [];
        foreach ($messages as $name => $message) {
            foreach (['both', 'legacy', 'raw overrides false', 'normalized only', 'normalized overrides false'] as $source) {
                $cases[$name . ' / ' . $source] = [$message, 'messages.upsert', $source];
            }
        }
        $cases['outgoing status update'] = [[], 'messages.update', 'legacy'];
        $cases['outgoing underscore event'] = [['conversation' => 'Ticket'], 'MESSAGES_UPSERT', 'both'];

        return $cases;
    }

    #[DataProvider('incomingDirections')]
    public function testIncomingPointMessageAndLocationRemainIntact(mixed $direction): void
    {
        $message = [
            'conversation' => '1',
            'locationMessage' => ['degreesLatitude' => -12.3, 'degreesLongitude' => -39.1],
        ];
        $event = $this->event($message, $direction);
        if ($direction === null) {
            $raw = $event->payload;
            unset($raw['data']['key']['fromMe']);
            $event->payload = $raw;
            $normalized = $event->normalized_payload;
            unset($normalized['from_me']);
            $event->normalized_payload = $normalized;
        }

        $fiscal = Mockery::mock(WhatsappNfeController::class);
        $fiscal->shouldReceive('deveProcessarEvento')->once()->with($event)->andReturn(false);
        $fiscal->shouldNotReceive('processarEventoConnectApi');
        $point = Mockery::mock(PontoWhatsAppController::class);
        $point->shouldReceive('receberMensagem')->once()->with(Mockery::on(function (Request $request) {
            $this->assertFalse($request->input('data.key.fromMe'));
            $this->assertSame('TEST-MESSAGE', $request->input('data.key.id'));
            $this->assertSame('5500000000000@s.whatsapp.net', $request->input('data.key.remoteJid'));
            $this->assertSame('1', $request->input('data.message.conversation'));
            $this->assertSame(-12.3, $request->input('data.message.locationMessage.degreesLatitude'));
            $this->assertSame(-39.1, $request->input('data.message.locationMessage.degreesLongitude'));
            $this->assertSame(42, $request->attributes->get('connect_api_empresa_id'));
            return true;
        }))->andReturnNull();
        $this->app->instance(WhatsappNfeController::class, $fiscal);
        $this->app->instance(PontoWhatsAppController::class, $point);

        (new ConnectApiAutomationDispatcher())->dispatch($event);
        Http::assertNothingSent();
    }

    public static function incomingDirections(): array
    {
        return [[false], [0], ['false'], ['0'], [' FALSE '], [null]];
    }

    public function testIncomingFiscalCommandStillUsesFiscalController(): void
    {
        $event = $this->event(['conversation' => 'NFE'], false);
        $fiscal = Mockery::mock(WhatsappNfeController::class);
        $fiscal->shouldReceive('deveProcessarEvento')->once()->with($event)->andReturn(true);
        $fiscal->shouldReceive('processarEventoConnectApi')->once()->with($event)->andReturnNull();
        $point = Mockery::mock(PontoWhatsAppController::class);
        $point->shouldNotReceive('receberMensagem');
        $this->app->instance(WhatsappNfeController::class, $fiscal);
        $this->app->instance(PontoWhatsAppController::class, $point);

        (new ConnectApiAutomationDispatcher())->dispatch($event);
        Http::assertNothingSent();
    }

    #[DataProvider('ruleEvents')]
    public function testRulesStillRunForIncomingAndNonMessageEvents(string $eventType): void
    {
        $event = $this->event(['conversation' => 'OI'], false, $eventType);
        $trigger = 'connect-api.' . $event->normalized_payload['event'];
        DB::table('automation_rules')->insert([
            'empresa_id' => 42,
            'trigger' => $trigger,
            'conditions' => json_encode([]),
            'actions' => json_encode([['type' => 'connect_api.send_text', 'message' => 'Recebido: {text}']]),
        ]);

        $fiscal = Mockery::mock(WhatsappNfeController::class);
        $point = Mockery::mock(PontoWhatsAppController::class);
        if ($eventType === 'messages.upsert') {
            $fiscal->shouldReceive('deveProcessarEvento')->once()->with($event)->andReturn(false);
            $point->shouldReceive('receberMensagem')->once()->andReturnNull();
        } else {
            $fiscal->shouldNotReceive('deveProcessarEvento');
            $point->shouldNotReceive('receberMensagem');
        }
        $this->app->instance(WhatsappNfeController::class, $fiscal);
        $this->app->instance(PontoWhatsAppController::class, $point);
        $sender = Mockery::mock(ConnectApiMessageService::class);
        $sender->shouldReceive('sendText')->once()
            ->with(42, '5500000000000', 'Recebido: OI')->andReturn(['success' => true]);
        $this->app->instance(ConnectApiMessageService::class, $sender);

        // Evita auditoria não relacionada; todas as consultas usam SQLite em memória.
        AutomationExecution::withoutEvents(function () use ($event) {
            (new ConnectApiAutomationDispatcher())->dispatch($event);
        });

        $this->assertSame('processed', DB::table('automation_executions')->value('status'));
        Http::assertNothingSent();
    }

    public static function ruleEvents(): array
    {
        return [['messages.upsert'], ['messages.update'], ['connection.update']];
    }

    private function event(array $message, mixed $direction, string $eventType = 'messages.upsert'): ConnectApiWebhookEvent
    {
        $raw = [
            'event' => $eventType,
            'instance' => 'TEST-INSTANCE',
            'data' => [
                'key' => ['id' => 'TEST-MESSAGE', 'remoteJid' => '5500000000000@s.whatsapp.net', 'fromMe' => $direction],
                'message' => $message,
                'pushName' => 'Teste',
                'messageTimestamp' => 1789876800,
            ],
        ];
        $event = new ConnectApiWebhookEvent([
            'empresa_id' => 42,
            'connect_api_instance_id' => 7,
            'event_type' => str_replace('.', '-', $eventType),
            'payload' => $raw,
            'normalized_payload' => (new ConnectApiWebhookNormalizer())->normalize($raw),
        ]);
        $event->id = 1;

        return $event;
    }
}
