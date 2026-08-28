<?php
declare(strict_types=1);

const SUBQUERY_EQUIPE = "(SELECT eq.nome FROM historico_piloto_equipe hpe JOIN equipes eq ON hpe.equipe_id = eq.id WHERE hpe.piloto_id = p.id ORDER BY hpe.data_inicio DESC LIMIT 1)";

function obter_dados_dashboard(mysqli $conexao, ?int $ano = null): array {
    $anos_disponiveis = [];
    $resAnos = mysqli_query($conexao, "SELECT ano FROM temporadas ORDER BY ano DESC");
    if($resAnos) { while($r = mysqli_fetch_assoc($resAnos)) { $anos_disponiveis[] = $r['ano']; } }

    $whereAno = $ano ? "WHERE ano = " . intval($ano) : "ORDER BY ano DESC LIMIT 1";
    $temporada = mysqli_fetch_assoc(mysqli_query($conexao, "SELECT id, ano FROM temporadas $whereAno"));
    
    if (!$temporada) {
        return ['temporada_atual' => '--', 'total_corridas' => 0, 'lider_campeonato' => null, 'ultima_corrida' => null, 'grafico_top5' => [], 'anos_disponiveis' => $anos_disponiveis];
    }
    
    $temp_id = $temporada['id'];
    $ano_atual = $temporada['ano'];

    $sqlCorr = "SELECT COUNT(DISTINCT id) as total FROM corridas WHERE temporada_id = $temp_id";
    $total_corridas = mysqli_fetch_assoc(mysqli_query($conexao, $sqlCorr))['total'] ?? 0;

    $sqlLider = "SELECT p.nome, SUM(rc.pontos) as pontos, SUM(CASE WHEN rc.posicao_final = 1 THEN 1 ELSE 0 END) as vitorias 
                 FROM resultado_corrida rc JOIN corridas c ON rc.corrida_id = c.id JOIN pilotos p ON rc.piloto_id = p.id 
                 WHERE c.temporada_id = $temp_id GROUP BY p.id ORDER BY pontos DESC LIMIT 1";
    $lider = mysqli_fetch_assoc(mysqli_query($conexao, $sqlLider));

    $sqlLastGp = "SELECT id, nome, data_corrida FROM corridas WHERE temporada_id = $temp_id ORDER BY data_corrida DESC LIMIT 1";
    $lastGp = mysqli_fetch_assoc(mysqli_query($conexao, $sqlLastGp));
    
    if ($lastGp) {
        $id_corrida = $lastGp['id'];
        $sqlPodio = "SELECT p.nome AS piloto_nome, " . SUBQUERY_EQUIPE . " AS equipe_nome, rc.pontos, rc.colocacao_grid 
                     FROM resultado_corrida rc JOIN pilotos p ON rc.piloto_id = p.id 
                     WHERE rc.corrida_id = $id_corrida AND rc.posicao_final IN (1,2,3) ORDER BY rc.posicao_final ASC";
        $resPodio = mysqli_query($conexao, $sqlPodio);
        $lastGp['podio'] = [];
        if($resPodio) { while($row = mysqli_fetch_assoc($resPodio)) { $lastGp['podio'][] = $row; } }
    }

    $grafico = [];
    $sqlGraph = "SELECT p.nome, SUM(rc.pontos) as pts FROM resultado_corrida rc JOIN corridas c ON rc.corrida_id = c.id JOIN pilotos p ON rc.piloto_id = p.id WHERE c.temporada_id = $temp_id GROUP BY p.id ORDER BY pts DESC LIMIT 5";
    $resGraph = mysqli_query($conexao, $sqlGraph);
    if($resGraph) { while($row = mysqli_fetch_assoc($resGraph)) { $grafico[] = $row; } }

    return [
        'anos_disponiveis' => $anos_disponiveis,
        'temporada_atual' => $ano_atual,
        'total_corridas' => $total_corridas,
        'lider_campeonato' => $lider ?: null,
        'ultima_corrida' => $lastGp ?: null,
        'grafico_top5' => $grafico
    ];
}

// NOVO: COMPARAÇÃO CRUZADA POR ANO
function comparar_pilotos(mysqli $conexao, int $id1, ?int $ano1, int $id2, ?int $ano2): ?array {
    $get_stats = function($id, $ano) use ($conexao) {
        $filtroAno = $ano ? "AND t.ano = " . intval($ano) : "";
        $sql = "SELECT p.id, p.nome, p.numero, p.nacionalidade, " . SUBQUERY_EQUIPE . " AS equipe,
                       COALESCE(SUM(rc.pontos), 0) AS pontos,
                       COALESCE(SUM(CASE WHEN rc.posicao_final = 1 THEN 1 ELSE 0 END), 0) AS vitorias,
                       COALESCE(SUM(CASE WHEN rc.posicao_final IN (1, 2, 3) THEN 1 ELSE 0 END), 0) AS podios,
                       COALESCE(SUM(CASE WHEN rc.colocacao_grid = 1 THEN 1 ELSE 0 END), 0) AS poles,
                       COALESCE(SUM(CASE WHEN rc.volta_mais_rapida IS NOT NULL THEN 1 ELSE 0 END), 0) AS voltas_rapidas,
                       COALESCE(AVG(rc.posicao_final), 0) AS posicao_media_chegada,
                       COALESCE(SUM(CASE WHEN rc.status != 'Finished' THEN 1 ELSE 0 END), 0) AS abandonos
                FROM pilotos p
                LEFT JOIN resultado_corrida rc ON rc.piloto_id = p.id
                LEFT JOIN corridas c ON rc.corrida_id = c.id
                LEFT JOIN temporadas t ON c.temporada_id = t.id
                WHERE p.id = $id $filtroAno
                GROUP BY p.id";
        return mysqli_fetch_assoc(mysqli_query($conexao, $sql));
    };

    $piloto1 = $get_stats($id1, $ano1);
    $piloto2 = $get_stats($id2, $ano2);

    if (!$piloto1 || !$piloto2) return null;

    $diferencial = [
        'pontos' => round((float) $piloto1['pontos'] - (float) $piloto2['pontos'], 2),
        'vitorias' => (int) $piloto1['vitorias'] - (int) $piloto2['vitorias'],
        'podios' => (int) $piloto1['podios'] - (int) $piloto2['podios'],
        'poles' => (int) $piloto1['poles'] - (int) $piloto2['poles'],
        'voltas_rapidas' => (int) $piloto1['voltas_rapidas'] - (int) $piloto2['voltas_rapidas'],
        'abandonos' => (int) $piloto1['abandonos'] - (int) $piloto2['abandonos'],
    ];

    return ['piloto1' => $piloto1, 'piloto2' => $piloto2, 'diferencial' => $diferencial];
}

