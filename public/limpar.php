<?php
$comando = "cd .. && php artisan optimize:clear";
echo "<pre>" . shell_exec($comando) . "</pre>";
echo "<h3>Limpeza forcada concluida com sucesso!</h3>";
?>