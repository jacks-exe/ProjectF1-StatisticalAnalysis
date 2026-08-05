<?php
declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/utils.php';
require_once __DIR__ . '/includes/valida_sessao.php';
require_once __DIR__ . '/includes/funcoes_usuarios.php';

enviar_headers_padrao();
iniciar_sessao_segura();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder_erro(405, 'Metodo nao permitido.');
}

$dados = ler_corpo_json();
$email = trim((string) ($dados['email'] ?? ''));
$senha = (string) ($dados['senha'] ?? '');

if ($email === '' || $senha === '') {
    responder_erro(400, 'Informe email e senha.');
}

$conexao = obter_conexao();
$usuario = buscar_usuario_por_email($conexao, $email);

if (!$usuario || !password_verify($senha, $usuario['senha'])) {
    responder_erro(401, 'Credenciais invalidas.');
}

session_regenerate_id(true);
$_SESSION['usuario_id'] = (int) $usuario['id'];
$_SESSION['nivel_acesso'] = $usuario['nivel_acesso'];
$_SESSION['nome'] = $usuario['nome'];

responder_json(200, [
    'mensagem' => 'Login realizado com sucesso.',
    'usuario' => [
        'id' => (int) $usuario['id'],
        'nome' => $usuario['nome'],
        'email' => $usuario['email'],
        'nivel_acesso' => $usuario['nivel_acesso'],
    ],
]);
