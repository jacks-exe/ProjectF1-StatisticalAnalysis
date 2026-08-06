<?php
declare(strict_types=1);

function listar_circuitos(mysqli $conexao): array
{
    $sql = "SELECT id, nome, pais, cidade, comprimento_km FROM circuitos ORDER BY nome ASC";
    $resultado = mysqli_query($conexao, $sql);
    
    $lista = [];
    while ($linha = mysqli_fetch_assoc($resultado)) {
        $lista[] = $linha;
    }
    return $lista;
}

function buscar_circuito_por_id(mysqli $conexao, int $id): ?array
{
    $sql = "SELECT id, nome, pais, cidade, comprimento_km FROM circuitos WHERE id = ?";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $linha = mysqli_fetch_assoc($resultado);
    mysqli_stmt_close($stmt);
    return $linha ?: null;
}

function inserir_circuito(mysqli $conexao, string $nome, string $pais, string $cidade, float $comprimento_km): int
{
    $sql = "INSERT INTO circuitos (nome, pais, cidade, comprimento_km) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, 'sssd', $nome, $pais, $cidade, $comprimento_km);
    mysqli_stmt_execute($stmt);
    $id = (int) mysqli_insert_id($conexao);
    mysqli_stmt_close($stmt);
    return $id;
}

function atualizar_circuito(mysqli $conexao, int $id, string $nome, string $pais, string $cidade, float $comprimento_km): bool
{
    $sql = "UPDATE circuitos SET nome = ?, pais = ?, cidade = ?, comprimento_km = ? WHERE id = ?";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, 'sssdi', $nome, $pais, $cidade, $comprimento_km, $id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

function deletar_circuito(mysqli $conexao, int $id): bool
{
    $sql = "DELETE FROM circuitos WHERE id = ?";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}