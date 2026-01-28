const puppeteer = require('puppeteer');

// Recebe o parâmetro da chave de acesso
const chave = process.argv[2];

if (!chave) {
    console.error('Erro: Chave de acesso não fornecida!');
    process.exit(1);
}

(async () => {
    console.log('Abrindo navegador...');
    const browser = await puppeteer.launch({
        headless: false, // Mantém o navegador visível
        args: ['--start-maximized'], // Abre em tela cheia
        defaultViewport: null, // Responsivo
    });

    const page = await browser.newPage();
    await page.setViewport({ width: 1366, height: 768 });

    console.log('Abrindo página...');
    await page.goto(`https://consultadanfe.com/?chave=${chave}`, {
        waitUntil: 'networkidle2',
    });

    try {
        // Fecha o modal inicial, se presente
        const modalSelector = '#modalSystem';
        const closeModalButton = '.btn-danger[data-dismiss="modal"]';
        if (await page.$(modalSelector)) {
            console.log('Modal inicial detectado. Fechando...');
            await page.click(closeModalButton);
            console.log('Modal inicial fechado.');

            // Espera 2 segundos após fechar modal
            await new Promise(resolve => setTimeout(resolve, 2000));
        }

        console.log('Esperando interação manual com o reCAPTCHA...');
        console.log('Resolva o reCAPTCHA manualmente e clique no botão de busca.');

        // Aguarda o botão "Buscar DANFE / XML"
        await page.waitForSelector('.g-recaptcha', { visible: true, timeout: 60000 });
        console.log('Botão encontrado! Clicando...');
        await page.click('.g-recaptcha'); // Clica no botão

        // Aguarda o modal de download aparecer
        console.log('Aguardando modal de download...');
        await page.waitForSelector('#modalNFe', { visible: true, timeout: 60000 });
        console.log('Modal de download carregado com sucesso!');

        // Clica no botão para baixar o PDF
        //console.log('Baixando PDF...');
        //await page.click('a[onclick*="DownPDF"]');
        //console.log('PDF baixado com sucesso!');

        // Aguarda 2 segundos para baixar o XML
        await new Promise(resolve => setTimeout(resolve, 2000));

        // Clica no botão para baixar o XML
        console.log('Baixando XML...');
        await page.click('a[onclick*="DownXML"]');
        console.log('XML baixado com sucesso!');

    } catch (error) {
        console.error('Erro no Puppeteer:', error.message);
    }

    console.log('Processo concluído! Navegador permanecerá aberto para conferência manual.');
})();
