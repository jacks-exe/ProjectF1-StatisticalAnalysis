<?php
declare(strict_types=1);

function listar_pilotos(mysqli $conexao, array $filtros): array
{
    // Usa uma subquery para buscar a equipe mais recente baseada na data de início
    $sql = "SELECT p.id, p.nome, p.numero, p.nacionalidade,
                   (SELECT eq.nome FROM historico_piloto_equipe h 
                    JOIN equipes eq ON h.equipe_id = eq.id 
                    WHERE h.piloto_id = p.id ORDER BY h.data_inicio DESC LIMIT 1) AS equipe
            FROM pilotos p WHERE 1=1";
    
    $tipos = '';
    $valores = [];

    if (!empty($filtros['nome'])) {
        $sql .= " AND p.nome LIKE ?";
        $tipos .= 's';
        $valores[] = '%' . $filtros['nome'] . '%';
    }
    if (!empty($filtros['nacionalidade'])) {
        $sql .= " AND p.nacionalidade LIKE ?";
        $tipos .= 's';
        $valores[] = '%' . $filtros['nacionalidade'] . '%';
    }
    if (!empty($filtros['numero'])) {
        $sql .= " AND p.numero = ?";
        $tipos .= 'i';
        $valores[] = (int) $filtros['numero'];
    }

    $sql .= " ORDER BY p.nome ASC";

    $stmt = mysqli_prepare($conexao, $sql);
    if ($tipos !== '') {
        mysqli_stmt_bind_param($stmt, $tipos, ...$valores);
    }
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);

    $lista = [];
    while ($linha = mysqli_fetch_assoc($resultado)) {
        // Aplica o filtro de equipe no PHP devido à subquery dinâmica
        if (!empty($filtros['equipe']) && stripos((string)$linha['equipe'], $filtros['equipe']) === false) {
            continue;
        }
        $lista[] = $linha;
    }
    mysqli_stmt_close($stmt);
    return $lista;
}

function buscar_piloto_por_id(mysqli $conexao, int $id): ?array
{
    $sql = "SELECT p.id, p.nome, p.numero, p.nacionalidade,
                   (SELECT eq.nome FROM historico_piloto_equipe h 
                    JOIN equipes eq ON h.equipe_id = eq.id 
                    WHERE h.piloto_id = p.id ORDER BY h.data_inicio DESC LIMIT 1) AS equipe
            FROM pilotos p WHERE p.id = ?";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $linha = mysqli_fetch_assoc($resultado);
    mysqli_stmt_close($stmt);
    return $linha ?: null;
}

function buscar_piloto_por_nome_numero(mysqli $conexao, string $nome, int $numero): ?array
{
    $sql = "SELECT id FROM pilotos WHERE nome = ? AND numero = ?";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, 'si', $nome, $numero);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $linha = mysqli_fetch_assoc($resultado);
    mysqli_stmt_close($stmt);
    return $linha ?: null;
}

function inserir_piloto(mysqli $conexao, string $nome, int $numero, string $nacionalidade): int
{
    $sql = "INSERT INTO pilotos (nome, numero, nacionalidade) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, 'sis', $nome, $numero, $nacionalidade);
    mysqli_stmt_execute($stmt);
    $id = (int) mysqli_insert_id($conexao);
    mysqli_stmt_close($stmt);
    return $id;
}

function atualizar_piloto(mysqli $conexao, int $id, string $nome, int $numero, string $nacionalidade): bool
{
    $sql = "UPDATE pilotos SET nome = ?, numero = ?, nacionalidade = ? WHERE id = ?";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, 'sisi', $nome, $numero, $nacionalidade, $id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

function deletar_piloto(mysqli $conexao, int $id): bool
{
    $sql = "DELETE FROM pilotos WHERE id = ?";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

function listar_estatisticas_do_piloto(mysqli $conexao, int $piloto_id): array
{
    $sql = "SELECT t.ano AS temporada, e.pontos, e.vitorias, e.podios, e.poles, 
                   e.voltas_rapidas, e.posicao_media_chegada, e.abandonos
            FROM estatisticas e 
            JOIN temporadas t ON e.temporada_id = t.id 
            WHERE e.piloto_id = ? ORDER BY t.ano DESC";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $piloto_id);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $lista = [];
    while ($linha = mysqli_fetch_assoc($resultado)) {
        $lista[] = $linha;
    }
    mysqli_stmt_close($stmt);
    return $lista;
}