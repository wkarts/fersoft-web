<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Helpers\EncryptionHelper;
use Dotenv\Dotenv;

try {
    // Inicializa o Dotenv para carregar o APP_KEY
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();

    // Inicializa o EncryptionHelper
    EncryptionHelper::initialize();

    // Senha gravada no banco de dados
    $encryptedPasswordFromDb = 'WVFnd0NVR2V5QWJ5bXc5YU1QR28xZz09OjqIEcT8xvIURFVcRBxS6Cbt';

    // Tenta descriptografar
    $decryptedPassword = EncryptionHelper::decrypt($encryptedPasswordFromDb);

    echo PHP_EOL . "Senha descriptografada: " . $decryptedPassword . PHP_EOL;

} catch (\Exception $e) {
    echo 'Erro ao descriptografar: ' . $e->getMessage() . PHP_EOL;
}


/*
try {

    // Inicializa o Dotenv para carregar o APP_KEY
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();

    // Inicializa o EncryptionHelper
    EncryptionHelper::initialize();

    $originalPassword = 'Wk856532#';
    $encrypted = EncryptionHelper::encrypt($originalPassword);
    $decrypted = EncryptionHelper::decrypt($encrypted);

    echo PHP_EOL . "Senha original: " . $originalPassword . PHP_EOL;
    echo "Senha criptografada: " . $encrypted . PHP_EOL;
    echo "Senha descriptografada: " . $decrypted . PHP_EOL;
} catch (\Exception $e) {
    echo 'Erro no helper: ' . $e->getMessage() . PHP_EOL;
}
*/
/*
try {
    // Inicializa o Dotenv para carregar o APP_KEY
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();

    // Inicializa o EncryptionHelper
    EncryptionHelper::initialize();

    // Senha criptografada que será testada
    $encryptedPassword = readline("WVFnd0NVR2V5QWJ5bXc5YU1QR28xZz09OjqIEcT8xvIURFVcRBxS6Cbt");

    // Descriptografa a senha
    $decryptedPassword = EncryptionHelper::decrypt($encryptedPassword);

    // Exibe os resultados
    echo PHP_EOL . "Senha descriptografada: " . $decryptedPassword . PHP_EOL;
} catch (\Exception $e) {
    echo 'Erro no helper: ' . $e->getMessage() . PHP_EOL;
}
*/

/*
try {
    // Inicializa o Dotenv para carregar o APP_KEY
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();

    // Inicializa o EncryptionHelper
    EncryptionHelper::initialize();

    $originalPassword = 'Wk856532#';
    $encrypted = EncryptionHelper::encrypt($originalPassword);
    $decrypted = EncryptionHelper::decrypt($encrypted);

    dd([
        'original' => $originalPassword,
        'encrypted' => $encrypted,
        'decrypted' => $decrypted,
    ]);
} catch (\Exception $e) {
    dd('Erro no helper: ' . $e->getMessage());
}
*/

/*
try {
    // Carrega o .env manualmente
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();

    // Inicializa o EncryptionHelper
    EncryptionHelper::initialize();

    // Testa a criptografia
    $encrypted = EncryptionHelper::encrypt('Texto de teste');
    echo "Texto criptografado: $encrypted\n";

    $decrypted = EncryptionHelper::decrypt($encrypted);
    echo "Texto descriptografado: $decrypted\n";
} catch (\Exception $e) {
    echo "Erro ao executar teste: " . $e->getMessage() . "\n";
}
*/
