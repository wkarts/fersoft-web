<?php
use PhpSerial\PhpSerial;

// Inicializa a comunicação serial
$serial = new PhpSerial();

// Configura a porta COM5 com o prefixo para Windows
$serial->deviceSet("\\\\.\\COM5");

// Configurações adicionais
$serial->confBaudRate(9600); // Baud Rate
$serial->confParity("none"); // Paridade
$serial->confCharacterLength(8); // Bits de Dados
$serial->confStopBits(1); // Bits de Parada
$serial->confFlowControl("none"); // Controle de Fluxo

// Abre a porta
if ($serial->deviceOpen()) {
    echo "Porta COM5 aberta com sucesso!\n";

    // Envia um comando de teste para a balança
    $serial->sendMessage("LE_PESO");
    sleep(1);

    // Lê a resposta da balança
    $resposta = $serial->readPort();
    echo "Resposta da balança: " . $resposta;

    // Fecha a porta
    $serial->deviceClose();
} else {
    echo "Erro ao abrir a porta COM5.\n";
}
