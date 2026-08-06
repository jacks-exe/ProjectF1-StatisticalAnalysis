<?php
declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/valida_sessao.php';
require_once __DIR__ . '/includes/funcoes_corridas.php';

exigir_login();

header('Content-Type: application/json; charset=utf-8');

$metodo = $_SERVER['REQUEST_METHOD'];
$conexao = obter_conexao();

try {
    // ---------------------------------------------------------
    // ROTA: LER DADOS (GET)
    // ---------------------------------------------------------
    if ($metodo === 'GET') {
        // Verifica se o front-end quer filtrar por uma temporada específica
        $temporada_id = isset($_GET['temporada_id']) ? (int)$_GET['temporada_id'] : null;
        
        $corridas = listar_corridas($conexao, $temporada_id);
        
        echo json_encode([
            'sucesso' => true, 
            'quantidade' => count($corridas),
            'dados' => $corridas
        ]);
        exit;
    }

    // ---------------------------------------------------------
    // ROTA: CRIAR DADOS (POST) - Estrutura de exemplo
    // ---------------------------------------------------------
    if ($metodo === 'POST') {
        // exigir_admin(); // Descomente para bloquear criação de dados para usuários comuns
        
        echo json_encode(['sucesso' => false, 'erro' => 'A rota de criação (POST) ainda está em desenvolvimento.']);
        exit;
    }

    // Se não for GET nem POST
    http_response_code(405);
    echo json_encode(['sucesso' => false, 'erro' => 'Método não permitido']);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'erro' => 'Erro interno no servidor.']);
}