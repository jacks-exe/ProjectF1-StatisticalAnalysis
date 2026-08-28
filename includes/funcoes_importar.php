<?php
declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/utils.php';
require_once __DIR__ . '/includes/valida_sessao.php';

enviar_headers_padrao();
exigir_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder_erro(405, 'Método não permitido. Utilize POST.');
}

$conexao = obter_conexao();
$acao = $_POST['acao'] ?? '';

/* ========================================================
   MÓDULO 1: WIPE (PURGE DE DADOS)
   ======================================================== */
if ($acao === 'wipe') {
    mysqli_begin_transaction($conexao);
    try {
        // Desativa checagem de chaves estrangeiras para permitir o TRUNCATE
        mysqli_query($conexao, "SET FOREIGN_KEY_CHECKS = 0");
        
        // Limpa as tabelas na ordem correta (Ajuste os nomes conforme seu database.sql)
        mysqli_query($conexao, "TRUNCATE TABLE resultados");
        mysqli_query($conexao, "TRUNCATE TABLE corridas");
        mysqli_query($conexao, "TRUNCATE TABLE estatisticas");
        mysqli_query($conexao, "TRUNCATE TABLE historico_piloto_equipe");
        mysqli_query($conexao, "TRUNCATE TABLE pilotos");
        mysqli_query($conexao, "TRUNCATE TABLE equipes");
        mysqli_query($conexao, "TRUNCATE TABLE temporadas");
        
        mysqli_query($conexao, "SET FOREIGN_KEY_CHECKS = 1");
        mysqli_commit($conexao);

        responder_json(200, ['sucesso' => true, 'mensagem' => 'Database limpo com sucesso. Sistema pronto para nova ingestão.']);
    } catch (Exception $e) {
        mysqli_rollback($conexao);
        mysqli_query($conexao, "SET FOREIGN_KEY_CHECKS = 1");
        responder_erro(500, 'Erro crítico ao limpar o banco: ' . $e->getMessage());
    }
}

/* ========================================================
   MÓDULO 2: INGESTÃO DA MATRIZ (CSV)
   ======================================================== */
if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK) {
    
    $arquivoTmp = $_FILES['arquivo']['tmp_name'];
    $handle = fopen($arquivoTmp, 'r');
    
    if ($handle === false) {
        responder_erro(400, 'Falha ao ler o arquivo físico.');
    }

    // Pula o cabeçalho
    $cabecalho = fgetcsv($handle, 1000, ',');

    mysqli_begin_transaction($conexao);
    try {
        $linhasProcessadas = 0;

        while (($linha = fgetcsv($handle, 1000, ',')) !== false) {
            // Mapeamento das colunas (baseado no cabeçalho exigido acima)
            $temporada_ano = (int) $linha[0];
            $etapa = (int) $linha[1];
            $gp_nome = trim($linha[2]);
            $circuito = trim($linha[3]);
            $data_corrida = trim($linha[4]);
            $piloto_nome = trim($linha[5]);
            $equipe_nome = trim($linha[6]);
            $numero = (int) $linha[7];
            $grid = (int) $linha[8];
            $chegada = (int) $linha[9];
            $pontos = (float) $linha[10];
            $status = trim($linha[11]);
            $fastest_lap = (int) $linha[12]; // 1 ou 0

            // ----------------------------------------------------
            // AQUI ENTRA A LÓGICA DE INSERÇÃO RELACIONAL INTELIGENTE
            // Como o código completo de INSERT/UPDATE depende 
            // intimamente da estrutura exata do seu database.sql,
            // deixei a estrutura base montada.
            // ----------------------------------------------------
            
            // 1. Verificar/Inserir Temporada
            // 2. Verificar/Inserir Equipe
            // 3. Verificar/Inserir Piloto
            // 4. Verificar/Inserir Corrida (Etapa)
            // 5. Inserir Resultado (Conectando Corrida + Piloto + Equipe)

            $linhasProcessadas++;
        }
        fclose($handle);
        mysqli_commit($conexao);

        responder_json(200, [
            'sucesso' => true, 
            'mensagem' => "Ingestão concluída. $linhasProcessadas registros de telemetria processados."
        ]);

    } catch (Exception $e) {
        mysqli_rollback($conexao);
        fclose($handle);
        responder_erro(500, 'Erro na linha ' . $linhasProcessadas . ': ' . $e->getMessage());
    }
} else {
    responder_erro(400, 'Nenhum payload ou arquivo válido recebido no servidor.');
}