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
        return ['sucesso' => false, 'erro' => 'Nao foi possivel abrir o arquivo enviado.'];
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
            return ['sucesso' => false, 'erro' => "Coluna obrigatoria ausente no CSV: {$coluna}"];
        }
        $mapa_indices[$coluna] = $indice;
    }

    $linhas_processadas = 0;
    $linhas_com_erro = 0;
    $erros = [];
    $numero_linha = 1;

    mysqli_begin_transaction($conexao);

    try {
        while (($linha = fgetcsv($handle, 0, ',')) !== false) {
            $numero_linha++;

            if (count($linha) < count(COLUNAS_CSV_ESPERADAS)) {
                $linhas_com_erro++;
                $erros[] = "Linha {$numero_linha}: numero de colunas insuficiente.";
                continue;
            }

            $nome = trim((string) $linha[$mapa_indices['nome']]);
            $equipe = trim((string) $linha[$mapa_indices['equipe']]);
            $numero = inteiro_ou_null($linha[$mapa_indices['numero']]) ?? 0;
            $nacionalidade = trim((string) $linha[$mapa_indices['nacionalidade']]);
            $temporada = inteiro_ou_null($linha[$mapa_indices['temporada']]) ?? 0;
            $pontos = decimal_ou_zero($linha[$mapa_indices['pontos']]);
            $vitorias = inteiro_ou_null($linha[$mapa_indices['vitorias']]) ?? 0;
            $podios = inteiro_ou_null($linha[$mapa_indices['podios']]) ?? 0;
            $poles = inteiro_ou_null($linha[$mapa_indices['poles']]) ?? 0;
            $voltas_rapidas = inteiro_ou_null($linha[$mapa_indices['voltas_rapidas']]) ?? 0;
            $posicao_media = decimal_ou_zero($linha[$mapa_indices['posicao_media_chegada']]);
            $abandonos = inteiro_ou_null($linha[$mapa_indices['abandonos']]) ?? 0;

            if ($nome === '' || $equipe === '' || $temporada === 0) {
                $linhas_com_erro++;
                $erros[] = "Linha {$numero_linha}: dados obrigatorios ausentes.";
                continue;
            }

            $piloto_existente = buscar_piloto_por_nome_equipe_numero($conexao, $nome, $equipe, $numero);
            if ($piloto_existente) {
                $piloto_id = (int) $piloto_existente['id'];
            } else {
                $piloto_id = inserir_piloto($conexao, $nome, $equipe, $numero, $nacionalidade);
            }

            $sql = "INSERT INTO estatisticas
                        (piloto_id, temporada, pontos, vitorias, podios, poles, voltas_rapidas, posicao_media_chegada, abandonos)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        pontos = VALUES(pontos),
                        vitorias = VALUES(vitorias),
                        podios = VALUES(podios),
                        poles = VALUES(poles),
                        voltas_rapidas = VALUES(voltas_rapidas),
                        posicao_media_chegada = VALUES(posicao_media_chegada),
                        abandonos = VALUES(abandonos)";
            $stmt = mysqli_prepare($conexao, $sql);
            mysqli_stmt_bind_param(
                $stmt,
                'iidiiiidi',
                $piloto_id,
                $temporada,
                $pontos,
                $vitorias,
                $podios,
                $poles,
                $voltas_rapidas,
                $posicao_media,
                $abandonos
            );
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $linhas_processadas++;
        }

        mysqli_commit($conexao);
    } catch (Throwable $excecao) {
        mysqli_rollback($conexao);
        fclose($handle);
        return ['sucesso' => false, 'erro' => 'Falha ao processar importacao: ' . $excecao->getMessage()];
    }

    fclose($handle);

    return [
        'sucesso' => true,
        'linhas_processadas' => $linhas_processadas,
        'linhas_com_erro' => $linhas_com_erro,
        'erros' => $erros,
    ];
}
