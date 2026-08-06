<?php
declare(strict_types=1);

function obter_dados_dashboard(mysqli $conexao): array
{
    $total_pilotos = mysqli_fetch_assoc(mysqli_query($conexao, "SELECT COUNT(*) AS total FROM pilotos"))['total'];
    $total_equipes = mysqli_fetch_assoc(mysqli_query($conexao, "SELECT COUNT(DISTINCT equipe) AS total FROM pilotos"))['total'];
    $total_temporadas = mysqli_fetch_assoc(mysqli_query($conexao, "SELECT COUNT(DISTINCT temporada) AS total FROM estatisticas"))['total'];

    $sql_lider = "SELECT p.id, p.nome, p.equipe, SUM(e.pontos) AS total_pontos
                  FROM pilotos p JOIN estatisticas e ON e.piloto_id = p.id
                  GROUP BY p.id, p.nome, p.equipe
                  ORDER BY total_pontos DESC LIMIT 1";
    $lider = mysqli_fetch_assoc(mysqli_query($conexao, $sql_lider));

    $sql_vitorias = "SELECT SUM(vitorias) AS total FROM estatisticas";
    $total_vitorias = mysqli_fetch_assoc(mysqli_query($conexao, $sql_vitorias))['total'];

    $sql_media_pontos = "SELECT p.id, p.nome, p.equipe, SUM(e.pontos) AS total_pontos
                          FROM pilotos p JOIN estatisticas e ON e.piloto_id = p.id
                          GROUP BY p.id, p.nome, p.equipe
                          ORDER BY total_pontos DESC";
    $resultado_media = mysqli_query($conexao, $sql_media_pontos);
    $soma = 0.0;
    $quantidade = 0;
    while ($linha = mysqli_fetch_assoc($resultado_media)) {
        $soma += (float) $linha['total_pontos'];
        $quantidade++;
    }
    $media_pontos = $quantidade > 0 ? round($soma / $quantidade, 2) : 0;

    return [
        'total_pilotos' => (int) $total_pilotos,
        'total_equipes' => (int) $total_equipes,
        'total_temporadas' => (int) $total_temporadas,
        'total_vitorias_registradas' => (int) $total_vitorias,
        'media_pontos_por_piloto' => $media_pontos,
        'piloto_lider' => $lider ?: null,
    ];
}

function comparar_pilotos(mysqli $conexao, int $id1, int $id2): ?array
{
    $sql = "SELECT p.id, p.nome, p.equipe, p.numero, p.nacionalidade,
                   COALESCE(SUM(e.pontos), 0) AS pontos,
                   COALESCE(SUM(e.vitorias), 0) AS vitorias,
                   COALESCE(SUM(e.podios), 0) AS podios,
                   COALESCE(SUM(e.poles), 0) AS poles,
                   COALESCE(SUM(e.voltas_rapidas), 0) AS voltas_rapidas,
                   COALESCE(AVG(e.posicao_media_chegada), 0) AS posicao_media_chegada,
                   COALESCE(SUM(e.abandonos), 0) AS abandonos
            FROM pilotos p
            LEFT JOIN estatisticas e ON e.piloto_id = p.id
            WHERE p.id = ?
            GROUP BY p.id, p.nome, p.equipe, p.numero, p.nacionalidade";

    $stmt = mysqli_prepare($conexao, $sql);

    mysqli_stmt_bind_param($stmt, 'i', $id1);
    mysqli_stmt_execute($stmt);
    $piloto1 = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    mysqli_stmt_bind_param($stmt, 'i', $id2);
    mysqli_stmt_execute($stmt);
    $piloto2 = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    mysqli_stmt_close($stmt);

    if (!$piloto1 || !$piloto2) {
        return null;
    }

    $diferencial = [
        'pontos' => round((float) $piloto1['pontos'] - (float) $piloto2['pontos'], 2),
        'vitorias' => (int) $piloto1['vitorias'] - (int) $piloto2['vitorias'],
        'podios' => (int) $piloto1['podios'] - (int) $piloto2['podios'],
        'poles' => (int) $piloto1['poles'] - (int) $piloto2['poles'],
        'voltas_rapidas' => (int) $piloto1['voltas_rapidas'] - (int) $piloto2['voltas_rapidas'],
        'posicao_media_chegada' => round((float) $piloto1['posicao_media_chegada'] - (float) $piloto2['posicao_media_chegada'], 2),
        'abandonos' => (int) $piloto1['abandonos'] - (int) $piloto2['abandonos'],
    ];

    return [
        'piloto1' => $piloto1,
        'piloto2' => $piloto2,
        'diferencial' => $diferencial,
    ];
}

function responder_perguntas_analiticas(mysqli $conexao): array
{
    return [
        'q1_melhor_desempenho' => pergunta_melhor_desempenho($conexao),
        'q2_pior_desempenho' => pergunta_pior_desempenho($conexao),
        'q3_top5_pilotos' => pergunta_top5_pilotos($conexao),
        'q4_maior_regularidade' => pergunta_maior_regularidade($conexao),
        'q5_estatistica_mais_importante' => pergunta_estatistica_mais_importante(),
        'q6_maior_taxa_conversao' => pergunta_maior_taxa_conversao($conexao),
        'q7_mais_rapido_em_corrida' => pergunta_mais_rapido($conexao),
        'q8_pior_confiabilidade' => pergunta_pior_confiabilidade($conexao),
    ];
}

