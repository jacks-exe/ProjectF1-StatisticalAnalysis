<?php
declare(strict_types=1);
error_reporting(0);
ini_set('display_errors', '0');

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/valida_sessao.php';
require_once __DIR__ . '/includes/funcoes_pilotos.php';

exigir_login();
header('Content-Type: application/json; charset=utf-8');

$filtros = [
    'nome' => $_GET['nome'] ?? '',
    'equipe' => $_GET['equipe'] ?? '',
    'nacionalidade' => $_GET['nacionalidade'] ?? '',
    'ano' => $_GET['ano'] ?? '',
];

echo json_encode(['sucesso' => true, 'dados' => listar_pilotos(obter_conexao(), $filtros)]);