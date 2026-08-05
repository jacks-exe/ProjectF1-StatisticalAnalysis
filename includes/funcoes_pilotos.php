<?php
declare(strict_types=1);

function listar_pilotos(mysqli $conexao, array $filtros): array
{
    $sql = "SELECT id, nome, equipe, numero, nacionalidade FROM pilotos WHERE 1=1";
    $tipos = '';
    $valores = [];

    if (!empty($filtros['nome'])) {
        $sql .= " AND nome LIKE ?";
        $tipos .= 's';
        $valores[] = '%' . $filtros['nome'] . '%';
    }
    if (!empty($filtros['equipe'])) {
        $sql .= " AND equipe LIKE ?";
        $tipos .= 's';
        $valores[] = '%' . $filtros['equipe'] . '%';
    }
    if (!empty($filtros['nacionalidade'])) {
        $sql .= " AND nacionalidade LIKE ?";
        $tipos .= 's';
        $valores[] = '%' . $filtros['nacionalidade'] . '%';
    }
    if (!empty($filtros['numero'])) {
        $sql .= " AND numero = ?";
        $tipos .= 'i';
        $valores[] = (int) $filtros['numero'];
    }

    $sql .= " ORDER BY nome ASC";

    $stmt = mysqli_prepare($conexao, $sql);
    if ($tipos !== '') {
        mysqli_stmt_bind_param($stmt, $tipos, ...$valores);
    }
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);

    $lista = [];
    while ($linha = mysqli_fetch_assoc($resultado)) {
        $lista[] = $linha;
    }
    mysqli_stmt_close($stmt);
    return $lista;
}

function buscar_piloto_por_id(mysqli $conexao, int $id): ?array
{
    $sql = "SELECT id, nome, equipe, numero, nacionalidade FROM pilotos WHERE id = ?";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $linha = mysqli_fetch_assoc($resultado);
    mysqli_stmt_close($stmt);
    return $linha ?: null;
}

function buscar_piloto_por_nome_equipe_numero(mysqli $conexao, string $nome, string $equipe, int $numero): ?array
{
    $sql = "SELECT id FROM pilotos WHERE nome = ? AND equipe = ? AND numero = ?";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, 'ssi', $nome, $equipe, $numero);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $linha = mysqli_fetch_assoc($resultado);
    mysqli_stmt_close($stmt);
    return $linha ?: null;
}

function inserir_piloto(mysqli $conexao, string $nome, string $equipe, int $numero, string $nacionalidade): int
{
    $sql = "INSERT INTO pilotos (nome, equipe, numero, nacionalidade) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, 'ssis', $nome, $equipe, $numero, $nacionalidade);
    mysqli_stmt_execute($stmt);
    $id = (int) mysqli_insert_id($conexao);
    mysqli_stmt_close($stmt);
    return $id;
}

function atualizar_piloto(mysqli $conexao, int $id, string $nome, string $equipe, int $numero, string $nacionalidade): bool
{
    $sql = "UPDATE pilotos SET nome = ?, equipe = ?, numero = ?, nacionalidade = ? WHERE id = ?";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, 'ssisi', $nome, $equipe, $numero, $nacionalidade, $id);
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
    $sql = "SELECT temporada, pontos, vitorias, podios, poles, voltas_rapidas, posicao_media_chegada, abandonos
            FROM estatisticas WHERE piloto_id = ? ORDER BY temporada DESC";
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
