<?php
declare(strict_types=1);
error_reporting(0);
ini_set('display_errors', '0');

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/valida_sessao.php';

exigir_login();
header('Content-Type: application/json; charset=utf-8');

$res = mysqli_query(obter_conexao(), "SELECT DISTINCT ano FROM temporadas ORDER BY ano DESC");
$anos = [];
if ($res) { while($row = mysqli_fetch_assoc($res)) { $anos[] = (int)$row['ano']; } }
echo json_encode(['sucesso' => true, 'dados' => $anos]);