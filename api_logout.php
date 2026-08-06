<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/utils.php';
require_once __DIR__ . '/includes/valida_sessao.php';

enviar_headers_padrao();
iniciar_sessao_segura();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder_erro(405, 'Metodo nao permitido.');
}

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $parametros = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $parametros['path'],
        $parametros['domain'],
        $parametros['secure'],
        $parametros['httponly']
    );
}

session_destroy();

responder_json(200, ['mensagem' => 'Logout realizado com sucesso.']);
