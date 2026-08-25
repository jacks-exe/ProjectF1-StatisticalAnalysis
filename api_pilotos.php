<?php
declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/valida_sessao.php';
require_once __DIR__ . '/includes/funcoes_pilotos.php';

exigir_login();

header('Content-Type: application/json; charset=utf-8');

$metodo = $_SERVER['REQUEST_METHOD'];
$conexao = obter_conexao();

try {
    if ($metodo === 'GET') {
        // Captura os filtros que o JavaScript pode enviar pela URL
        $filtros = [
            'nome' => $_GET['nome'] ?? '',
            'equipe' => $_GET['equipe'] ?? '',
            'nacionalidade' => $_GET['nacionalidade'] ?? '',
            'numero' => isset($_GET['numero']) ? (int)$_GET['numero'] : null,
        ];
        
        $pilotos = listar_pilotos($conexao, $filtros);
        
        // JSON_UNESCAPED_UNICODE ajuda a mostrar os acentos bonitos direto no navegador
        echo json_encode([
            'sucesso' => true, 
            'quantidade' => count($pilotos),
            'dados' => $pilotos
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(405);
    echo json_encode(['sucesso' => false, 'erro' => 'Método não permitido']);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'erro' => 'Erro interno no servidor.']);
}