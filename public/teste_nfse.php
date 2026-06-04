<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$cnpj = "06073657000165";
$senha = "1234"; 
$pfxPath = __DIR__ . "/certificados/06073657000165.pfx"; 

echo "<h2>Varredura de Endereços ADN</h2>";

// Lista de possíveis portas (URLs) da Receita em 2026
$urls = [
    "ADN Padrão" => "https://www.nfse.gov.br/api/v1/nfs/distribuicao",
    "ADN Direto" => "https://www.nfse.gov.br/api/v1/distribuicao",
    "ADN Tomadas" => "https://www.nfse.gov.br/api/v1/nfs/distribuicao/tomador"
];

foreach ($urls as $nome => $urlBase) {
    $urlCompleta = $urlBase . "?cnpjTomador=" . $cnpj . "&tpAmb=1";
    echo "<hr><b>Testando: $nome</b><br>URL: $urlCompleta<br>";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $urlCompleta);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'P12');
    curl_setopt($ch, CURLOPT_SSLCERT, $pfxPath);
    curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $senha);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if ($httpCode == 200) {
        echo "<b style='color:green'>✅ SUCESSO! Código 200 encontrado nesta URL.</b><br>";
        echo "Resposta: <pre>" . htmlspecialchars(substr($response, 0, 200)) . "...</pre>";
        break; // Para o loop se achar a correta
    } else {
        echo "<b style='color:red'>❌ Falhou: Código $httpCode</b><br>";
    }
    curl_close($ch);
}