<?php

namespace App\Exceptions;

use App\Services\LogService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use NFePHP\Common\Certificate\Exception\Expired;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * Evita registrar a mesma instância de exception duas vezes na mesma request.
     * O Handler possui callbacks reportable + renderable e, sem essa barreira,
     * ambos podiam chamar logContext() para a mesma falha.
     */
    protected array $loggedExceptionFingerprints = [];

    /**
     * Exceções que não serão reportadas.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        \Illuminate\Validation\ValidationException::class,
        AuthenticationException::class,
        HttpException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
    ];

    /**
     * Inputs que não serão retornados em withInput().
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Registra callbacks para report e render de exceções.
     */
    public function register(): void
    {
        // 1) Report de toda exceção (arquivo + DB + flash)
        $this->reportable(function (Throwable $e) {
            /*
             * Não use request() diretamente aqui.
             * Durante comandos Artisan executados pelo Composer, como package:discover,
             * a aplicação pode ainda não ter um Request HTTP registrado no container.
             * Se chamarmos request() nesse momento, o Laravel tenta resolver a classe
             * "request" e o erro real fica mascarado por:
             * Target class [request] does not exist.
             */
            $request = $this->safeRequest();

            if ($request instanceof Request) {
                $persistToDatabase = !($e instanceof HttpException) || $e->getStatusCode() >= 500;
                $this->logContext('error', $e->getMessage(), $e, $request, $persistToDatabase);

                if (! $request->expectsJson()) {
                    session()->flash('mensagem_erro', $e->getMessage());
                }

                return;
            }

            // Contexto CLI/Composer: registra no log sem depender de sessão/request/banco.
            try {
                \Log::error($e->getMessage(), [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
            } catch (Throwable $inner) {
                // Evita erro secundário no próprio handler de exceções.
            }
        });

        // 2) Tratamento específico para certificado expirado
        $this->renderable([$this, 'handleCertificateExpired']);

        // 3) HTTP exceptions (404,403,419,500...)
        $this->renderable([$this, 'handleHttpException']);

        // 4) QueryException (erros de BD)
        $this->renderable([$this, 'handleQueryException']);

        // 5) Todo o resto
        $this->renderable([$this, 'handleThrowable']);
    }

    /**
     * AuthenticationException -> flash + redirect-login.
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $exception->getMessage()], 401);
        }

        session()->flash('mensagem_erro', 'Você precisa estar autenticado para continuar.');
        return redirect()->guest(route('login'));
    }

    /**
     * Lida com Expired certificate (NFePHP\Common\Certificate\Exception\Expired)
     * — sempre flash + redirect em web; JSON segue padrão.
     */
    public function handleCertificateExpired(Expired $e, Request $request)
    {
        // Se for AJAX/JSON, devolve JSON padrão
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Certificado expirado: ' . $e->getMessage(),
            ], 400);
        }

        // Monta a mensagem
        $message = 'Certificado expirado: ' . $e->getMessage();

        // 1) Log no arquivo e no banco
        $this->logContext('error', $message, $e, $request);

        // 2) Flash + redirect com mensagem e mantém os inputs
        return redirect()->back()
            ->with('mensagem_erro', $message)
            ->withInput();
    }

    /**
     * Lida com HttpException: sempre flash + redirect em web; JSON segue padrão.
     */
    public function handleHttpException(HttpException $e, Request $request)
    {
        if ($request->expectsJson()) {
            return null;
        }

        $code   = $e->getStatusCode();
        $labels = [
            404 => 'Página não encontrada.',
            403 => 'Acesso negado.',
            419 => 'Sessão expirada. Por favor, tente novamente.',
        ];
        $message = $labels[$code] ?? "Erro {$code}: {$e->getMessage()}";
        $level   = $code >= 500 ? 'error' : 'warning';

        $this->logContext($level, $message, $e, $request, $code >= 500);
        session()->flash('mensagem_erro', $message);

        return redirect()->back()->withInput();
    }

    /**
     * Lida com QueryException: sempre flash + redirect em web; JSON segue padrão.
     */
    public function handleQueryException(QueryException $e, Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Erro de Banco de Dados'], 500);
        }

        $message = 'Erro de Banco de Dados: ' . $e->getMessage();
        $this->logContext('critical', $message, $e, $request);
        session()->flash('mensagem_erro', $message);

        return redirect()->back()->withInput();
    }

    /**
     * Lida com qualquer outra Throwable: sempre flash + redirect em web; JSON segue padrão.
     */
    public function handleThrowable(Throwable $e, Request $request)
    {
        if ($request->expectsJson()) {
            return null;
        }

        $message = $e->getMessage();
        $this->logContext('error', $message, $e, $request);
        session()->flash('mensagem_erro', $message);

        return redirect()->back()->withInput();
    }


    /**
     * Retorna o Request apenas quando ele realmente já está registrado no container.
     * Em execução de Composer/Artisan nem sempre existe request HTTP ativo.
     */
    protected function safeRequest(): ?Request
    {
        try {
            if (function_exists('app') && app()->bound('request')) {
                $request = app('request');

                return $request instanceof Request ? $request : null;
            }
        } catch (Throwable $e) {
            return null;
        }

        return null;
    }

    /**
     * Monta contexto de log (arquivo + banco) e dispara.
     *
     * @param string    $level   error|warning|critical|info
     * @param string    $message
     * @param Throwable $e
     * @param Request   $request
     */
    protected function logContext(string $level, string $message, Throwable $e, Request $request, bool $persistToDatabase = true): void
    {
        $fingerprint = implode('|', [
            spl_object_id($e),
            get_class($e),
            $request->method(),
            $request->fullUrl(),
        ]);

        // reportable() e renderable() podem receber a mesma exception. Registra uma vez.
        if (isset($this->loggedExceptionFingerprints[$fingerprint])) {
            return;
        }
        $this->loggedExceptionFingerprints[$fingerprint] = true;

        // 1) Log no arquivo Laravel
        $context = [
            'url'       => $request->fullUrl(),
            'exception' => $e,
        ];
        $empresaId   = null;
        $razaoSocial = null;

        if (Auth::check() && $emp = Auth::user()->empresa) {
            $empresaId   = $emp->empresa_id;
            $razaoSocial = $emp->razao_social;
            $context['empresa_id']   = $empresaId;
            $context['razao_social'] = $razaoSocial;
        }

        \Log::{$level}($message, $context);

        // HTTP 4xx (404/405/scanners, sessão expirada etc.) fica somente no arquivo.
        // Não é evento de auditoria empresarial e não deve aumentar a tabela logs.
        if (!$persistToDatabase) {
            return;
        }

        // 2) Grava também no banco via LogService
        try {
            $usuarioId = Auth::id();
            $filialId  = session('user_logged.local_padrao') ?? null;

            // Corrige valor da filial para null quando for -1 (string ou inteiro)
            $filialFinal = ($filialId === '-1' || $filialId === -1) ? null : (is_numeric($filialId) ? (int)$filialId : null);

            $logService = new LogService(
                $empresaId !== null ? (int)$empresaId : null,
                $usuarioId !== null ? (int)$usuarioId : null,
                $filialFinal
            );

            $logService->registrar(
                'exception',
                get_class($e),
                [
                    'registro_id' => null,
                    'dados_anteriores' => null,
                    'dados_depois'     => [
                        'message'    => mb_substr($message, 0, 2000),
                        'url'        => mb_substr($request->fullUrl(), 0, 1500),
                        'empresa_id' => $empresaId,
                        'empresa'    => $razaoSocial,
                        'usuario_id' => $usuarioId,
                        'filial_id'  => $filialFinal,
                        'file'       => $e->getFile(),
                        'line'       => $e->getLine(),
                        'trace'      => substr($e->getTraceAsString(), 0, 1000),
                    ],
                ]
            );
        } catch (Throwable $inner) {
            \Log::error('Falha ao registrar exception no banco via LogService', [
                'erro' => $inner->getMessage(),
                'original_message' => $message
            ]);
        }
    }

    protected function logContext_(string $level, string $message, Throwable $e, Request $request): void
    {
        // 1) Log no arquivo Laravel
        $context = [
            'url'       => $request->fullUrl(),
            'exception' => $e,
        ];
        $empresaId   = null;
        $razaoSocial = null;

        if (Auth::check() && $emp = Auth::user()->empresa) {
            $empresaId   = $emp->empresa_id;
            $razaoSocial = $emp->razao_social;
            $context['empresa_id']   = $empresaId;
            $context['razao_social'] = $razaoSocial;
        }

        \Log::{$level}($message, $context);

        // 2) Grava em banco via LogService
        try {
            $usuarioId = Auth::id();
            $filialId  = session('user_logged.local_padrao') ?? null;

            $logService = new LogService(
                $empresaId !== null ? (int) $empresaId : null,
                $usuarioId !== null ? (int) $usuarioId : null,
                is_numeric($filialId) && (int)$filialId !== -1 ? (int) $filialId : null
            );

            $logService->registrar(
                'exception',
                get_class($e),
                [
                    'dados_anteriores' => null,
                    'dados_depois'     => [
                        'message'    => $message,
                        'url'        => $request->fullUrl(),
                        'empresa'    => $razaoSocial,
                        'usuario_id' => $usuarioId,
                        'filial_id'  => $filialId,
                    ],
                ]
            );
        } catch (Throwable $inner) {
            \Log::error('Falha ao registrar exception no banco: ' . $inner->getMessage());
        }
    }
}
