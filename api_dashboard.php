<?php
declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/valida_sessao.php';
require_once __DIR__ . '/includes/funcoes_estatisticas.php';

// Proteção da rota: exige que o usuário esteja logado (bloqueia acessos diretos)
exigir_login();

header('Content-Type: application/json; charset=utf-8');

$metodo = $_SERVER['REQUEST_METHOD'];
if ($metodo !== 'GET') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['sucesso' => false, 'erro' => 'Método não permitido. Utilize GET.']);
    exit;
}

try {
    $conexao = obter_conexao();
    
    // Busca os dados consolidados usando as funções que reescrevemos
    $cards_resumo = obter_dados_dashboard($conexao);
    $analise_avancada = responder_perguntas_analiticas($conexao);

    // Devolve o JSON estruturado para o front-end montar a tela
    echo json_encode([
        'sucesso' => true,
        'dados' => [
            'resumo' => $cards_resumo,
            'analise_avancada' => $analise_avancada
        ]
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'erro' => 'Erro interno no servidor ao processar a Dashboard.']);
}