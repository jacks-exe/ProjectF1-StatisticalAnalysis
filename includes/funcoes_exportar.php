<?php
declare(strict_types=1);

function exportar_banco_para_csv(mysqli $conexao): void {
    // Monta a query para recriar a matriz corrida a corrida
    $sql = "
        SELECT 
            t.ano AS temporada,
            c.etapa,
            c.nome AS gp_nome,
            circ.nome AS circuito,
            c.data_corrida,
            p.nome AS piloto,
            (SELECT eq.nome FROM historico_piloto_equipe hpe 
             JOIN equipes eq ON hpe.equipe_id = eq.id 
             WHERE hpe.piloto_id = p.id 
             ORDER BY hpe.data_inicio DESC LIMIT 1) AS equipe,
            p.numero,
            rc.colocacao_grid AS posicao_largada,
            rc.posicao_final AS posicao_chegada,
            rc.pontos,
            rc.status,
            rc.volta_mais_rapida AS fastest_lap
        FROM resultado_corrida rc
        JOIN corridas c ON rc.corrida_id = c.id
        JOIN temporadas t ON c.temporada_id = t.id
        JOIN circuitos circ ON c.circuito_id = circ.id
        JOIN pilotos p ON rc.piloto_id = p.id
        ORDER BY t.ano DESC, c.etapa ASC, rc.posicao_final ASC
    ";

    $resultado = mysqli_query($conexao, $sql);

    if (!$resultado) {
        http_response_code(500);
        echo json_encode(['erro' => 'Falha ao extrair dados para exportação.']);
        exit;
    }

    // Configura os headers para forçar o download do CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="pitwall_telemetry_' . date('Ymd_His') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Abre o output em memória
    $output = fopen('php://output', 'w');
    
    // Insere BOM para garantir compatibilidade UTF-8 no Excel
    fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));

    // Imprime o cabeçalho idêntico ao modelo de importação
    fputcsv($output, [
        'temporada', 'etapa', 'gp_nome', 'circuito', 'data_corrida',
        'piloto', 'equipe', 'numero', 'posicao_largada', 'posicao_chegada',
        'pontos', 'status', 'fastest_lap'
    ]);

    // Imprime os dados linha a linha
    while ($linha = mysqli_fetch_assoc($resultado)) {
        fputcsv($output, $linha);
    }

    fclose($output);
    exit;
}