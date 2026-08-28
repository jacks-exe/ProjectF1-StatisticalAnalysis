<?php
declare(strict_types=1);
error_reporting(0);
ini_set('display_errors', '0');

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/utils.php';
require_once __DIR__ . '/includes/valida_sessao.php';
require_once __DIR__ . '/includes/funcoes_estatisticas.php';

enviar_headers_padrao();
exigir_login();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') { responder_erro(405, 'Metodo nao permitido.'); }

$id1 = inteiro_ou_null($_GET['id1'] ?? null);
$ano1 = inteiro_ou_null($_GET['ano1'] ?? null);
$id2 = inteiro_ou_null($_GET['id2'] ?? null);
$ano2 = inteiro_ou_null($_GET['ano2'] ?? null);

if ($id1 === null || $id2 === null) { responder_erro(400, 'Informe id1 e id2 na query string.'); }

$conexao = obter_conexao();
$comparacao = comparar_pilotos($conexao, $id1, $ano1, $id2, $ano2);

if ($comparacao === null) { responder_erro(404, 'Um ou ambos os pilotos nao foram encontrados com telemetria para este ano.'); }

responder_json(200, $comparacao);