<?php
declare(strict_types=1);

function listar_pilotos(mysqli $conexao, array $filtros): array {
    $filtroAno = !empty($filtros['ano']) ? "AND t.ano = " . (int)$filtros['ano'] : "";
    
    $sql = "SELECT p.id, p.nome, p.numero, p.nacionalidade,
                   (SELECT eq.nome FROM historico_piloto_equipe hpe JOIN equipes eq ON hpe.equipe_id = eq.id WHERE hpe.piloto_id = p.id ORDER BY hpe.data_inicio DESC LIMIT 1) AS equipe,
                   COALESCE(SUM(rc.pontos), 0) AS pontos,
                   COALESCE(SUM(CASE WHEN rc.posicao_final = 1 THEN 1 ELSE 0 END), 0) AS vitorias,
                   COALESCE(SUM(CASE WHEN rc.posicao_final IN (1, 2, 3) THEN 1 ELSE 0 END), 0) AS podios,
                   COALESCE(SUM(CASE WHEN rc.colocacao_grid = 1 THEN 1 ELSE 0 END), 0) AS poles,
                   COALESCE(SUM(CASE WHEN rc.colocacao_grid > rc.posicao_final THEN (rc.colocacao_grid - rc.posicao_final) ELSE 0 END), 0) AS posicoes_ganhas
            FROM pilotos p 
            LEFT JOIN resultado_corrida rc ON p.id = rc.piloto_id
            LEFT JOIN corridas c ON rc.corrida_id = c.id
            LEFT JOIN temporadas t ON c.temporada_id = t.id
            WHERE 1=1 $filtroAno";
            
    $tipos = '';
    $valores = [];
    
    if (!empty($filtros['nome'])) { $sql .= " AND p.nome LIKE ?"; $tipos .= 's'; $valores[] = '%' . $filtros['nome'] . '%'; }
    
    $sql .= " GROUP BY p.id ORDER BY pontos DESC, p.nome ASC";
    
    $stmt = mysqli_prepare($conexao, $sql);
    if ($tipos !== '') { mysqli_stmt_bind_param($stmt, $tipos, ...$valores); }
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    
    $lista = [];
    while ($linha = mysqli_fetch_assoc($resultado)) {
        if (!empty($filtros['equipe']) && stripos((string)$linha['equipe'], $filtros['equipe']) === false) continue;
        $lista[] = $linha;
    }
    mysqli_stmt_close($stmt);
    return $lista;
}

function buscar_piloto_por_id(mysqli $conexao, int $id): ?array {
    $sql = "SELECT p.id, p.nome, p.numero, p.nacionalidade, (SELECT eq.nome FROM historico_piloto_equipe hpe JOIN equipes eq ON hpe.equipe_id = eq.id WHERE hpe.piloto_id = p.id ORDER BY hpe.data_inicio DESC LIMIT 1) AS equipe FROM pilotos p WHERE p.id = ?";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $linha = mysqli_fetch_assoc($resultado);
    mysqli_stmt_close($stmt);
    return $linha ?: null;
}