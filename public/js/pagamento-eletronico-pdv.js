(() => {
    const area = document.getElementById('asaas-pdv');
    if (!area) return;
    const botao = document.getElementById('asaas-pdv-emitir');
    const liberar = document.getElementById('asaas-pdv-liberar');
    const link = document.getElementById('asaas-pdv-link');
    const mensagem = document.getElementById('asaas-pdv-status');
    const chave = 'asaas-pdv:' + area.dataset.conexao + ':' + area.dataset.usuario;
    let operacao = JSON.parse(sessionStorage.getItem(chave) || 'null');

    function mostrar() {
        liberar.hidden = !operacao;
        if (operacao && operacao.url) {
            link.href = operacao.url;
            link.hidden = false;
            mensagem.textContent = 'Consulte a cobrança antes de finalizar a venda.';
        }
    }
    window.pagamentoEletronicoPdv = {
        referencia: () => operacao ? (operacao.referencia || 'aguardando-emissao') : null,
        concluir() {
            sessionStorage.removeItem(chave);
            operacao = null;
            link.hidden = true;
            liberar.hidden = true;
        }
    };
    mostrar();
    liberar.addEventListener('click', async () => {
        if (!operacao || botao.disabled) return;
        try {
            const resposta = await fetch(area.dataset.liberar, {
                method: 'POST', headers: {'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.getElementById('_token').value},
                body: JSON.stringify({operacao: operacao.id, conexao: area.dataset.conexao})
            });
            if (!resposta.ok) throw new Error('Conclua a venda ou cancele a cobrança de produção para liberar o caixa.');
            window.pagamentoEletronicoPdv.concluir();
            mensagem.textContent = 'Caixa liberado.';
        } catch (erro) {
            mensagem.textContent = erro.message;
        }
    });
    botao.addEventListener('click', async () => {
        const metodo = {'17': 'pix', '03': 'credit_card', '15': 'boleto'}[document.getElementById('tipo-pagamento').value];
        const centavos = Math.round((Number(TOTAL) + Number(VALORBAIRRO) + Number(VALORACRESCIMO) - Number(DESCONTO)) * 100);
        const filial = Math.max(0, Number.parseInt(document.getElementById('filial')?.value, 10) || 0);
        if (!metodo || !CLIENTE?.id || !Number.isSafeInteger(centavos) || centavos <= 0 || PAGMULTI.length || filial !== Number(area.dataset.local)) {
            mensagem.textContent = 'Selecione a filial ativa, um cliente e uma única forma de pagamento Pix, boleto ou cartão de crédito.';
            return;
        }
        if (!operacao) operacao = {id: crypto.randomUUID()};
        sessionStorage.setItem(chave, JSON.stringify(operacao));
        mostrar();
        botao.disabled = true;
        try {
            const resposta = await fetch(area.dataset.url, {
                method: 'POST',
                headers: {'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.getElementById('_token').value},
                body: JSON.stringify({operacao: operacao.id, cliente: CLIENTE.id, centavos, metodo, itens: ITENS.map(({id, quantidade, valor}) => ({id, quantidade, valor})), conexao: area.dataset.conexao})
            });
            if (!resposta.ok) throw new Error('Confira os dados e o histórico em Financeiro → Pagamentos eletrônicos antes de tentar novamente.');
            const cobranca = await resposta.json();
            operacao.referencia = cobranca.referencia;
            operacao.url = cobranca.url;
            sessionStorage.setItem(chave, JSON.stringify(operacao));
            mostrar();
        } catch (erro) {
            mensagem.textContent = erro.message;
        } finally {
            botao.disabled = false;
        }
    });
})();
