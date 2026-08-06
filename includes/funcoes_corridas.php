<?php
declare(strict_types=1);

function listar_corridas(mysqli $conexao, ?int $temporada_id = null): array
{
    $sql = "SELECT c.id, c.nome, c.data_corrida, c.voltas, 
                   t.ano AS temporada_ano, 
                   circ.nome AS circuito_nome
            FROM corridas c
            JOIN temporadas t ON c.temporada_id = t.id
            JOIN circuitos circ ON c.circuito_id = circ.id ";
    
    if ($temporada_id !== null) {
        $sql .= " WHERE c.temporada_id = ? ORDER BY c.data_corrida ASC";
        $stmt = mysqli_prepare($conexao, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $temporada_id);
    } else {
        $sql .= " ORDER BY c.data_corrida DESC";
        $stmt = mysqli_prepare($conexao, $sql);
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

function buscar_corrida_por_id(mysqli $conexao, int $id): ?array
{
    $sql = "SELECT c.id, c.nome, c.temporada_id, c.circuito_id, c.data_corrida, c.voltas, 
                   t.ano AS temporada_ano, 
                   circ.nome AS circuito_nome
            FROM corridas c
            JOIN temporadas t ON c.temporada_id = t.id
            JOIN circuitos circ ON c.circuito_id = circ.id
            WHERE c.id = ?";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $linha = mysqli_fetch_assoc($resultado);
    mysqli_stmt_close($stmt);
    return $linha ?: null;
}

function inserir_corrida(mysqli $conexao, string $nome, int $temporada_id, int $circuito_id, string $data_corrida, int $voltas): int
{
    $sql = "INSERT INTO corridas (nome, temporada_id, circuito_id, data_corrida, voltas) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, 'siisi', $nome, $temporada_id, $circuito_id, $data_corrida, $voltas);
    mysqli_stmt_execute($stmt);
    $id = (int) mysqli_insert_id($conexao);
    mysqli_stmt_close($stmt);
    return $id;
}

function atualizar_corrida(mysqli $conexao, int $id, string $nome, int $temporada_id, int $circuito_id, string $data_corrida, int $voltas): bool
{
    $sql = "UPDATE corridas SET nome = ?, temporada_id = ?, circuito_id = ?, data_corrida = ?, voltas = ? WHERE id = ?";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, 'siisii', $nome, $temporada_id, $circuito_id, $data_corrida, $voltas, $id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

function deletar_corrida(mysqli $conexao, int $id): bool
{
    $sql = "DELETE FROM corridas WHERE id = ?";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}