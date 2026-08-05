<?php
declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/utils.php';
require_once __DIR__ . '/includes/valida_sessao.php';
require_once __DIR__ . '/includes/funcoes_estatisticas.php';

enviar_headers_padrao();
exigir_login();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    responder_erro(405, 'Metodo nao permitido.');
}

$conexao = obter_conexao();
responder_json(200, obter_dados_dashboard($conexao));
