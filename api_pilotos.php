<?php
declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/utils.php';
require_once __DIR__ . '/includes/valida_sessao.php';
require_once __DIR__ . '/includes/funcoes_pilotos.php';

enviar_headers_padrao();

$conexao = obter_conexao();
$metodo = $_SERVER['REQUEST_METHOD'];

switch ($metodo) {
    case 'GET':
        exigir_login();
        $id = inteiro_ou_null($_GET['id'] ?? null);
        if ($id !== null) {
            $piloto = buscar_piloto_por_id($conexao, $id);
            if (!$piloto) {
                responder_erro(404, 'Piloto nao encontrado.');
            }
            $piloto['estatisticas'] = listar_estatisticas_do_piloto($conexao, $id);
            responder_json(200, ['piloto' => $piloto]);
        }

        $filtros = [
            'nome' => $_GET['nome'] ?? null,
            'equipe' => $_GET['equipe'] ?? null,
            'nacionalidade' => $_GET['nacionalidade'] ?? null,
            'numero' => $_GET['numero'] ?? null,
        ];
        responder_json(200, ['pilotos' => listar_pilotos($conexao, $filtros)]);
        break;

    case 'POST':
        exigir_admin();
        $dados = ler_corpo_json();
        $nome = trim((string) ($dados['nome'] ?? ''));
        $equipe = trim((string) ($dados['equipe'] ?? ''));
        $numero = inteiro_ou_null($dados['numero'] ?? null) ?? 0;
        $nacionalidade = trim((string) ($dados['nacionalidade'] ?? ''));

        if ($nome === '' || $equipe === '' || $nacionalidade === '') {
            responder_erro(400, 'Nome, equipe e nacionalidade sao obrigatorios.');
        }

        $id = inserir_piloto($conexao, $nome, $equipe, $numero, $nacionalidade);
        responder_json(201, ['mensagem' => 'Piloto criado.', 'id' => $id]);
        break;

    case 'PUT':
        exigir_admin();
        $dados = ler_corpo_json();
        $id = inteiro_ou_null($dados['id'] ?? null);
        $nome = trim((string) ($dados['nome'] ?? ''));
        $equipe = trim((string) ($dados['equipe'] ?? ''));
        $numero = inteiro_ou_null($dados['numero'] ?? null) ?? 0;
        $nacionalidade = trim((string) ($dados['nacionalidade'] ?? ''));

        if ($id === null || $nome === '' || $equipe === '' || $nacionalidade === '') {
            responder_erro(400, 'Id, nome, equipe e nacionalidade sao obrigatorios.');
        }
        if (!buscar_piloto_por_id($conexao, $id)) {
            responder_erro(404, 'Piloto nao encontrado.');
        }

        atualizar_piloto($conexao, $id, $nome, $equipe, $numero, $nacionalidade);
        responder_json(200, ['mensagem' => 'Piloto atualizado.']);
        break;

    case 'DELETE':
        exigir_admin();
        $id = inteiro_ou_null($_GET['id'] ?? null);
        if ($id === null) {
            responder_erro(400, 'Id obrigatorio.');
        }
        if (!buscar_piloto_por_id($conexao, $id)) {
            responder_erro(404, 'Piloto nao encontrado.');
        }
        deletar_piloto($conexao, $id);
        responder_json(200, ['mensagem' => 'Piloto excluido.']);
        break;

    default:
        responder_erro(405, 'Metodo nao permitido.');
}
