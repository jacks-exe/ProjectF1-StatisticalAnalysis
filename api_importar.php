<?php
declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/valida_sessao.php';
require_once __DIR__ . '/includes/funcoes_importar.php';

exigir_login();
exigir_admin(); // Apenas administradores podem fazer upload de novos dados

header('Content-Type: application/json; charset=utf-8');

$metodo = $_SERVER['REQUEST_METHOD'];
$conexao = obter_conexao();

try {
    if ($metodo === 'POST') {
        // Verifica se o arquivo CSV foi enviado corretamente pelo front-end
        if (!isset($_FILES['arquivo_csv']) || $_FILES['arquivo_csv']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400); // Bad Request
            echo json_encode(['sucesso' => false, 'erro' => 'Nenhum arquivo válido foi enviado.']);
            exit;
        }

        $caminho_temporario = $_FILES['arquivo_csv']['tmp_name'];
        
        // Chama o nosso "chefão" que insere tudo no banco
        $resultado = processar_importacao_csv($conexao, $caminho_temporario);
        
        if ($resultado['sucesso']) {
            echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(500);
            echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    http_response_code(405);
    echo json_encode(['sucesso' => false, 'erro' => 'Método não permitido']);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'erro' => 'Erro interno no servidor.']);
}