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
        const habilitados = area.dataset.metodos.split(',');
        const metodos = Object.fromEntries(Object.entries({'17': 'pix', '03': 'credit_card', '15': 'boleto'})
            .filter(([, metodo]) => habilitados.includes(metodo)));
        const seletor = document.getElementById('asaas-pdv-parcela');
        const candidatas = PAGMULTI.map((p, indice) => ({...p, indice})).filter(p => metodos[p.tipo]);
        const assinatura = JSON.stringify(candidatas.map(p => [p.indice, p.tipo, p.valor]));
        if (seletor.dataset.opcoes !== assinatura) {
            seletor.replaceChildren(new Option('Selecione a parcela', ''));
            candidatas.forEach(p => seletor.add(new Option((p.indice + 1) + ' · ' + metodos[p.tipo] + ' · R$ ' + p.valor, p.indice)));
            if (candidatas.length === 1) seletor.value = String(candidatas[0].indice);
            seletor.dataset.opcoes = assinatura;
        }
        document.getElementById('asaas-pdv-parcela-label').hidden = !PAGMULTI.length;
        const parcela = PAGMULTI.length && seletor.value !== '' ? Number(seletor.value) : null;
        const metodo = metodos[PAGMULTI.length ? PAGMULTI[parcela]?.tipo : document.getElementById('tipo-pagamento').value];
        const total = Math.round((Number(TOTAL) + Number(VALORBAIRRO) + Number(VALORACRESCIMO) - Number(DESCONTO)) * 100);
        const centavos = parcela === null ? total : Math.round(Number(String(PAGMULTI[parcela].valor).replace(',', '.')) * 100);
        const filial = Math.max(0, Number.parseInt(document.getElementById('filial')?.value, 10) || 0);
        if (!metodo || !CLIENTE?.id || !Number.isSafeInteger(centavos) || centavos <= 0 || filial !== Number(area.dataset.local)) {
            mensagem.textContent = 'Selecione a filial ativa, o cliente e a forma ou parcela que será cobrada por Pix, boleto ou cartão.';
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
                body: JSON.stringify({operacao: operacao.id, cliente: CLIENTE.id, centavos, total, metodo, numero_parcelas: metodo === 'credit_card' ? Number(document.getElementById('asaas-pdv-numero-parcelas')?.value ?? 1) : 1, parcela, parcelas: PAGMULTI.map(({tipo, valor}) => ({tipo, valor})), itens: ITENS.map(({id, quantidade, valor}) => ({id, quantidade, valor})), conexao: area.dataset.conexao})
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
