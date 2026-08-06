<?php
declare(strict_types=1);

require_once __DIR__ . '/funcoes_pilotos.php';

const COLUNAS_CSV_ESPERADAS = [
    'nome', 'equipe', 'numero', 'nacionalidade', 'temporada',
    'pontos', 'vitorias', 'podios', 'poles', 'voltas_rapidas',
    'posicao_media_chegada', 'abandonos',
];

function processar_importacao_csv(mysqli $conexao, string $caminho_arquivo_temp): array
{
    $handle = fopen($caminho_arquivo_temp, 'r');
    if (!$handle) {
        return ['sucesso' => false, 'erro' => 'Não foi possível abrir o arquivo enviado.'];
    }

    $cabecalho = fgetcsv($handle, 0, ',');
    if ($cabecalho === false) {
        fclose($handle);
        return ['sucesso' => false, 'erro' => 'Arquivo CSV vazio.'];
    }

    $cabecalho = array_map(fn($c) => strtolower(trim($c)), $cabecalho);
    $mapa_indices = [];
    foreach (COLUNAS_CSV_ESPERADAS as $coluna) {
        $indice = array_search($coluna, $cabecalho, true);
        if ($indice === false) {
            fclose($handle);
            return ['sucesso' => false, 'erro' => "Coluna obrigatória ausente no CSV: {$coluna}"];
        }
        $mapa_indices[$coluna] = $indice;
    }

    $linhas_processadas = 0;
    $linhas_com_erro = 0;
    $erros = [];
    $numero_linha = 1;

    // Prepara todos os statements fora do loop para máxima performance
    $stmt_sel_temp = mysqli_prepare($conexao, "SELECT id FROM temporadas WHERE ano = ?");
    $stmt_ins_temp = mysqli_prepare($conexao, "INSERT INTO temporadas (ano) VALUES (?)");

    $stmt_sel_eq = mysqli_prepare($conexao, "SELECT id FROM equipes WHERE nome = ?");
    $stmt_ins_eq = mysqli_prepare($conexao, "INSERT INTO equipes (nome, nacionalidade) VALUES (?, 'N/D')");

    $stmt_ins_hist = mysqli_prepare($conexao, "INSERT IGNORE INTO historico_piloto_equipe (piloto_id, equipe_id, data_inicio) VALUES (?, ?, ?)");

    $stmt_ins_est = mysqli_prepare($conexao, "INSERT INTO estatisticas
                    (piloto_id, temporada_id, pontos, vitorias, podios, poles, voltas_rapidas, posicao_media_chegada, abandonos)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    pontos = VALUES(pontos),
                    vitorias = VALUES(vitorias),
                    podios = VALUES(podios),
                    poles = VALUES(poles),
                    voltas_rapidas = VALUES(voltas_rapidas),
                    posicao_media_chegada = VALUES(posicao_media_chegada),
                    abandonos = VALUES(abandonos)");

    mysqli_begin_transaction($conexao);

    try {
        while (($linha = fgetcsv($handle, 0, ',')) !== false) {
            $numero_linha++;

            if (count($linha) < count(COLUNAS_CSV_ESPERADAS)) {
                $linhas_com_erro++;
                $erros[] = "Linha {$numero_linha}: número de colunas insuficiente.";
                continue;
            }

            $nome = trim((string) $linha[$mapa_indices['nome']]);
            $equipe_nome = trim((string) $linha[$mapa_indices['equipe']]);
            $numero = inteiro_ou_null($linha[$mapa_indices['numero']]) ?? 0;
            $nacionalidade = trim((string) $linha[$mapa_indices['nacionalidade']]);
            $ano = inteiro_ou_null($linha[$mapa_indices['temporada']]) ?? 0;
            $pontos = decimal_ou_zero($linha[$mapa_indices['pontos']]);
            $vitorias = inteiro_ou_null($linha[$mapa_indices['vitorias']]) ?? 0;
            $podios = inteiro_ou_null($linha[$mapa_indices['podios']]) ?? 0;
            $poles = inteiro_ou_null($linha[$mapa_indices['poles']]) ?? 0;
            $voltas_rapidas = inteiro_ou_null($linha[$mapa_indices['voltas_rapidas']]) ?? 0;
            $posicao_media = decimal_ou_zero($linha[$mapa_indices['posicao_media_chegada']]);
            $abandonos = inteiro_ou_null($linha[$mapa_indices['abandonos']]) ?? 0;

            if ($nome === '' || $equipe_nome === '' || $ano === 0) {
                $linhas_com_erro++;
                $erros[] = "Linha {$numero_linha}: dados obrigatórios ausentes.";
                continue;
            }

            // 1. Resolve Temporada
            mysqli_stmt_bind_param($stmt_sel_temp, 'i', $ano);
            mysqli_stmt_execute($stmt_sel_temp);
            $res_temp = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_sel_temp));
            if ($res_temp) {
                $temporada_id = (int) $res_temp['id'];
            } else {
                mysqli_stmt_bind_param($stmt_ins_temp, 'i', $ano);
                mysqli_stmt_execute($stmt_ins_temp);
                $temporada_id = (int) mysqli_insert_id($conexao);
            }

            // 2. Resolve Equipe
            mysqli_stmt_bind_param($stmt_sel_eq, 's', $equipe_nome);
            mysqli_stmt_execute($stmt_sel_eq);
            $res_eq = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_sel_eq));
            if ($res_eq) {
                $equipe_id = (int) $res_eq['id'];
            } else {
                mysqli_stmt_bind_param($stmt_ins_eq, 's', $equipe_nome);
                mysqli_stmt_execute($stmt_ins_eq);
                $equipe_id = (int) mysqli_insert_id($conexao);
            }

            // 3. Resolve Piloto
            $piloto = buscar_piloto_por_nome_numero($conexao, $nome, $numero);
            if ($piloto) {
                $piloto_id = (int) $piloto['id'];
            } else {
                $piloto_id = inserir_piloto($conexao, $nome, $numero, $nacionalidade);
            }

            // 4. Resolve Histórico Piloto-Equipe (Data fictícia baseada no ano da temporada para compor a PK)
            $data_inicio_hist = "{$ano}-01-01";
            mysqli_stmt_bind_param($stmt_ins_hist, 'iis', $piloto_id, $equipe_id, $data_inicio_hist);
            mysqli_stmt_execute($stmt_ins_hist);

            // 5. Insere ou Atualiza as Estatísticas
            mysqli_stmt_bind_param(
                $stmt_ins_est,
                'iidiiiidi',
                $piloto_id,
                $temporada_id,
                $pontos,
                $vitorias,
                $podios,
                $poles,
                $voltas_rapidas,
                $posicao_media,
                $abandonos
            );
            mysqli_stmt_execute($stmt_ins_est);

            $linhas_processadas++;
        }

        mysqli_commit($conexao);
    } catch (Throwable $excecao) {
        mysqli_rollback($conexao);
        fclose($handle);
        return ['sucesso' => false, 'erro' => 'Falha ao processar importação: ' . $excecao->getMessage()];
    }

    fclose($handle);

    return [
        'sucesso' => true,
        'linhas_processadas' => $linhas_processadas,
        'linhas_com_erro' => $linhas_com_erro,
        'erros' => $erros,
    ];
}