// NOVO: 10 ALGORITMOS MONEYBALL
function calc_moneyball(mysqli $conexao, ?int $ano, string $select_calc, string $order_by, string $having = ""): array {
    $whereAno = $ano ? "AND t.ano = " . intval($ano) : "";
    $sql = "SELECT p.nome, " . SUBQUERY_EQUIPE . " AS equipe, $select_calc AS valor 
            FROM resultado_corrida rc 
            JOIN pilotos p ON rc.piloto_id = p.id 
            JOIN corridas c ON rc.corrida_id = c.id 
            JOIN temporadas t ON c.temporada_id = t.id 
            WHERE 1=1 $whereAno 
            GROUP BY p.id $having ORDER BY $order_by LIMIT 3";
    $res = mysqli_query($conexao, $sql);
    $lista = [];
    if($res) { while($row = mysqli_fetch_assoc($res)){ $lista[] = $row; } }
    return $lista;
}

function responder_perguntas_analiticas(mysqli $conexao, ?int $ano = null): array {
    return [
        ['pergunta' => 'Highest Total Points Accumulation', 'resultado' => calc_moneyball($conexao, $ano, 'SUM(rc.pontos)', 'valor DESC')],
        ['pergunta' => 'Most Podium Finishes / Consistency', 'resultado' => calc_moneyball($conexao, $ano, 'SUM(CASE WHEN rc.posicao_final IN(1,2,3) THEN 1 ELSE 0 END)', 'valor DESC')],
        ['pergunta' => 'One-Lap Pace Dominance', 'resultado' => calc_moneyball($conexao, $ano, 'SUM(CASE WHEN rc.colocacao_grid = 1 THEN 1 ELSE 0 END)', 'valor DESC')],
        ['pergunta' => 'Race Pace & Tyre Degradation Index', 'resultado' => calc_moneyball($conexao, $ano, 'SUM(CASE WHEN rc.volta_mais_rapida IS NOT NULL THEN 1 ELSE 0 END)', 'valor DESC')],
        ['pergunta' => 'Incident Vulnerability (DNFs)', 'resultado' => calc_moneyball($conexao, $ano, 'SUM(CASE WHEN rc.status != \'Finished\' THEN 1 ELSE 0 END)', 'valor DESC')],
        ['pergunta' => 'Overtake Index (Positions Gained)', 'resultado' => calc_moneyball($conexao, $ano, 'SUM(CASE WHEN rc.colocacao_grid > rc.posicao_final THEN (rc.colocacao_grid - rc.posicao_final) ELSE 0 END)', 'valor DESC')],
        ['pergunta' => 'Grid Conversion (Pole to Win %)', 'resultado' => calc_moneyball($conexao, $ano, 'ROUND((SUM(CASE WHEN rc.posicao_final = 1 AND rc.colocacao_grid = 1 THEN 1 ELSE 0 END) / SUM(CASE WHEN rc.colocacao_grid = 1 THEN 1 ELSE 0 END)) * 100, 2)', 'valor DESC', 'HAVING SUM(CASE WHEN rc.colocacao_grid = 1 THEN 1 ELSE 0 END) > 0')],
        ['pergunta' => 'Resilience Index (Points outside Top 5 Start)', 'resultado' => calc_moneyball($conexao, $ano, 'SUM(CASE WHEN rc.colocacao_grid > 5 THEN rc.pontos ELSE 0 END)', 'valor DESC')],
        ['pergunta' => 'Track Adaptability (Wins on Different Tracks)', 'resultado' => calc_moneyball($conexao, $ano, 'COUNT(DISTINCT CASE WHEN rc.posicao_final = 1 THEN c.circuito_id ELSE NULL END)', 'valor DESC')],
        ['pergunta' => 'Overall Scouting Score (Moneyball Formula)', 'resultado' => calc_moneyball($conexao, $ano, 'ROUND((SUM(rc.pontos) * 0.5) + (SUM(CASE WHEN rc.posicao_final = 1 THEN 10 ELSE 0 END)) + (SUM(CASE WHEN rc.colocacao_grid > rc.posicao_final THEN 1 ELSE 0 END)) - (SUM(CASE WHEN rc.status != \'Finished\' THEN 5 ELSE 0 END)), 2)', 'valor DESC')]
    ];
}