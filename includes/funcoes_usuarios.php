<?php
declare(strict_types=1);

function listar_usuarios(mysqli $conexao): array
{
    $sql = "SELECT id, nome, email, nivel_acesso, criado_em FROM usuarios ORDER BY nome ASC";
    $resultado = mysqli_query($conexao, $sql);
    $lista = [];
    while ($linha = mysqli_fetch_assoc($resultado)) {
        $lista[] = $linha;
    }
    return $lista;
}

function buscar_usuario_por_id(mysqli $conexao, int $id): ?array
{
    $sql = "SELECT id, nome, email, nivel_acesso, criado_em FROM usuarios WHERE id = ?";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $linha = mysqli_fetch_assoc($resultado);
    mysqli_stmt_close($stmt);
    return $linha ?: null;
}

function buscar_usuario_por_email(mysqli $conexao, string $email): ?array
{
    $sql = "SELECT id, nome, email, senha, nivel_acesso FROM usuarios WHERE email = ?";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $linha = mysqli_fetch_assoc($resultado);
    mysqli_stmt_close($stmt);
    return $linha ?: null;
}

function email_ja_existe(mysqli $conexao, string $email, ?int $ignorar_id = null): bool
{
    if ($ignorar_id !== null) {
        $sql = "SELECT id FROM usuarios WHERE email = ? AND id != ?";
        $stmt = mysqli_prepare($conexao, $sql);
        mysqli_stmt_bind_param($stmt, 'si', $email, $ignorar_id);
    } else {
        $sql = "SELECT id FROM usuarios WHERE email = ?";
        $stmt = mysqli_prepare($conexao, $sql);
        mysqli_stmt_bind_param($stmt, 's', $email);
    }
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $existe = mysqli_num_rows($resultado) > 0;
    mysqli_stmt_close($stmt);
    return $existe;
}

function inserir_usuario(mysqli $conexao, string $nome, string $email, string $senha, string $nivel_acesso): int
{
    $hash = password_hash($senha, PASSWORD_BCRYPT);
    $sql = "INSERT INTO usuarios (nome, email, senha, nivel_acesso) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, 'ssss', $nome, $email, $hash, $nivel_acesso);
    mysqli_stmt_execute($stmt);
    $id = (int) mysqli_insert_id($conexao);
    mysqli_stmt_close($stmt);
    return $id;
}

function atualizar_usuario(mysqli $conexao, int $id, string $nome, string $email, string $nivel_acesso, ?string $senha): bool
{
    if ($senha !== null && $senha !== '') {
        $hash = password_hash($senha, PASSWORD_BCRYPT);
        $sql = "UPDATE usuarios SET nome = ?, email = ?, nivel_acesso = ?, senha = ? WHERE id = ?";
        $stmt = mysqli_prepare($conexao, $sql);
        mysqli_stmt_bind_param($stmt, 'ssssi', $nome, $email, $nivel_acesso, $hash, $id);
    } else {
        $sql = "UPDATE usuarios SET nome = ?, email = ?, nivel_acesso = ? WHERE id = ?";
        $stmt = mysqli_prepare($conexao, $sql);
        mysqli_stmt_bind_param($stmt, 'sssi', $nome, $email, $nivel_acesso, $id);
    }
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

function deletar_usuario(mysqli $conexao, int $id): bool
{
    $sql = "DELETE FROM usuarios WHERE id = ?";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}
