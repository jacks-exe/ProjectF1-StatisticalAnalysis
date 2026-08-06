<?php
declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/funcoes_usuarios.php';

$nome = 'Administrador';
$email = 'admin@moneyball.com';
$senha = 'admin123';

$conexao = obter_conexao();

if (email_ja_existe($conexao, $email)) {
    echo "Usuario admin ja existe." . PHP_EOL;
    exit;
}

$id = inserir_usuario($conexao, $nome, $email, $senha, 'admin');
echo "Admin criado com id {$id}. Email: {$email} Senha: {$senha}" . PHP_EOL;
