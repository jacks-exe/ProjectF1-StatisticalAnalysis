<?php
declare(strict_types=1);
error_reporting(0);
ini_set('display_errors', '0');

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/valida_sessao.php';
require_once __DIR__ . '/includes/funcoes_estatisticas.php';

exigir_login();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); exit; }

$conexao = obter_conexao();
$ano = isset($_GET['ano']) && $_GET['ano'] !== '' ? (int)$_GET['ano'] : null;

echo json_encode([
    'sucesso' => true,
    'dados' => obter_dados_dashboard($conexao, $ano)
]);