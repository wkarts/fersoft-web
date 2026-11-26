/* A câmera e as fotos pendentes pertencem ao formulário de um único ticket. */
window.CameraTicket = (() => {
    const capturas = new WeakMap();

    function iniciar(area) {
        const buscar = seletor => area.querySelector(seletor);
        const botao = nome => buscar('[data-acao="' + nome + '"]');
        const video = buscar('video');
        const previa = buscar('[data-previa]');
        const cameras = buscar('[data-cameras]');
        const aviso = buscar('[data-aviso]');
        const fotos = [];
        let versao = 0;
        let rascunho = null;

        function parar() {
            if (video.srcObject) {
                video.srcObject.getTracks().forEach(track => track.stop());
                video.srcObject = null;
            }
        }

        function fechar() {
            ++versao;
            parar();
            if (rascunho) URL.revokeObjectURL(rascunho.url);
            rascunho = null;
            previa.removeAttribute('src');
            buscar('[data-legenda]').value = '';
            buscar('[data-painel]').hidden = true;
            botao('abrir').hidden = false;
            botao('abrir').disabled = fotos.length >= 4;
        }

        async function abrir(deviceId) {
            fechar();
            const tentativa = versao;
            aviso.textContent = '';
            botao('abrir').hidden = true;
            buscar('[data-painel]').hidden = false;
            [previa, buscar('[data-rotulo]'), buscar('[data-selecao]'), botao('usar'), botao('refazer'), botao('capturar')].forEach(el => el.hidden = true);
            video.hidden = false;
            try {
                if (!navigator.mediaDevices?.getUserMedia) {
                    throw new Error('Abra o sistema por HTTPS em um navegador com acesso à câmera.');
                }
                const origem = deviceId ? {deviceId: {exact: deviceId}} : {facingMode: {ideal: 'environment'}};
                const stream = await navigator.mediaDevices.getUserMedia({video: origem, audio: false});
                if (tentativa !== versao) {
                    stream.getTracks().forEach(track => track.stop());
                    return;
                }
                video.srcObject = stream;
                await video.play();
                if (tentativa !== versao) return;
                botao('capturar').hidden = false;
                botao('capturar').disabled = false;
                const dispositivos = await navigator.mediaDevices.enumerateDevices().catch(() => []);
                if (tentativa !== versao || !video.srcObject) return;
                cameras.replaceChildren();
                dispositivos.filter(d => d.kind === 'videoinput' && d.deviceId).forEach((d, i) => {
                    cameras.add(new Option(d.label || 'Câmera ' + (i + 1), d.deviceId));
                });
                cameras.value = stream.getVideoTracks()[0].getSettings().deviceId || '';
                buscar('[data-selecao]').hidden = cameras.length < 2;
            } catch (erro) {
                if (tentativa !== versao) return;
                fechar();
                aviso.textContent = erro.name === 'NotAllowedError'
                    ? 'Acesso à câmera negado. Libere a câmera nas permissões do navegador para tirar uma foto.'
                    : (erro.name === 'Error' ? erro.message : 'Não foi possível abrir a câmera. Confira se está conectada e disponível.');
            }
        }

        function capturar() {
            if (!video.videoWidth || !video.videoHeight) return;
            const tentativa = versao;
            botao('capturar').disabled = true;
            const canvas = document.createElement('canvas');
            const escala = Math.min(1, 1600 / Math.max(video.videoWidth, video.videoHeight));
            canvas.width = Math.round(video.videoWidth * escala);
            canvas.height = Math.round(video.videoHeight * escala);
            canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
            canvas.toBlob(blob => {
                if (tentativa !== versao) return;
                if (!blob || blob.size > 1536 * 1024) {
                    aviso.textContent = 'Não foi possível preparar a foto. Tente capturar novamente.';
                    botao('capturar').disabled = false;
                    return;
                }
                parar();
                rascunho = {blob, url: URL.createObjectURL(blob), chave: crypto.randomUUID(), data: new Date().toISOString()};
                previa.src = rascunho.url;
                [video, buscar('[data-selecao]'), botao('capturar')].forEach(el => el.hidden = true);
                [previa, buscar('[data-rotulo]'), botao('usar'), botao('refazer')].forEach(el => el.hidden = false);
            }, 'image/jpeg', 0.8);
        }

        function usar() {
            if (!rascunho) return;
            const foto = rascunho;
            rascunho = null;
            foto.legenda = buscar('[data-legenda]').value.trim();
            fotos.push(foto);
            const item = document.createElement('figure');
            item.className = 'mr-3 mb-2';
            const img = document.createElement('img');
            img.src = foto.url;
            img.width = 100;
            img.alt = foto.legenda || 'Foto pendente';
            const legenda = document.createElement('figcaption');
            legenda.textContent = foto.legenda || 'Foto pendente';
            const remover = document.createElement('button');
            remover.type = 'button';
            remover.className = 'btn btn-sm btn-light';
            remover.textContent = 'Remover';
            remover.onclick = () => {
                fotos.splice(fotos.indexOf(foto), 1);
                URL.revokeObjectURL(foto.url);
                item.remove();
                botao('abrir').disabled = false;
                aviso.textContent = '';
            };
            item.append(img, legenda, remover);
            buscar('[data-pendentes]').append(item);
            fechar();
            aviso.textContent = 'A foto será anexada ao salvar o ticket.';
        }

        botao('abrir').onclick = () => abrir();
        botao('cancelar').onclick = fechar;
        botao('refazer').onclick = () => abrir(cameras.value);
        botao('capturar').onclick = capturar;
        botao('usar').onclick = usar;
        cameras.onchange = () => abrir(cameras.value);

        return {
            preparar(form) {
                if (!buscar('[data-painel]').hidden) {
                    throw new Error('Use a foto ou cancele a câmera antes de salvar o ticket.');
                }
                if (!fotos.length) return null;
                const dados = new FormData(form);
                dados.append('quantidade_fotos', fotos.length);
                fotos.forEach((foto, i) => {
                    const campo = 'fotos_dispositivo[' + i + ']';
                    dados.append(campo + '[arquivo]', foto.blob, 'captura.jpg');
                    dados.append(campo + '[chave]', foto.chave);
                    dados.append(campo + '[capturado_em]', foto.data);
                    dados.append(campo + '[legenda]', foto.legenda);
                });
                return dados;
            },
            limpar() {
                fechar();
                fotos.splice(0).forEach(foto => URL.revokeObjectURL(foto.url));
                buscar('[data-pendentes]').replaceChildren();
                botao('abrir').disabled = false;
                aviso.textContent = '';
            }
        };
    }

    document.addEventListener('click', evento => {
        const area = evento.target.closest('[data-camera-ticket]');
        if (area && !capturas.has(area)) capturas.set(area, iniciar(area));
    }, true);

    function galeria(fotos) {
        const grupo = document.createElement('details');
        const titulo = document.createElement('summary');
        titulo.textContent = 'Fotos do dispositivo (' + fotos.length + ')';
        grupo.append(titulo);
        grupo.addEventListener('toggle', () => {
            if (!grupo.open || grupo.children.length > 1) return;
            fotos.forEach(foto => {
                const link = document.createElement('a');
                link.href = foto.url;
                link.target = '_blank';
                link.rel = 'noopener';
                link.className = 'd-block my-2';
                const imagem = document.createElement('img');
                imagem.src = foto.url;
                imagem.width = 96;
                imagem.alt = foto.legenda || 'Câmera do dispositivo';
                link.append(imagem, document.createTextNode(' ' + (foto.legenda || 'Abrir foto')));
                grupo.append(link);
            });
        });
        return grupo;
    }

    return {
        preparar: form => capturas.get(form.querySelector('[data-camera-ticket]'))?.preparar(form),
        limpar: form => capturas.get(form.querySelector('[data-camera-ticket]'))?.limpar(),
        carregarGalerias(tabela) {
            if (!tabela || !tabela.rows.length) return;
            $.getJSON(tabela.dataset.fotosUrl).done(dados => {
                for (const linha of tabela.rows) {
                    const fotos = dados[linha.id.replace('ticket-', '')];
                    if (fotos?.length && !linha.querySelector('details')) {
                        linha.lastElementChild.append(galeria(fotos));
                    }
                }
            });
        }
    };
})();
