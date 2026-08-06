<?php
declare(strict_types=1);

require_once __DIR__ . '/utils.php';

function iniciar_sessao_segura(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function exigir_login(): void
{
    iniciar_sessao_segura();

    if (!isset($_SESSION['usuario_id'])) {
        responder_erro(401, 'Nao autenticado.');
    }
}

function exigir_admin(): void
{
    exigir_login();

    if (!isset($_SESSION['nivel_acesso']) || $_SESSION['nivel_acesso'] !== 'admin') {
        responder_erro(403, 'Acesso restrito a administradores.');
    }
}

function usuario_logado_id(): int
{
    return (int) $_SESSION['usuario_id'];
}
