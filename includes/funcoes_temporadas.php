<?php
declare(strict_types=1);

function listar_temporadas(mysqli $conexao): array
{
    $sql = "SELECT id, ano, data_inicio, data_fim FROM temporadas ORDER BY ano DESC";
    $resultado = mysqli_query($conexao, $sql);
    
    $lista = [];
    while ($linha = mysqli_fetch_assoc($resultado)) {
        $lista[] = $linha;
    }
    return $lista;
}

function buscar_temporada_por_id(mysqli $conexao, int $id): ?array
{
    $sql = "SELECT id, ano, data_inicio, data_fim FROM temporadas WHERE id = ?";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $linha = mysqli_fetch_assoc($resultado);
    mysqli_stmt_close($stmt);
    return $linha ?: null;
}

function inserir_temporada(mysqli $conexao, int $ano, string $data_inicio, string $data_fim): int
{
    $sql = "INSERT INTO temporadas (ano, data_inicio, data_fim) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conexao, $sql);
    // Datas são tratadas como strings ('s') no binding do MySQLi
    mysqli_stmt_bind_param($stmt, 'iss', $ano, $data_inicio, $data_fim);
    mysqli_stmt_execute($stmt);
    $id = (int) mysqli_insert_id($conexao);
    mysqli_stmt_close($stmt);
    return $id;
}

function atualizar_temporada(mysqli $conexao, int $id, int $ano, string $data_inicio, string $data_fim): bool
{
    $sql = "UPDATE temporadas SET ano = ?, data_inicio = ?, data_fim = ? WHERE id = ?";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, 'issi', $ano, $data_inicio, $data_fim, $id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

function deletar_temporada(mysqli $conexao, int $id): bool
{
    $sql = "DELETE FROM temporadas WHERE id = ?";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}