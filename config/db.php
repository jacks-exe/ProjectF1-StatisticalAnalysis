<?php
declare(strict_types=1);

const DB_HOST = '127.0.0.1';
const DB_USER = 'root';
const DB_PASS = '';
const DB_NAME = 'moneyball';
const DB_PORT = 3306;

function obter_conexao(): mysqli
{
    static $conexao = null;

    if ($conexao !== null) {
        return $conexao;
    }

    mysqli_report(MYSQLI_REPORT_OFF);
    $conexao = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

    if (!$conexao) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['erro' => 'Falha na conexao com o banco de dados.']);
        exit;
    }

    mysqli_set_charset($conexao, 'utf8mb4');

    return $conexao;
}
