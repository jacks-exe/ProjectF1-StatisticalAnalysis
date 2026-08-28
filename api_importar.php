<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once 'config/db.php'; 
require_once 'includes/utils.php';
require_once 'includes/valida_sessao.php';

exigir_login();

function responder_json_import($dados, $status = 200) {
    http_response_code($status);
    echo json_encode($dados);
    exit;
}

function responder_erro_import($mensagem, $status = 500) {
    responder_json_import(['status' => 'error', 'message' => $mensagem], $status);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder_erro_import('Método não permitido.', 405);
}

$acao = $_POST['acao'] ?? '';
$conn = obter_conexao();

// ==========================================
// AÇÃO 1: WIPE (PURGE DE DADOS)
// ==========================================
if ($acao === 'wipe') {
    mysqli_begin_transaction($conn);
    try {
        $conn->query("SET FOREIGN_KEY_CHECKS = 0;");
        
        // Tabelas exatas presentes no database.sql
        $tabelas = ['voltas', 'resultado_corrida', 'historico_piloto_equipe', 'estatisticas', 'corridas', 'circuitos', 'pilotos', 'equipes', 'temporadas'];
        foreach ($tabelas as $tabela) {
            $conn->query("TRUNCATE TABLE {$tabela};");
        }
        
        $conn->query("SET FOREIGN_KEY_CHECKS = 1;");
        mysqli_commit($conn);
        responder_json_import(['status' => 'success', 'message' => 'Database purged successfully.']);
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $conn->query("SET FOREIGN_KEY_CHECKS = 1;");
        responder_erro_import('Falha ao executar purge: ' . $e->getMessage());
    }
}

// ==========================================
// AÇÃO 2: IMPORT (INGESTÃO DE MATRIZ CSV)
// ==========================================
elseif ($acao === 'import') {
    if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
        responder_erro_import('Nenhum arquivo CSV válido foi enviado.', 400);
    }

    $file = fopen($_FILES['arquivo']['tmp_name'], 'r');
    if (!$file) responder_erro_import('Falha ao abrir o arquivo CSV.');

    $header_esperado = ['temporada','etapa','gp_nome','circuito','data_corrida','piloto','equipe','numero','posicao_largada','posicao_chegada','pontos','status','fastest_lap'];
    $header_csv = fgetcsv($file);
    $header_csv[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $header_csv[0]); // Limpa BOM
    
    if ($header_csv !== $header_esperado) {
        fclose($file);
        responder_erro_import('Cabeçalho do CSV inválido. Estrutura exigida incorreta.', 400);
    }

    mysqli_begin_transaction($conn);
    $linhas_processadas = 0;

    try {
        function getIdOrInsert($conn, $querySelect, $queryInsert, $paramType, $paramValue) {
            $stmt = $conn->prepare($querySelect);
            $stmt->bind_param($paramType, $paramValue);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) return $row['id'];
            
            $stmt = $conn->prepare($queryInsert);
            $stmt->bind_param($paramType, $paramValue);
            $stmt->execute();
            return $stmt->insert_id;
        }

        $qSelTemp = "SELECT id FROM temporadas WHERE ano = ?";
        $qInsTemp = "INSERT INTO temporadas (ano) VALUES (?)";
        
        $qSelCir = "SELECT id FROM circuitos WHERE nome = ?";
        $qInsCir = "INSERT INTO circuitos (nome) VALUES (?)";
        
        $qSelEquipe = "SELECT id FROM equipes WHERE nome = ?";
        $qInsEquipe = "INSERT INTO equipes (nome) VALUES (?)";
        
        $qSelPiloto = "SELECT id FROM pilotos WHERE nome = ?";
        $qInsPiloto = "INSERT INTO pilotos (nome, numero) VALUES (?, ?)";
        
        $qSelCorrida = "SELECT id FROM corridas WHERE temporada_id = ? AND nome = ?";
        $qInsCorrida = "INSERT INTO corridas (temporada_id, circuito_id, nome, data_corrida) VALUES (?, ?, ?, ?)";
        
        $qInsResultado = "INSERT INTO resultado_corrida (corrida_id, piloto_id, colocacao_grid, posicao_final, pontos, status, volta_mais_rapida) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmtResultado = $conn->prepare($qInsResultado);

        $qInsHistEquipe = "INSERT IGNORE INTO historico_piloto_equipe (piloto_id, equipe_id, data_inicio) VALUES (?, ?, ?)";
        $stmtHistEquipe = $conn->prepare($qInsHistEquipe);

        while (($linha = fgetcsv($file)) !== false) {
            list($ano, $etapa, $gp_nome, $circuito, $data_corrida, $piloto_nome, $equipe_nome, $numero, $pos_largada, $pos_chegada, $pontos, $status, $fastest_lap) = $linha;

            $id_temporada = getIdOrInsert($conn, $qSelTemp, $qInsTemp, "i", $ano);
            $id_circuito = getIdOrInsert($conn, $qSelCir, $qInsCir, "s", $circuito);
            $id_equipe = getIdOrInsert($conn, $qSelEquipe, $qInsEquipe, "s", $equipe_nome);
            
            $stmt = $conn->prepare($qSelPiloto);
            $stmt->bind_param("s", $piloto_nome);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $id_piloto = $row['id'];
            } else {
                $stmt = $conn->prepare($qInsPiloto);
                $stmt->bind_param("si", $piloto_nome, $numero);
                $stmt->execute();
                $id_piloto = $stmt->insert_id;
            }

            $stmt = $conn->prepare($qSelCorrida);
            $stmt->bind_param("is", $id_temporada, $gp_nome);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $id_corrida = $row['id'];
            } else {
                $stmt = $conn->prepare($qInsCorrida);
                $stmt->bind_param("iiss", $id_temporada, $id_circuito, $gp_nome, $data_corrida);
                $stmt->execute();
                $id_corrida = $stmt->insert_id;
            }

            $stmtHistEquipe->bind_param("iis", $id_piloto, $id_equipe, $data_corrida);
            $stmtHistEquipe->execute();

            $fastest_lap_val = empty($fastest_lap) || $fastest_lap === '0' ? null : '00:00:01';

            $stmtResultado->bind_param("iiiidss", 
                $id_corrida, 
                $id_piloto, 
                $pos_largada, 
                $pos_chegada, 
                $pontos, 
                $status, 
                $fastest_lap_val
            );
            $stmtResultado->execute();

            $linhas_processadas++;
        }

        mysqli_commit($conn);
        fclose($file);
        
        responder_json_import([
            'status' => 'success', 
            'message' => 'Matrix ingestion complete.',
            'rows_processed' => $linhas_processadas
        ]);

    } catch (Exception $e) {
        mysqli_rollback($conn);
        if($file) fclose($file);
        responder_erro_import('Erro de integridade relacional: ' . $e->getMessage());
    }
} else {
    responder_erro_import('Ação desconhecida.', 400);
}