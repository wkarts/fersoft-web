<?php

namespace App\Http\Controllers;

use App\Models\ClienteDelivery;
use App\Models\DeliveryConfig;
use App\Utils\WhatsAppUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeliveryBotController extends Controller
{
    public function processarMensagem($telefone, $texto, $empresaId, $pushName = null, $requestData = null)
    {
        $whatsappUtil = app(WhatsAppUtil::class);
        $telefoneLimpo = preg_replace('/[^0-9]/', '', $telefone);

        // ==========================================
        // TRAVA ANTI-ECO / MENSAGENS ENVIADAS PELA PRÓPRIA LOJA
        // ==========================================
        // Se a API do WhatsApp indicar que a mensagem partiu de nós mesmos (fromMe), ignoramos na hora.
        if (isset($requestData['key']['fromMe']) && $requestData['key']['fromMe'] === true) {
            return;
        }

        // Também ignoramos se o texto contiver o aviso de contracheque do RH
        if (str_contains($texto, 'contracheque') || str_contains($texto, 'REGISTRO DE PONTO')) {
            return;
        }

        // Se o Delivery estiver desativado nas configurações, não faz nada
        $configDelivery = DeliveryConfig::where('empresa_id', $empresaId)->first();
        if ($configDelivery && isset($configDelivery->ativo_bot_whatsapp) && $configDelivery->ativo_bot_whatsapp == 0) {
            return;
        }
        // ==========================================

        // 1. Localiza ou cadastra o cliente automaticamente
        $cliente = ClienteDelivery::where('empresa_id', $empresaId)
            ->where('celular', 'LIKE', '%' . substr($telefoneLimpo, -8) . '%')
            ->first();

        $nomeCliente = !empty($pushName) ? $pushName : 'Cliente WhatsApp';
        $partesNome = explode(' ', $nomeCliente, 2);
        $primeiroNome = $partesNome[0];
        $sobreNome = $partesNome[1] ?? '.';

        if (!$cliente) {
            $cliente = ClienteDelivery::create([
                'empresa_id' => $empresaId,
                'nome' => $primeiroNome,
                'sobre_nome' => $sobreNome,
                'celular' => $telefoneLimpo,
                'email' => uniqid('zap_', true) . '@friccaozero.local',
                'senha' => md5(uniqid()),
                'ativo' => 1,
                'token' => rand(100000, 999999),
            ]);
        }

        // 2. Limpeza básica do texto
        $textoLimpo = trim($texto);
        if (str_contains($textoLimpo, ':')) {
            $partes = explode(':', $textoLimpo);
            $textoLimpo = trim(end($partes));
        }
        $textoLower = mb_strtolower($textoLimpo);

        // 3. Gerenciamento de Sessão
        $sessao = DB::table('delivery_sessoes')
            ->where('empresa_id', $empresaId)
            ->where('celular', $telefoneLimpo)
            ->first();

        if (!$sessao) {
            DB::table('delivery_sessoes')->insert([
                'empresa_id' => $empresaId,
                'celular' => $telefoneLimpo,
                'etapa' => 'INICIO',
                'dados_temp' => json_encode([]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $etapa = 'INICIO';
            $carrinho = [];
        } else {
            $etapa = $sessao->etapa;
            $carrinho = json_decode($sessao->dados_temp, true) ?? [];
        }

        // Comando universal para reiniciar
        if (in_array($textoLower, ['menu', 'cardapio', 'cardápio', 'inicio', 'olá', 'oi', 'olar'])) {
            DB::table('delivery_sessoes')->where('empresa_id', $empresaId)->where('celular', $telefoneLimpo)->update([
                'etapa' => 'ESCOLHER_CANAL',
                'dados_temp' => json_encode(['cliente_id' => $cliente->id, 'itens' => [], 'desconto' => 0]),
                'updated_at' => now()
            ]);

            $config = DeliveryConfig::where('empresa_id', $empresaId)->first();
            $nomeLoja = $config->nome ?? 'Delivery';

            $resposta = "🍔 *Olá, {$primeiroNome}! Seja muito bem-vindo ao atendimento oficial do {$nomeLoja}!*\n\n";
            $resposta .= "É um prazer atender você. Como prefere realizar o seu pedido hoje?\n\n";
            $resposta .= "1️⃣ - Pedir por aqui mesmo (Atendimento Rápido no WhatsApp)\n";
            $resposta .= "2️⃣ - Acessar o Cardápio Web Completo\n\n";
            $resposta .= "👉 *Digite o número da opção desejada:*";

            $whatsappUtil->sendMessage("55" . $telefoneLimpo, $resposta, $empresaId);
            return;
        }

        // Proteção contra sessões travadas
        if (in_array($etapa, ['PIZZA_ESCOLHER_TAMANHO', 'PIZZA_QTD_SABORES', 'PIZZA_ESCOLHER_SABORES', 'PIZZA_QUANTIDADE']) && !isset($carrinho['produto_atual'])) {
            DB::table('delivery_sessoes')->where('empresa_id', $empresaId)->where('celular', $telefoneLimpo)->delete();
            $whatsappUtil->sendMessage("55" . $telefoneLimpo, "⚠️ Houve uma atualização na sessão. Por favor, digite *menu* para recomeçar:", $empresaId);
            return;
        }

        // 4. Máquina de Estados Profissional
        switch ($etapa) {
            case 'ESCOLHER_CANAL':
                if ($textoLimpo === '2') {
                    $config = DeliveryConfig::where('empresa_id', $empresaId)->first();
                    $linkCardapio = env('PATH_URL') . "/cardapio?empresa=" . $empresaId;

                    $resposta = "📋 *Perfeito! Acesse o nosso cardápio online pelo link abaixo:*\n\n";
                    $resposta .= "🔗 {$linkCardapio}\n\n";
                    $resposta .= "Assim que finalizar por lá, seu pedido será enviado diretamente para a nossa cozinha. Bom apetite!";

                    $whatsappUtil->sendMessage("55" . $telefoneLimpo, $resposta, $empresaId);
                    DB::table('delivery_sessoes')->where('empresa_id', $empresaId)->where('celular', $telefoneLimpo)->delete();
                    return;
                }

                DB::table('delivery_sessoes')->where('empresa_id', $empresaId)->where('celular', $telefoneLimpo)->update([
                    'etapa' => 'ESCOLHER_BAIRRO',
                    'dados_temp' => json_encode($carrinho),
                    'updated_at' => now()
                ]);

                $whatsappUtil->sendMessage("55" . $telefoneLimpo, "🛵 Para começarmos com a entrega, por favor, **digite o nome do seu bairro**:", $empresaId);
                break;

            case 'ESCOLHER_BAIRRO':
                $termoBairro = trim($textoLimpo);

                $bairroSelecionado = DB::table('bairro_delivery_lojas')
                    ->where('empresa_id', $empresaId)
                    ->where('nome', 'LIKE', '%' . $termoBairro . '%')
                    ->first();

                if (!$bairroSelecionado) {
                    $whatsappUtil->sendMessage("55" . $telefoneLimpo, "❌ Infelizmente ainda não realizamos entregas para o bairro *'{$termoBairro}'*.\n\nPor favor, digite o nome correto do seu bairro novamente:", $empresaId);
                    return;
                }

                $carrinho['taxa_entrega'] = floatval($bairroSelecionado->valor_entrega);
                $carrinho['bairro_nome'] = $bairroSelecionado->nome;

                DB::table('delivery_sessoes')->where('empresa_id', $empresaId)->where('celular', $telefoneLimpo)->update([
                    'etapa' => 'SOLICITAR_ENDERECO',
                    'dados_temp' => json_encode($carrinho),
                    'updated_at' => now()
                ]);

                $resposta = "✅ Bairro confirmado: *{$bairroSelecionado->nome}* (Taxa: R$ " . number_format($bairroSelecionado->valor_entrega, 2, ',', '.') . ")\n\n";
                $resposta .= "📍 *Agora, informe o seu endereço completo para entrega:*\n";
                $resposta .= "Ex: _Rua da Glória, 120, Próximo ao mercado_";

                $whatsappUtil->sendMessage("55" . $telefoneLimpo, $resposta, $empresaId);
                break;

            case 'SOLICITAR_ENDERECO':
                if (strlen($textoLimpo) < 5) {
                    $whatsappUtil->sendMessage("55" . $telefoneLimpo, "❌ Por favor, informe um endereço válido contendo rua e número:", $empresaId);
                    return;
                }

                $carrinho['endereco_completo'] = $textoLimpo;

                DB::table('delivery_sessoes')->where('empresa_id', $empresaId)->where('celular', $telefoneLimpo)->update([
                    'etapa' => 'MENU_CATEGORIAS',
                    'dados_temp' => json_encode($carrinho),
                    'updated_at' => now()
                ]);

                $categorias = DB::table('categoria_produto_deliveries')->where('empresa_id', $empresaId)->get();
                $resposta = "🎉 Endereço salvo com sucesso!\n\n📋 *Escolha uma categoria do nosso cardápio:*\n\n";
                foreach ($categorias as $index => $cat) {
                    $num = $index + 1;
                    $resposta .= "*{$num}* - {$cat->nome}\n";
                }
                $resposta .= "\n👉 *Digite o número da categoria desejada:*";

                $whatsappUtil->sendMessage("55" . $telefoneLimpo, $resposta, $empresaId);
                break;

            case 'MENU_CATEGORIAS':
                $categorias = DB::table('categoria_produto_deliveries')->where('empresa_id', $empresaId)->get();
                $indexEscolha = intval($textoLimpo) - 1;

                if (!isset($categorias[$indexEscolha])) {
                    $whatsappUtil->sendMessage("55" . $telefoneLimpo, "❌ Opção inválida. Digite o número correto da categoria:", $empresaId);
                    return;
                }

                $categoriaSelecionada = $categorias[$indexEscolha];

                $produtos = DB::table('produto_deliveries')
                    ->where('empresa_id', $empresaId)
                    ->where('categoria_id', $categoriaSelecionada->id)
                    ->where('status', '1')
                    ->get();

                if ($produtos->isEmpty()) {
                    $whatsappUtil->sendMessage("55" . $telefoneLimpo, "⚠️ Não há produtos disponíveis nesta categoria no momento. Escolha outra:", $empresaId);
                    return;
                }

                DB::table('delivery_sessoes')->where('empresa_id', $empresaId)->where('celular', $telefoneLimpo)->update([
                    'etapa' => 'LISTANDO_PRODUTOS',
                    'updated_at' => now()
                ]);

                $resposta = "🍽️ *Produtos da categoria {$categoriaSelecionada->nome}:*\n\n";
                foreach ($produtos as $prod) {
                    $resposta .= "🔹 *Cód: {$prod->id}* | *{$prod->descricao_curta}*\n";
                    $resposta .= "   Preço: R$ " . number_format($prod->valor, 2, ',', '.') . "\n";
                    if (!empty($prod->descricao)) {
                        $resposta .= "   _{$prod->descricao}_\n";
                    }
                    $resposta .= "\n";
                }
                $resposta .= "✍️ *Digite o CÓDIGO (ID) do item que deseja pedir:*";

                $whatsappUtil->sendMessage("55" . $telefoneLimpo, $resposta, $empresaId);
                break;

            case 'LISTANDO_PRODUTOS':
                $produtoId = intval($textoLimpo);
                $produto = DB::table('produto_deliveries')
                    ->where('empresa_id', $empresaId)
                    ->where('id', $produtoId)
                    ->where('status', '1')
                    ->first();

                if (!$produto) {
                    $whatsappUtil->sendMessage("55" . $telefoneLimpo, "❌ Código não encontrado. Digite um ID válido da lista:", $empresaId);
                    return;
                }

                $tamanhosPizza = DB::table('produto_pizzas')
                    ->join('tamanho_pizzas', 'tamanho_pizzas.id', '=', 'produto_pizzas.tamanho_id')
                    ->where('produto_pizzas.produto_id', $produto->id)
                    ->select('tamanho_pizzas.id as tamanho_id', 'tamanho_pizzas.nome as tamanho_nome', 'tamanho_pizzas.maximo_sabores', 'produto_pizzas.valor')
                    ->get();

                // Já guarda o primeiro sabor escolhido na raiz do produto!
                $carrinho['produto_atual'] = [
                    'id' => $produto->id,
                    'nome' => $produto->descricao_curta,
                    'preco' => floatval($produto->valor),
                    'is_pizza' => $tamanhosPizza->isNotEmpty(),
                    'tamanhos_disponiveis' => $tamanhosPizza->toArray(),
                    'sabores_escolhidos' => [$produto->descricao_curta] // 1º sabor já garantido aqui!
                ];

                if ($tamanhosPizza->isNotEmpty()) {
                    DB::table('delivery_sessoes')->where('empresa_id', $empresaId)->where('celular', $telefoneLimpo)->update([
                        'etapa' => 'PIZZA_ESCOLHER_TAMANHO',
                        'dados_temp' => json_encode($carrinho),
                        'updated_at' => now()
                    ]);

                    $resposta = "🍕 *Você escolheu como 1º sabor:* {$produto->descricao_curta}\n\n📏 *Escolha o Tamanho da Pizza:*\n\n";
                    foreach ($tamanhosPizza as $index => $t) {
                        $num = $index + 1;
                        $resposta .= "*{$num}* - {$t->tamanho_nome} (R$ " . number_format($t->valor, 2, ',', '.') . ")\n";
                    }
                    $resposta .= "\n👉 *Digite o número do tamanho desejado:*";
                    $whatsappUtil->sendMessage("55" . $telefoneLimpo, $resposta, $empresaId);
                } else {
                    DB::table('delivery_sessoes')->where('empresa_id', $empresaId)->where('celular', $telefoneLimpo)->update([
                        'etapa' => 'QUANTIDADE_PRODUTO',
                        'dados_temp' => json_encode($carrinho),
                        'updated_at' => now()
                    ]);

                    $whatsappUtil->sendMessage("55" . $telefoneLimpo, "📦 Produto selecionado: *{$produto->descricao_curta}* (R$ " . number_format($produto->valor, 2, ',', '.') . ")\n\nDigite a *quantidade* desejada:", $empresaId);
                }
                break;

            case 'PIZZA_ESCOLHER_TAMANHO':
                $tamanhos = $carrinho['produto_atual']['tamanhos_disponiveis'] ?? [];
                $indexEscolha = intval($textoLimpo) - 1;
                $tamanhosArray = is_array($tamanhos) ? $tamanhos : (array)$tamanhos;

                if (!isset($tamanhosArray[$indexEscolha])) {
                    $whatsappUtil->sendMessage("55" . $telefoneLimpo, "❌ Tamanho inválido. Digite o número correspondente ao tamanho:", $empresaId);
                    return;
                }

                $tamanhoEscolhido = (object)$tamanhosArray[$indexEscolha];

                $carrinho['produto_atual']['tamanho_id'] = intval($tamanhoEscolhido->tamanho_id);
                $carrinho['produto_atual']['tamanho_nome'] = $tamanhoEscolhido->tamanho_nome;
                $carrinho['produto_atual']['preco'] = floatval($tamanhoEscolhido->valor);

                $maxSabores = intval($tamanhoEscolhido->maximo_sabores);
                $carrinho['produto_atual']['max_sabores'] = $maxSabores;

                // Se o tamanho aceita mais de 1 sabor, vamos pedir os adicionais
                if ($maxSabores > 1) {
                    $carrinho['produto_atual']['sabor_atual_contador'] = 2; // O próximo é o 2º sabor

                    DB::table('delivery_sessoes')->where('empresa_id', $empresaId)->where('celular', $telefoneLimpo)->update([
                        'etapa' => 'PIZZA_ESCOLHER_SABORES',
                        'dados_temp' => json_encode($carrinho),
                        'updated_at' => now()
                    ]);

                    $pizzasDisponiveis = DB::table('produto_deliveries')
                        ->where('produto_deliveries.empresa_id', $empresaId)
                        ->where('produto_deliveries.status', '1')
                        ->join('categoria_produto_deliveries', 'categoria_produto_deliveries.id', '=', 'produto_deliveries.categoria_id')
                        ->where('categoria_produto_deliveries.tipo_pizza', '1')
                        ->select('produto_deliveries.id', 'produto_deliveries.descricao_curta')
                        ->get();

                    $resposta = "✨ Tamanho *{$tamanhoEscolhido->tamanho_nome}* selecionado (Permite até {$maxSabores} sabores).\n\n";
                    $resposta .= "🍕 *Escolha o 2º sabor da sua pizza:*\n\n";
                    foreach ($pizzasDisponiveis as $p) {
                        $resposta .= "• *Cód: {$p->id}* - {$p->descricao_curta}\n";
                    }
                    $resposta .= "\n👉 *Digite o CÓDIGO do 2º sabor:*";

                    $whatsappUtil->sendMessage("55" . $telefoneLimpo, $resposta, $empresaId);
                } else {
                    // Se o tamanho aceita apenas 1 sabor, vai direto para a quantidade
                    DB::table('delivery_sessoes')->where('empresa_id', $empresaId)->where('celular', $telefoneLimpo)->update([
                        'etapa' => 'PIZZA_QUANTIDADE',
                        'dados_temp' => json_encode($carrinho),
                        'updated_at' => now()
                    ]);

                    $whatsappUtil->sendMessage("55" . $telefoneLimpo, "✨ Tamanho *{$tamanhoEscolhido->tamanho_nome}* selecionado.\n\n📦 Quantas unidades desta pizza você deseja? (Ex: 1, 2...):", $empresaId);
                }
                break;

            case 'PIZZA_ESCOLHER_SABORES':
                $saborId = intval($textoLimpo);
                $saborObj = DB::table('produto_deliveries')->where('id', $saborId)->first();

                if (!$saborObj) {
                    $whatsappUtil->sendMessage("55" . $telefoneLimpo, "❌ Sabor não encontrado. Digite um código válido da lista:", $empresaId);
                    return;
                }

                // Adiciona o novo sabor escolhido
                $carrinho['produto_atual']['sabores_escolhidos'][] = $saborObj->descricao_curta;

                $contadorAtual = intval($carrinho['produto_atual']['sabor_atual_contador'] ?? 2);
                $maxSabores = intval($carrinho['produto_atual']['max_sabores'] ?? 1);

                // Se ainda puder adicionar mais sabores
                if ($contadorAtual < $maxSabores) {
                    $proximo = $contadorAtual + 1;
                    $carrinho['produto_atual']['sabor_atual_contador'] = $proximo;

                    DB::table('delivery_sessoes')->where('empresa_id', $empresaId)->where('celular', $telefoneLimpo)->update([
                        'dados_temp' => json_encode($carrinho),
                        'updated_at' => now()
                    ]);

                    $whatsappUtil->sendMessage("55" . $telefoneLimpo, "✅ Sabor *{$saborObj->descricao_curta}* adicionado! Agora digite o CÓDIGO do *{$proximo}º sabor*:", $empresaId);
                    return;
                }

                // Atingiu o limite de sabores: avança para a quantidade sem erros de sintaxe
                DB::table('delivery_sessoes')->where('empresa_id', $empresaId)->where('celular', $telefoneLimpo)->update([
                    'etapa' => 'PIZZA_QUANTIDADE',
                    'dados_temp' => json_encode($carrinho),
                    'updated_at' => now()
                ]);

                $listaSaboresResumo = implode(' / ', $carrinho['produto_atual']['sabores_escolhidos']);
                $resposta = "🍕 *Sabores definidos:* {$listaSaboresResumo}\n\n";
                $resposta += "📦 Quantas unidades desta pizza você deseja? (Digite apenas um número, ex: 1, 2...):";

                $whatsappUtil->sendMessage("55" . $telefoneLimpo, $resposta, $empresaId);
                break;

            case 'PIZZA_QUANTIDADE':
                $qtd = intval(preg_replace('/[^0-9]/', '', $textoLimpo));

                if ($qtd <= 0 || $qtd > 20) {
                    $whatsappUtil->sendMessage("55" . $telefoneLimpo, "❌ Quantidade inválida. Por favor, digite apenas o número de unidades (Ex: 1, 2...):", $empresaId);
                    return;
                }

                $carrinho['produto_atual']['quantidade'] = $qtd;
                $saboresStr = implode(' / ', $carrinho['produto_atual']['sabores_escolhidos']);
                $carrinho['produto_atual']['nome'] = "Pizza " . $carrinho['produto_atual']['tamanho_nome'] . " ({$saboresStr})";

                DB::table('delivery_sessoes')->where('empresa_id', $empresaId)->where('celular', $telefoneLimpo)->update([
                    'etapa' => 'OBSERVACAO_PRODUTO',
                    'dados_temp' => json_encode($carrinho),
                    'updated_at' => now()
                ]);

                $subtotalItem = floatval($carrinho['produto_atual']['preco']) * $qtd;
                $subtotalFmt = number_format($subtotalItem, 2, ',', '.');

                $resposta = "✅ Você pediu: *{$qtd}x Pizza {$carrinho['produto_atual']['tamanho_nome']}* ({$saboresStr})\n";
                $resposta .= "💰 Subtotal deste item: *R$ {$subtotalFmt}*\n\n";
                $resposta .= "📝 Deseja adicionar alguma observação? (Ex: *bem passada*, *sem cebola*). Se não houver, digite *não*:";

                $whatsappUtil->sendMessage("55" . $telefoneLimpo, $resposta, $empresaId);
                break;

            case 'QUANTIDADE_PRODUTO':
                $qtd = intval($textoLimpo);
                if ($qtd <= 0) {
                    $whatsappUtil->sendMessage("55" . $telefoneLimpo, "❌ Quantidade inválida. Digite um número maior que zero:", $empresaId);
                    return;
                }

                $carrinho['produto_atual']['quantidade'] = $qtd;
                $subtotalItem = $carrinho['produto_atual']['preco'] * $qtd;

                DB::table('delivery_sessoes')->where('empresa_id', $empresaId)->where('celular', $telefoneLimpo)->update([
                    'etapa' => 'OBSERVACAO_PRODUTO',
                    'dados_temp' => json_encode($carrinho),
                    'updated_at' => now()
                ]);

                $subtotalFmt = number_format($subtotalItem, 2, ',', '.');
                $resposta = "✅ Subtotal deste item: *R$ {$subtotalFmt}*\n\n";
                $resposta .= "📝 Deseja incluir alguma observação? (Ex: *sem cebola*, *bem passado*).\n\nSe não houver, digite *não*:";

                $whatsappUtil->sendMessage("55" . $telefoneLimpo, $resposta, $empresaId);
                break;

            case 'OBSERVACAO_PRODUTO':
                $obs = (strtolower($textoLimpo) === 'não' || strtolower($textoLimpo) === 'nao') ? '' : $textoLimpo;

                $carrinho['produto_atual']['observacao'] = $obs;
                $carrinho['itens'][] = $carrinho['produto_atual'];
                unset($carrinho['produto_atual']);

                DB::table('delivery_sessoes')->where('empresa_id', $empresaId)->where('celular', $telefoneLimpo)->update([
                    'etapa' => 'CONTINUAR_OU_FECHAR',
                    'dados_temp' => json_encode($carrinho),
                    'updated_at' => now()
                ]);

                $whatsappUtil->sendMessage("55" . $telefoneLimpo, "✅ Item adicionado ao carrinho!\n\nO que deseja fazer agora?\n1️⃣ - Adicionar mais produtos\n2️⃣ - Finalizar Pedido", $empresaId);
                break;

            case 'CONTINUAR_OU_FECHAR':
                if ($textoLimpo === '1') {
                    DB::table('delivery_sessoes')->where('empresa_id', $empresaId)->where('celular', $telefoneLimpo)->update([
                        'etapa' => 'MENU_CATEGORIAS',
                        'dados_temp' => json_encode($carrinho),
                        'updated_at' => now()
                    ]);

                    $categorias = DB::table('categoria_produto_deliveries')->where('empresa_id', $empresaId)->get();
                    $resposta = "📋 *Escolha outra categoria:*\n\n";
                    foreach ($categorias as $index => $cat) {
                        $num = $index + 1;
                        $resposta .= "*{$num}* - {$cat->nome}\n";
                    }
                    $whatsappUtil->sendMessage("55" . $telefoneLimpo, $resposta, $empresaId);
                } elseif ($textoLimpo === '2') {
                    DB::table('delivery_sessoes')->where('empresa_id', $empresaId)->where('celular', $telefoneLimpo)->update([
                        'etapa' => 'SOLICITAR_CUPOM',
                        'dados_temp' => json_encode($carrinho),
                        'updated_at' => now()
                    ]);

                    $whatsappUtil->sendMessage("55" . $telefoneLimpo, "🎟️ Você possui algum *Cupom de Desconto*?\n\nSe tiver, digite o código do cupom. Se não tiver, digite *não*:", $empresaId);
                } else {
                    $whatsappUtil->sendMessage("55" . $telefoneLimpo, "❌ Opção inválida. Digite *1* para mais itens ou *2* para finalizar.", $empresaId);
                }
                break;

            case 'SOLICITAR_CUPOM':
                $codigoCupom = trim($textoLimpo);
                $desconto = 0;

                if (strtolower($codigoCupom) !== 'não' && strtolower($codigoCupom) !== 'nao') {
                    // Validação simples de cupom cadastrado (ajuste conforme sua tabela de cupons se houver)
                    $cupomObj = DB::table('delivery_ cupons') // ou tabela equivalente no seu ERP
                    ->where('empresa_id', $empresaId)
                        ->where('codigo', $codigoCupom)
                        ->where('ativo', 1)
                        ->first();

                    // Se não tiver tabela de cupom própria, tratamos o desconto de forma segura ou avisa
                    if ($cupomObj) {
                        $desconto = floatval($cupomObj->valor_desconto ?? 0);
                        $carrinho['cupom_nome'] = $codigoCupom;
                    } else {
                        // Exemplo flexível: se digitar cupom livre ou ignorar
                        $desconto = 0;
                    }
                }

                $carrinho['desconto'] = $desconto;

                DB::table('delivery_sessoes')->where('empresa_id', $empresaId)->where('celular', $telefoneLimpo)->update([
                    'etapa' => 'FORMA_PAGAMENTO',
                    'dados_temp' => json_encode($carrinho),
                    'updated_at' => now()
                ]);

                $resposta = $desconto > 0 ? "🎉 Cupom aplicado com sucesso!\n\n" : "";
                $resposta .= "💳 *Escolha a Forma de Pagamento:*\n1️⃣ - Dinheiro\n2️⃣ - Cartão / Maquininha\n3️⃣ - Pix";

                $whatsappUtil->sendMessage("55" . $telefoneLimpo, $resposta, $empresaId);
                break;

            case 'FORMA_PAGAMENTO':
                $formas = ['1' => 'dinheiro', '2' => 'maquineta', '3' => 'pix'];
                $formaEscolhida = $formas[$textoLimpo] ?? 'maquineta';
                $carrinho['forma_pagamento'] = $formaEscolhida;

                if ($formaEscolhida === 'dinheiro') {
                    DB::table('delivery_sessoes')->where('empresa_id', $empresaId)->where('celular', $telefoneLimpo)->update([
                        'etapa' => 'SOLICITAR_TROCO',
                        'dados_temp' => json_encode($carrinho),
                        'updated_at' => now()
                    ]);

                    $whatsappUtil->sendMessage("55" . $telefoneLimpo, "💵 Vai precisar de troco? Se sim, informe para quanto (Ex: *Para 50*). Se não precisar, digite *não*:", $empresaId);
                    return;
                }

                $this->finalizarPedidoComSucesso($empresaId, $telefoneLimpo, $carrinho, $whatsappUtil);
                break;

            case 'SOLICITAR_TROCO':
                $troco = (strtolower($textoLimpo) === 'não' || strtolower($textoLimpo) === 'nao') ? '0.00' : $textoLimpo;
                $carrinho['troco_para'] = $troco;

                $this->finalizarPedidoComSucesso($empresaId, $telefoneLimpo, $carrinho, $whatsappUtil);
                break;
        }
    }

    private function finalizarPedidoComSucesso($empresaId, $telefoneLimpo, $carrinho, $whatsappUtil)
    {
        $valorProdutos = 0;
        foreach ($carrinho['itens'] as $item) {
            $valorProdutos += ($item['preco'] * $item['quantidade']);
        }

        $taxaEntrega = floatval($carrinho['taxa_entrega'] ?? 0);
        $desconto = floatval($carrinho['desconto'] ?? 0);
        $valorTotal = ($valorProdutos + $taxaEntrega) - $desconto;
        if ($valorTotal < 0) $valorTotal = 0;

        $trocoPara = $carrinho['troco_para'] ?? '0.00';
        $formaPagamento = $carrinho['forma_pagamento'] ?? 'maquineta';
        $enderecoCompleto = $carrinho['endereco_completo'] ?? 'Endereço não informado';
        $bairroNome = $carrinho['bairro_nome'] ?? 'Não informado';

        // Grava na tabela pedido_deliveries do ERP
        $pedidoId = DB::table('pedido_deliveries')->insertGetId([
            'empresa_id' => $empresaId,
            'filial_id' => null,
            'usuario_id' => 1,
            'cliente_id' => $carrinho['cliente_id'],
            'data_registro' => now(),
            'valor_total' => $valorTotal,
            'valor_entrega' => $taxaEntrega,
            'desconto' => $desconto,
            'troco_para' => is_numeric($trocoPara) ? floatval($trocoPara) : 0.00,
            'forma_pagamento' => $formaPagamento,
            'observacao' => "Endereço: {$enderecoCompleto} - Bairro: {$bairroNome} | Pedido via WhatsApp Bot",
            'telefone' => $telefoneLimpo,
            'estado' => 'novo',
            'entregue' => '0',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($carrinho['itens'] as $item) {
            DB::table('item_pedido_deliveries')->insert([
                'pedido_id' => $pedidoId,
                'produto_id' => $item['id'],
                'status' => '0',
                'quantidade' => $item['quantidade'],
                'valor' => $item['preco'],
                'observacao' => $item['observacao'] ?? '',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Resumo profissional impecável para o cliente
        $resumo = "🎉 *Pedido #{$pedidoId} Registrado com Sucesso!*\n\n";
        $resumo .= "📋 *Itens do Pedido:*\n";
        foreach ($carrinho['itens'] as $item) {
            $sub = $item['preco'] * $item['quantidade'];
            $resumo .= "• {$item['quantidade']}x {$item['nome']} - R$ " . number_format($sub, 2, ',', '.') . "\n";
            if (!empty($item['observacao'])) {
                $resumo .= "  _Obs: {$item['observacao']}_\n";
            }
        }

        $resumo .= "\n📍 *Local de Entrega:*\n";
        $resumo .= "• Endereço: {$enderecoCompleto}\n";
        $resumo .= "• Bairro: {$bairroNome}\n";
        $resumo .= "• Taxa de Entrega: R$ " . number_format($taxaEntrega, 2, ',', '.') . "\n";

        if ($desconto > 0) {
            $resumo .= "• Desconto Aplicado: - R$ " . number_format($desconto, 2, ',', '.') . "\n";
        }

        $resumo .= "\n💳 *Pagamento:* " . ucfirst($formaPagamento);
        if ($formaPagamento === 'dinheiro' && $trocoPara > 0) {
            $resumo .= " (Troco para: R$ " . number_format(floatval($trocoPara), 2, ',', '.') . ")";
        }

        $resumo .= "\n\n💰 *VALOR TOTAL: R$ " . number_format($valorTotal, 2, ',', '.') . "*\n\n";
        $resumo .= "🚀 Seu pedido já foi enviado para a nossa equipe e impressora. Agradecemos a preferência!";

        $whatsappUtil->sendMessage("55" . $telefoneLimpo, $resumo, $empresaId);

        // Limpa a sessão ao concluir
        DB::table('delivery_sessoes')->where('empresa_id', $empresaId)->where('celular', $telefoneLimpo)->delete();
    }
}