function pergunta_melhor_desempenho(mysqli $conexao): array
{
    $sql = "SELECT p.id, p.nome, p.equipe, SUM(e.pontos) AS total_pontos
            FROM pilotos p JOIN estatisticas e ON e.piloto_id = p.id
            GROUP BY p.id, p.nome, p.equipe
            ORDER BY total_pontos DESC LIMIT 1";
    $linha = mysqli_fetch_assoc(mysqli_query($conexao, $sql));
    return [
        'resposta' => $linha,
        'explicacao' => 'Piloto com o maior somatorio de pontos em todas as temporadas registradas.',
    ];
}

function pergunta_pior_desempenho(mysqli $conexao): array
{
    $sql = "SELECT p.id, p.nome, p.equipe, SUM(e.pontos) AS total_pontos
            FROM pilotos p JOIN estatisticas e ON e.piloto_id = p.id
            GROUP BY p.id, p.nome, p.equipe
            ORDER BY total_pontos ASC LIMIT 1";
    $linha = mysqli_fetch_assoc(mysqli_query($conexao, $sql));
    return [
        'resposta' => $linha,
        'explicacao' => 'Piloto com o menor somatorio de pontos em todas as temporadas registradas.',
    ];
}

function pergunta_top5_pilotos(mysqli $conexao): array
{
    $sql = "SELECT p.id, p.nome, p.equipe, SUM(e.pontos) AS total_pontos
            FROM pilotos p JOIN estatisticas e ON e.piloto_id = p.id
            GROUP BY p.id, p.nome, p.equipe
            ORDER BY total_pontos DESC LIMIT 5";
    $resultado = mysqli_query($conexao, $sql);
    $lista = [];
    while ($linha = mysqli_fetch_assoc($resultado)) {
        $lista[] = $linha;
    }
    return [
        'resposta' => $lista,
        'explicacao' => 'Ranking calculado pela soma total de pontos de cada piloto em ordem decrescente, limitado aos 5 primeiros colocados.',
    ];
}

function pergunta_maior_regularidade(mysqli $conexao): array
{
    $sql = "SELECT p.id, p.nome, p.equipe,
                   AVG(e.posicao_media_chegada) AS media_posicao,
                   SUM(e.abandonos) AS total_abandonos
            FROM pilotos p JOIN estatisticas e ON e.piloto_id = p.id
            GROUP BY p.id, p.nome, p.equipe
            ORDER BY media_posicao ASC, total_abandonos ASC LIMIT 1";
    $linha = mysqli_fetch_assoc(mysqli_query($conexao, $sql));
    return [
        'resposta' => $linha,
        'explicacao' => 'Formula: menor media de posicao_media_chegada combinada com o menor numero de abandonos, ordenados nessa prioridade.',
    ];
}

function pergunta_estatistica_mais_importante(): array
{
    return [
        'resposta' => 'pontos',
        'explicacao' => 'A metrica "pontos" e a mais importante pois e o criterio oficial usado pela FIA para definir o Campeao Mundial de Pilotos ao final da temporada.',
    ];
}

function pergunta_maior_taxa_conversao(mysqli $conexao): array
{
    $sql = "SELECT p.id, p.nome, p.equipe,
                   SUM(e.vitorias) AS total_vitorias,
                   SUM(e.poles) AS total_poles,
                   CASE WHEN SUM(e.poles) > 0 THEN SUM(e.vitorias) / SUM(e.poles) ELSE 0 END AS taxa_conversao
            FROM pilotos p JOIN estatisticas e ON e.piloto_id = p.id
            GROUP BY p.id, p.nome, p.equipe
            HAVING total_poles > 0
            ORDER BY taxa_conversao DESC LIMIT 1";
    $linha = mysqli_fetch_assoc(mysqli_query($conexao, $sql));
    return [
        'resposta' => $linha,
        'explicacao' => 'Taxa de conversao calculada como vitorias dividido por poles, indicando o piloto mais eficiente em transformar pole position em vitoria.',
    ];
}

function pergunta_mais_rapido(mysqli $conexao): array
{
    $sql = "SELECT p.id, p.nome, p.equipe, SUM(e.voltas_rapidas) AS total_voltas_rapidas
            FROM pilotos p JOIN estatisticas e ON e.piloto_id = p.id
            GROUP BY p.id, p.nome, p.equipe
            ORDER BY total_voltas_rapidas DESC LIMIT 1";
    $linha = mysqli_fetch_assoc(mysqli_query($conexao, $sql));
    return [
        'resposta' => $linha,
        'explicacao' => 'Piloto com o maior numero total de voltas mais rapidas registradas nas corridas.',
    ];
}

function pergunta_pior_confiabilidade(mysqli $conexao): array
{
    $sql = "SELECT p.id, p.nome, p.equipe, SUM(e.abandonos) AS total_abandonos
            FROM pilotos p JOIN estatisticas e ON e.piloto_id = p.id
            GROUP BY p.id, p.nome, p.equipe
            ORDER BY total_abandonos DESC LIMIT 1";
    $linha = mysqli_fetch_assoc(mysqli_query($conexao, $sql));
    return [
        'resposta' => $linha,
        'explicacao' => 'Piloto com o maior numero total de abandonos (DNF), indicando a pior confiabilidade mecanica/consistencia em corrida.',
    ];
}
