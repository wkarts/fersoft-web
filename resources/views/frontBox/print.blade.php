<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Git</title>
</head>
<body>
    <input id="texto" type="button" value="Imprimir Teste" style="width: 50%; height: 50px;"><br>
    <input id="qrcode" type="button" value="Imprimir QRCode" style="width: 50%; height: 50px;"><br>
    <input id="status" type="button" value="Status Impressora" style="width: 50%; height: 50px;"><br>
    <input id="abre" type="button" value="Abre Conexao" style="width: 50%; height: 50px;"><br>
    <input id="fecha" type="button" value="Fecha Conexao" style="width: 50%; height: 50px;"><br>
    <input id="qrcode-solo" type="button" value="Imprimir QRCode Solo" style="width: 50%; height: 50px;"><br>
    <input id="texto-solo" type="button" value="Imprimir Texto Solo" style="width: 50%; height: 50px;"><br>
    <input id="abre-gaveta" type="button" value="Abre Gaveta" style="width: 50%; height: 50px;"><br>
    <input id="corte" type="button" value="Corte" style="width: 50%; height: 50px;"><br>
    <input id="avanca" type="button" value="Avanca Papel" style="width: 50%; height: 50px;"><br>
    <input id="cupomtef" type="button" value="Cupom Tef" style="width: 50%; height: 50px;"><br>
    <input id="imprimeXMLSAT" type="button" value="Imprime XML SAT" style="width: 50%; height: 50px;"><br>
    <ul id="list">
        <li>resultados:</li>
    </ul>
    <script>
        // document.querySelector('#texto').addEventListener('click', testeImpressaoTexto)
        // document.querySelector('#qrcode').addEventListener('click', testeImpressaoQRCode)
        document.querySelector('#abre').addEventListener('click', testeAbreConexao)
        document.querySelector('#status').addEventListener('click', testeStatusImpressora)
        document.querySelector('#qrcode-solo').addEventListener('click', testeImpressaoQRCodeSolo)
        document.querySelector('#texto-solo').addEventListener('click', testeImpressaoTextoSolo)
        document.querySelector('#abre-gaveta').addEventListener('click', testeAbreGaveta)
        document.querySelector('#corte').addEventListener('click', testeCorte)
        document.querySelector('#avanca').addEventListener('click', testeAvancaPapel)
        document.querySelector('#cupomtef').addEventListener('click', testeImprimeCupomTEF)
        document.querySelector('#fecha').addEventListener('click', testeFechaConexao)
        document.querySelector('#imprimeXMLSAT').addEventListener('click', testeImprimeXMLSAT)

        function addLi(text) {
            let ul = document.getElementById("list");
            let li = document.createElement("li");
            li.appendChild(document.createTextNode(text));
            ul.appendChild(li);
        }

        function removeLis() {
            document.getElementById("list").innerHTML = "";
        }

        function testeAbreConexao() {
            removeLis()
            addLi('bora lá')
            let args = JSON.stringify({ tipo: 5, modelo: '', conexao: '', parametro: 0 })
            let result = Termica.AbreConexaoImpressora(args)
            addLi('abreconexaoimpressora')
            addLi(`result: ${result}`)
        }

        function testeFechaConexao() {
            removeLis()
            result = Termica.FechaConexaoImpressora(JSON.stringify({}))
            addLi('fecha')
            addLi(`result: ${result}`)
        }

        function testeImpressaoTexto() {
            removeLis()
            addLi('bora lá')
            let args = JSON.stringify({ tipo: 5, modelo: '', conexao: '', parametro: 0 })
            let result = Termica.AbreConexaoImpressora(args)
            addLi('abreconexaoimpressora')
            addLi(`result: ${result}`)
    
            args = JSON.stringify({ dados : 'Elgin Developers Community From Web App', 
                                    alinhamento: 0, 
                                    stilo: 0, 
                                    tamanho: 17 })
            result = Termica.ImpressaoTexto(args)
            addLi('impressao texto')
            addLi(`result: ${result}`)
    
            result = Termica.Corte(JSON.stringify({ avanco : 10 }))
            addLi('corte')
            addLi(`result: ${result}`)

            result = Termica.FechaConexaoImpressora(JSON.stringify({}))
            addLi('fecha')
            addLi(`result: ${result}`)
        }

        function testeImpressaoQRCode() {
            removeLis()
            addLi('bora lá')
            let args = JSON.stringify({ tipo: 5, modelo: '', conexao: '', parametro: 0 })
            let result = Termica.AbreConexaoImpressora(args)
            addLi('abreconexaoimpressora')
            addLi(`result: ${result}`)
    
            args = JSON.stringify({ dados: 'https://web-app-experience.herokuapp.com/', 
                                    tamanho: 4, 
                                    nivelCorrecao: 2 })
            result = Termica.ImpressaoQRCode(args)
            addLi('impressao texto')
            addLi(`result: ${result}`)
    
            result = Termica.Corte(JSON.stringify({ avanco : 10 }))
            addLi('corte')
            addLi(`result: ${result}`)

            result = Termica.FechaConexaoImpressora(JSON.stringify({}))
            addLi('fecha')
            addLi(`result: ${result}`)
        }

        function testeStatusImpressora() {
            removeLis()
            // addLi('bora lá')
            // let args = JSON.stringify({ tipo: 5, modelo: '', conexao: '', parametro: 0 })
            // let result = Termica.AbreConexaoImpressora(args)
            // addLi('abreconexaoimpressora')
            // addLi(`result: ${result}`)
    
            args = JSON.stringify({ param: 3 })
            result = Termica.StatusImpressora(args)
            addLi('statusimpressora')
            addLi(`result: ${result}`)

            // result = Termica.FechaConexaoImpressora(JSON.stringify({}))
            // addLi('fecha')
            // addLi(`result: ${result}`)
        }

        function testeImpressaoTextoSolo() {
            removeLis()
            args = JSON.stringify({ dados : 'Elgin Developers Community From Web App', 
                                    alinhamento: 0, 
                                    stilo: 0, 
                                    tamanho: 17 })
            result = Termica.ImpressaoTexto(args)
            addLi('impressao texto')
            addLi(`result: ${result}`)
        }

        function testeImpressaoQRCodeSolo() {
            removeLis()
            args = JSON.stringify({ dados: 'https://web-app-experience.herokuapp.com/', 
                                    tamanho: 4, 
                                    nivelCorrecao: 2 })
            result = Termica.ImpressaoQRCode(args)
            addLi('impressao qrcode solo')
            addLi(`result: ${result}`)
        }

        function testeAbreGaveta() {
            removeLis()
            args = JSON.stringify({ })
            result = Termica.AbreGavetaElgin(args)
            addLi('teste gaveta')
            addLi(`result: ${result}`)
        }

        function testeCorte() {
            removeLis()
            result = Termica.Corte(JSON.stringify({ avanco : 10 }))
            addLi('corte')
            addLi(`result: ${result}`)
        }

        function testeAvancaPapel() {
            removeLis()
            result = Termica.AvancaPapel(JSON.stringify({ linhas : 10 }))
            addLi('avancapapel')
            addLi(`result: ${result}`)
        }

        function testeImprimeCupomTEF() {
            let cupom = '000-000 = CRT\r\n' +
                '001-000 = 1\r\n' +
                '002-000 = 123456\r\n' +
                '003-000 = 4500\r\n' +
                '010-000 = ELECTRON\r\n' +
                '010-001 = 103\r\n' +
                '010-003 = 21\r\n' +
                '010-004 = 417402\r\n' +
                '010-005 = 7578\r\n' +
                '011-000 = 03603511027\r\n' +
                '012-000 = 001315\r\n' +
                '013-000 = 001315\r\n' +
                '018-000 = 01\r\n' +
                '022-000 = 0326\r\n' +
                '023-000 = 192414\r\n' +
                '028-000 = 37\r\n' +
                '029-001 = ELGIN PAY TESTE BANRISUL\r\n' +
                '029-002 = 92.702.067/0001-96   \r\n' +
                '029-003 = R CAPITAO MONTANHA, 177\r\n' +
                '029-004 = CENTRO PORTO ALEGRE RS\r\n' +
                '029-005 = \r\n' +
                '029-006 = \r\n' +
                '029-007 = \r\n' +
                '029-008 = \r\n' +
                '029-009 =                  REDE                 \r\n' +
                '029-010 = \r\n' +
                '029-011 = REDESHOP  -      OKI                  \r\n' +
                '029-012 = \r\n' +
                '029-013 = \r\n' +
                '029-014 = COMPROV: 123456789 VALOR: 45,00\r\n' +
                '029-015 = \r\n' +
                '029-016 = ESTAB:013932594 SCOPE TESTE SIMULADO  \r\n' +
                '029-017 = DD.MM.AA-HH:MM:SS TERM:PV123456/pppnnn\r\n' +
                '029-018 = CARTAO: ************7578\r\n' +
                '029-019 = AUTORIZACAO: 123456                   \r\n' +
                '029-020 = ARQC:36DEFEF9D3490BC5\r\n' +
                '029-021 = \r\n' +
                '029-022 = **************************************\r\n' +
                '029-023 =          D E M O N S T R A C A O      \r\n' +
                '029-024 =  Transacao sem validade para reembolso\r\n' +
                '029-025 =     Autorizacao gerada por simulador  \r\n' +
                '029-026 = **************************************\r\n' +
                '029-027 = \r\n' +
                '029-028 =     TRANSACAO AUTORIZADA MEDIANTE     \r\n' +
                '029-029 =         USO DE SENHA PESSOAL.         \r\n' +
                '029-030 =                                       \r\n' +
                '029-031 = 0\r\n' +
                '029-032 = CONTROLE 03603511027  OKI BRASIL SCOPE\r\n' +
                '029-033 = \r\n' +
                '029-034 = \r\n' +
                '029-035 = \r\n' +
                '029-036 = \r\n' +
                '029-037 = \r\n' +
                '030-000 = Transação Finalizada com Sucesso\r\n' +
                '043-000 = SIMULADOR\r\n' +
                '047-000 = 00\r\n' +
                '050-000 = 000\r\n' +
                '150-000 = 000000000000002\r\n' +
                '210-004 = 4174020000007578=25080000000000000000\r\n' +
                '210-052 = 001\r\n' +
                '210-052 = 001\r\n' +
                '300-001 = 0825\r\n' +
                '600-000 = 01425787000104\r\n' +
                '701-016 = 0326\r\n' +
                '999-999 = 0\r\n'

            removeLis()
            result = Termica.ImprimeCupomTEF(JSON.stringify({ dados : cupom }))
            addLi('cupomtef')
            addLi(`result: ${result}`)
        }


        function testeImprimeXMLSAT() {
            removeLis()
            let dados = 'oK1VIW6YLMt8V3yY2NdxZKHlZGOS7i7DMl90sxRHfebbHqYeEeovVPOC3T6/pdfYoaUDzk4sWFz/dffWaQ6lKgF8yM44BYX63c9V1vycfM8x8hXeAAK0Iwna0pp5g75d1SZxpe4DsP+McPsppAJiv3PX1swEsr3a6B6AUJFsN9huhugf4VsWcgPPzbMdxV1K6t20/6YJr77uf2YiLrRBN6JMeTTCaKndVYIImUJjDUmV194bhYmxl2lLDJK9MqPDcfeGRC4WyW4TESfTfQYNIAFOijapZt3LHUFakkmC42NPL0lwKXvZ8Fow618vSVm1PKnoK3i76MaxE9jl8hn8GQ==</SignatureValue> <KeyInfo> <X509Data> <X509Certificate>MIIFzTCCBLWgAwIBAgICGuowDQYJKoZIhvcNAQENBQAwaDELMAkGA1UEBhMCQlIxEjAQBgNVBAgMCVNBTyBQQVVMTzESMBAGA1UEBwwJU0FPIFBBVUxPMQ8wDQYDVQQKDAZBQ0ZVU1AxDzANBgNVBAsMBkFDRlVTUDEPMA0GA1UEAwwGQUNGVVNQMB4XDTE4MDkyMDE0MjAwOVoXDTIzMDkxOTE0MjAwOVowgbIxCzAJBgNVBAYTAkJSMREwDwYDVQQIDAhBbWF6b25hczERMA8GA1UECgwIU0VGQVotU1AxGDAWBgNVBAsMD0FDIFNBVCBTRUZBWiBTUDEoMCYGA1UECwwfQXV0b3JpZGFkZSBkZSBSZWdpc3RybyBTRUZBWiBTUDE5MDcGA1UEAwwwRUxHSU4gSU5EVVNUUklBTCBEQSBBTUFaT05JQSBMVERBOjE0MjAwMTY2MDAwMTY2MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAyFisbknHWpIDQhroPXCT6SJMqSMIxP/nSlxMseAgfd56ac8Mhwl9pXAMLBdz5rq+g83TcV16GFwPTZg1x9SZAbgrGkTTepaCh7lxTh9WuwBhhYU9fBnCNwZcWRjJStNErO2phqvyq9Oc/5rwEv8Vrokff9Ck1TBvXVZIUaBcFuB9dEMVFrxfdYvaRdlfTT9xFeDaLaXZkPOW5Or0rpGap2A10blt7mhuAVbhZrsTjX5SkNRUtNHWp/72e7Q5/5K+KoHXQvlpvQKwA3oaU9ODkrVrcCAsegFDz1d3EIF1KSVf8Nx2JF5pQ7r1m97Y7bxcMukFMq4edSwI624IF6er3wIDAQABo4ICNDCCAjAwCQYDVR0TBAIwADAOBgNVHQ8BAf8EBAMCBeAwLAYJYIZIAYb4QgENBB8WHU9wZW5TU0wgR2VuZXJhdGVkIENlcnRpZmljYXRlMB0GA1UdDgQWBBR7TIYiwFbiCkb/P94YmH2L6ylSxjAfBgNVHSMEGDAWgBQVtOORhiQs6jNPBR4tL5O3SJfHeDATBgNVHSUEDDAKBggrBgEFBQcDAjBDBgNVHR8EPDA6MDigNqA0hjJodHRwOi8vYWNzYXQuZmF6ZW5kYS5zcC5nb3YuYnIvYWNzYXRzZWZhenNwY3JsLmNybDCBpwYIKwYBBQUHAQEEgZowgZcwNQYIKwYBBQUHMAGGKWh0dHA6Ly9vY3NwLXBpbG90LmltcHJlbnNhb2ZpY2lhbC5jb20uYnIvMF4GCCsGAQUFBzAChlJodHRwOi8vYWNzYXQtdGVzdGUuaW1wcmVuc2FvZmljaWFsLmNvbS5ici9yZXBvc2l0b3Jpby9jZXJ0aWZpY2Fkb3MvYWNzYXQtdGVzdGUucDdjMHsGA1UdIAR0MHIwcAYJKwYBBAGB7C0DMGMwYQYIKwYBBQUHAgEWVWh0dHA6Ly9hY3NhdC5pbXByZW5zYW9maWNpYWwuY29tLmJyL3JlcG9zaXRvcmlvL2RwYy9hY3NhdHNlZmF6c3AvZHBjX2Fjc2F0c2VmYXpzcC5wZGYwJAYDVR0RBB0wG6AZBgVgTAEDA6AQDA4xNDIwMDE2NjAwMDE2NjANBgkqhkiG9w0BAQ0FAAOCAQEAMHliSH9gc+1ciPx+wR3/K1WHUBOH+4/FgyHvzk3j8DMk8OGFhyYyvfiXF3gn2KrscXGuT6TY/TQxyJIZjZzujkBc5eDnRPBYOSDkiWpA5vGS2ba8OdhpZrlDfaxgQPN4mX74rEsj1/zydwkfHlObSWWwfqvQqvnvMNYqFuAgVLHfKtikRXId2wuC9uozYpBNxxNWNiCJj2VqD68KL8S1M0locZe/Sq/1HtnziWKyYmnNW6xKcE8wAXOVh13I1eNvWOr8KKPEsoyIq4LdS9TBI82OgjFH/kVWTWCI1RmoSyaw/knlrjT70AseiUHygbDBADeMKAmUi+LlSl5ppV+rrw=='
            args = JSON.stringify({ dados : dados, param : 0 })
            addLi('imprime xml sat')
            result = Termica.ImprimeXMLSAT(args)
            addLi(`result: ${result}`)
        }


    </script>
</body>
</html>