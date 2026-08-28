<?php
declare(strict_types=1);
error_reporting(0);
ini_set('display_errors', '0');

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/valida_sessao.php';

exigir_login();
header('Content-Type: application/json; charset=utf-8');

$ano = isset($_GET['ano']) && $_GET['ano'] !== '' ? (int)$_GET['ano'] : null;
$whereAno = $ano ? "WHERE t.ano = $ano" : "";

$sql = "SELECT c.id, c.nome, c.data_corrida, t.ano AS temporada_ano, circ.nome AS circuito_nome,
        (SELECT p.nome FROM resultado_corrida rc JOIN pilotos p ON rc.piloto_id = p.id WHERE rc.corrida_id = c.id ORDER BY rc.posicao_final ASC LIMIT 1) AS vencedor
        FROM corridas c JOIN temporadas t ON c.temporada_id = t.id JOIN circuitos circ ON c.circuito_id = circ.id 
        $whereAno ORDER BY c.data_corrida ASC";

$res = mysqli_query(obter_conexao(), $sql);
$dados = [];
if($res) { while($row = mysqli_fetch_assoc($res)) $dados[] = $row; }
echo json_encode(['sucesso' => true, 'dados' => $dados]);