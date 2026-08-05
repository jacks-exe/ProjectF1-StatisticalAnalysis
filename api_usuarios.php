<?php
declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/utils.php';
require_once __DIR__ . '/includes/valida_sessao.php';
require_once __DIR__ . '/includes/funcoes_usuarios.php';

enviar_headers_padrao();
require_once __DIR__ . '/includes/valida_sessao.php';
exigir_admin();

$conexao = obter_conexao();
$metodo = $_SERVER['REQUEST_METHOD'];

switch ($metodo) {
    case 'GET':
        $id = inteiro_ou_null($_GET['id'] ?? null);
        if ($id !== null) {
            $usuario = buscar_usuario_por_id($conexao, $id);
            if (!$usuario) {
                responder_erro(404, 'Usuario nao encontrado.');
            }
            responder_json(200, ['usuario' => $usuario]);
        }
        responder_json(200, ['usuarios' => listar_usuarios($conexao)]);
        break;

    case 'POST':
        $dados = ler_corpo_json();
        $nome = trim((string) ($dados['nome'] ?? ''));
        $email = trim((string) ($dados['email'] ?? ''));
        $senha = (string) ($dados['senha'] ?? '');
        $nivel_acesso = (string) ($dados['nivel_acesso'] ?? 'comum');

        if ($nome === '' || $email === '' || $senha === '') {
            responder_erro(400, 'Nome, email e senha sao obrigatorios.');
        }
        if (!in_array($nivel_acesso, ['admin', 'comum'], true)) {
            responder_erro(400, 'Nivel de acesso invalido.');
        }
        if (email_ja_existe($conexao, $email)) {
            responder_erro(409, 'Ja existe um usuario com este email.');
        }

        $id = inserir_usuario($conexao, $nome, $email, $senha, $nivel_acesso);
        responder_json(201, ['mensagem' => 'Usuario criado.', 'id' => $id]);
        break;

    case 'PUT':
        $dados = ler_corpo_json();
        $id = inteiro_ou_null($dados['id'] ?? null);
        $nome = trim((string) ($dados['nome'] ?? ''));
        $email = trim((string) ($dados['email'] ?? ''));
        $nivel_acesso = (string) ($dados['nivel_acesso'] ?? 'comum');
        $senha = isset($dados['senha']) ? (string) $dados['senha'] : null;

        if ($id === null || $nome === '' || $email === '') {
            responder_erro(400, 'Id, nome e email sao obrigatorios.');
        }
        if (!in_array($nivel_acesso, ['admin', 'comum'], true)) {
            responder_erro(400, 'Nivel de acesso invalido.');
        }
        if (!buscar_usuario_por_id($conexao, $id)) {
            responder_erro(404, 'Usuario nao encontrado.');
        }
        if (email_ja_existe($conexao, $email, $id)) {
            responder_erro(409, 'Ja existe outro usuario com este email.');
        }

        atualizar_usuario($conexao, $id, $nome, $email, $nivel_acesso, $senha);
        responder_json(200, ['mensagem' => 'Usuario atualizado.']);
        break;

    case 'DELETE':
        $id = inteiro_ou_null($_GET['id'] ?? null);
        if ($id === null) {
            responder_erro(400, 'Id obrigatorio.');
        }
        if (!buscar_usuario_por_id($conexao, $id)) {
            responder_erro(404, 'Usuario nao encontrado.');
        }
        if ($id === usuario_logado_id()) {
            responder_erro(400, 'Nao e possivel excluir o proprio usuario logado.');
        }
        deletar_usuario($conexao, $id);
        responder_json(200, ['mensagem' => 'Usuario excluido.']);
        break;

    default:
        responder_erro(405, 'Metodo nao permitido.');
}
