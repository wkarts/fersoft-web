<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Nesk\Puphpeteer\Puppeteer;
use Nesk\Rialto\Data\JsFunction;
use Exception;
use Illuminate\Support\Facades\Log;

class EletronicDocsController extends BaseController
{
    protected $formTitle = 'Documentos Eletrônicos';
    protected $listView = 'eletronic_docs.list';
    protected $redirectPage = '/eletronicDocs';

    /**
     * Validações para o formulário.
     */
    protected function rules(): array
    {
        return [
            'chave' => 'required|string|size:44',
        ];
    }

    /**
     * Mensagens personalizadas.
     */
    protected function messages(): array
    {
        return [
            'chave.required' => 'A chave de acesso é obrigatória.',
            'chave.size' => 'A chave de acesso deve ter 44 caracteres.',
        ];
    }

    /**
     * Lista a página principal.
     */
    public function list(Request $request)
    {
        return view($this->listView, [
            'title' => $this->formTitle,
        ]);
    }

    /**
     * Detecta o caminho correto do Node.js conforme o sistema operacional.
     */
    protected function getNodePath()
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'
            ? base_path('bin\node\windows\node.exe') // Windows
            : base_path('bin/node/linux/bin/node');    // Linux
    }

    /**
     * Executa o script Puppeteer para consulta e download.
     */
    public function download(Request $request)
    {
        // Validação
        $request->validate([
            'chave' => 'required|string|size:44',
        ]);

        $chave = $request->input('chave');
        Log::info('Iniciando processo para a chave: ' . $chave); // Log inicial

        try {
            // Configuração do Puppeteer
            $puppeteer = new Puppeteer([
                'executable_path' => $this->getNodePath(),
                'log_browser_console' => true, // Log do console
            ]);

            // Inicia o navegador
            Log::info('Iniciando navegador...');
            $browser2 = $puppeteer->launch([
                'headless' => false, // Modo visível para depuração
                'args' => [
                    '--no-sandbox',
                    '--disable-setuid-sandbox',
                    '--start-maximized', // Tela cheia
                ],
                'defaultViewport' => null,
            ]);

            $browser = $puppeteer->launch([
                'headless' => false, // Visível para testes
                'args' => [
                    '--no-sandbox',
                    '--disable-setuid-sandbox',
                    '--disable-dev-shm-usage',
                    '--remote-debugging-address=127.0.0.1', // Força o uso do IPv4
                    '--remote-debugging-port=65222', // Porta específica
                ],
                'defaultViewport' => null,
            ]);

            $page = $browser->newPage();
            $page->setViewport(['width' => 1366, 'height' => 768]);

            Log::info('Acessando a página de consulta...');
            $page->goto("https://consultadanfe.com/?chave={$chave}", [
                'waitUntil' => 'networkidle2',
            ]);

            // Fecha modal inicial, se existir
            $modalSelector = '#modalSystem';
            $closeModalButton = '.btn-danger[data-dismiss="modal"]';

            if ($page->querySelector($modalSelector)) {
                Log::info('Modal detectado. Fechando...');
                $page->click($closeModalButton);
                sleep(2); // Aguarda 2 segundos após fechar modal
            }

            // Aguarda o usuário resolver o reCAPTCHA
            Log::info('Esperando interação manual com o reCAPTCHA...');
            echo "Resolva o reCAPTCHA manualmente e clique no botão de busca.";
            sleep(30); // Aguarda 30 segundos para interação manual

            // Aguarda o modal de download aparecer
            Log::info('Aguardando modal de download...');
            $page->waitForSelector('#modalNFe', ['visible' => true, 'timeout' => 60000]);

            // Captura links para download
            $pdfLink = $page->evaluate(JsFunction::createWithBody("
                return document.querySelector('a[onclick*=\"DownPDF\"]').href;
            "));

            $xmlLink = $page->evaluate(JsFunction::createWithBody("
                return document.querySelector('a[onclick*=\"DownXML\"]').href;
            "));

            Log::info('Links capturados com sucesso.');

            // Fecha o navegador
            $browser->close();

            // Retorna os links
            return response()->json([
                'pdf_url' => utf8_encode($pdfLink),
                'xml_url' => utf8_encode($xmlLink),
            ]);

        } catch (\Nesk\Rialto\Exceptions\Node\FatalProcessException $e) {
            Log::error('Erro no Node.js: ' . $e->getMessage());
            return response()->json(['error' => 'Erro interno ao executar Node.js.'], 500);
        } catch (\Nesk\Rialto\Exceptions\ProcessException $e) {
            Log::error('Erro no processo Puppeteer: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao processar os dados no navegador.'], 500);
        } catch (Exception $e) {
            Log::error('Erro desconhecido: ' . $e->getMessage());
            return response()->json(['error' => 'Erro inesperado. Tente novamente.'], 500);
        }
    }
}
