<?php
declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/utils.php';
require_once __DIR__ . '/includes/valida_sessao.php';
require_once __DIR__ . '/includes/funcoes_importar.php';

enviar_headers_padrao();
exigir_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder_erro(405, 'Metodo nao permitido.');
}

if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
    responder_erro(400, 'Envie o arquivo CSV no campo "arquivo".');
}

$nome_original = $_FILES['arquivo']['name'];
$extensao = strtolower((string) pathinfo($nome_original, PATHINFO_EXTENSION));
if ($extensao !== 'csv') {
    responder_erro(400, 'O arquivo deve estar no formato .csv.');
}

$conexao = obter_conexao();
$resultado = processar_importacao_csv($conexao, $_FILES['arquivo']['tmp_name']);

if (!$resultado['sucesso']) {
    responder_erro(422, $resultado['erro']);
}

responder_json(200, [
    'mensagem' => 'Importacao concluida.',
    'linhas_processadas' => $resultado['linhas_processadas'],
    'linhas_com_erro' => $resultado['linhas_com_erro'],
    'erros' => $resultado['erros'],
]);
