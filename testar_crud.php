<?php
declare(strict_types=1);

// Importa a conexão e os nossos novos arquivos
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/funcoes_temporadas.php';
require_once __DIR__ . '/includes/funcoes_circuitos.php';
require_once __DIR__ . '/includes/funcoes_corridas.php';

// Ativa exibição de erros na tela para facilitar o teste
ini_set('display_errors', '1');
error_reporting(E_ALL);

header('Content-Type: text/plain; charset=utf-8');

$conexao = obter_conexao();
echo "Conexão com o banco estabelecida!\n\n";

try {
    // 1. Testando Temporadas
    echo "--- TESTANDO TEMPORADAS ---\n";
    $id_temporada = inserir_temporada($conexao, 2026, '2026-03-01', '2026-12-01');
    echo "Temporada 2026 criada com ID: {$id_temporada}\n";
    
    // 2. Testando Circuitos
    echo "\n--- TESTANDO CIRCUITOS ---\n";
    $id_circuito = inserir_circuito($conexao, 'Autódromo de Interlagos', 'Brasil', 'São Paulo', 4.309);
    echo "Circuito criado com ID: {$id_circuito}\n";
    
    // 3. Testando Corridas (Usando os IDs gerados acima)
    echo "\n--- TESTANDO CORRIDAS ---\n";
    $id_corrida = inserir_corrida($conexao, 'GP de São Paulo', $id_temporada, $id_circuito, '2026-11-08', 71);
    echo "Corrida criada com ID: {$id_corrida}\n";

    // 4. Testando as Listagens (Com os JOINs)
    echo "\n--- RESULTADO DA LISTAGEM DE CORRIDAS ---\n";
    $corridas = listar_corridas($conexao);
    print_r($corridas);

} catch (Throwable $e) {
    echo "\n[ERRO FATAL] Algo deu errado: " . $e->getMessage() . "\n";
}