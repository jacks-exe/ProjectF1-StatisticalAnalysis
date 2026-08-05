<?php
declare(strict_types=1);

function enviar_headers_padrao(): void
{
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Allow-Credentials: true');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

function responder_json(int $status, array $dados): void
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

function responder_erro(int $status, string $mensagem): void
{
    responder_json($status, ['erro' => $mensagem]);
}

function ler_corpo_json(): array
{
    $corpo = file_get_contents('php://input');
    $dados = json_decode($corpo, true);
    return is_array($dados) ? $dados : [];
}

function inteiro_ou_null(mixed $valor): ?int
{
    if ($valor === null || $valor === '') {
        return null;
    }
    return (int) $valor;
}

function decimal_ou_zero(mixed $valor): float
{
    if ($valor === null || $valor === '') {
        return 0.0;
    }
    return (float) $valor;
}
