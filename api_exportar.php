<?php
declare(strict_types=1);

// Desativa erros na tela para não corromper o arquivo CSV com HTML
error_reporting(0);
ini_set('display_errors', '0');

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/utils.php';
require_once __DIR__ . '/includes/valida_sessao.php';

// Proteção: Apenas usuários logados exportam a matriz
exigir_login();

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder_erro(405, 'Método não permitido.');
}

$conexao = obter_conexao();

// Query adaptada ao schema real: calcula a etapa em tempo real e busca a equipe no histórico
$sql = "
    SELECT 
        t.ano AS temporada,
        (SELECT COUNT(*) FROM corridas c2 WHERE c2.temporada_id = c.temporada_id AND c2.data_corrida <= c.data_corrida) AS etapa,
        c.nome AS gp_nome,
        circ.nome AS circuito,
        c.data_corrida,
        p.nome AS piloto,
        COALESCE((SELECT eq.nome FROM historico_piloto_equipe hpe 
         JOIN equipes eq ON hpe.equipe_id = eq.id 
         WHERE hpe.piloto_id = p.id 
         ORDER BY hpe.data_inicio DESC LIMIT 1), 'Desconhecida') AS equipe,
        p.numero,
        rc.colocacao_grid AS posicao_largada,
        rc.posicao_final AS posicao_chegada,
        rc.pontos,
        rc.status,
        rc.volta_mais_rapida AS fastest_lap
    FROM resultado_corrida rc
    JOIN corridas c ON rc.corrida_id = c.id
    JOIN temporadas t ON c.temporada_id = t.id
    JOIN circuitos circ ON c.circuito_id = circ.id
    JOIN pilotos p ON rc.piloto_id = p.id
    ORDER BY t.ano DESC, c.data_corrida ASC, rc.posicao_final ASC
";

$resultado = mysqli_query($conexao, $sql);

if (!$resultado) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    // Adicionado mysqli_error para expor exatamente o que quebrou no banco, caso falhe
    echo json_encode(['erro' => 'Falha ao extrair dados da matriz: ' . mysqli_error($conexao)]);
    exit;
}

// Configura os headers HTTP para forçar o download do CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="pitwall_telemetry_matrix_' . date('Ymd_His') . '.csv"');
header('Pragma: no-cache');
header('Expires: 0');

// Abre o buffer de saída direto do PHP
$output = fopen('php://output', 'w');

// Insere o BOM (Byte Order Mark) para garantir que o Excel abra em UTF-8 mantendo os acentos
fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Imprime a estrutura de cabeçalho idêntica à de importação
fputcsv($output, [
    'temporada', 'etapa', 'gp_nome', 'circuito', 'data_corrida',
    'piloto', 'equipe', 'numero', 'posicao_largada', 'posicao_chegada',
    'pontos', 'status', 'fastest_lap'
]);

// Varre os resultados e imprime linha a linha
while ($linha = mysqli_fetch_assoc($resultado)) {
    fputcsv($output, $linha);
}

fclose($output);
exit;