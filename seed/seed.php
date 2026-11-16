<?php
/**
 * Executa este arquivo UMA VEZ (pelo navegador ou via CLI: php seed/seed.php)
 * depois de importar database/schema.sql, para popular as tabelas de
 * conteúdo (hiragana, katakana, kanji, palavras e frases).
 *
 * Por segurança, apague este arquivo do servidor depois de rodá-lo.
 */

declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';

$pdo = getConexao();

function popularParDeColunas(PDO $pdo, string $tabela, array $dados): int
{
    $pdo->exec("DELETE FROM `$tabela`");
    $stmt = $pdo->prepare("INSERT INTO `$tabela` (caractere, romaji) VALUES (:a, :b)");
    $n = 0;
    foreach ($dados as $car => $romaji) {
        $stmt->execute(['a' => $car, 'b' => $romaji]);
        $n++;
    }
    return $n;
}

function popularPalavraSignificado(PDO $pdo, string $tabela, array $dados): int
{
    $pdo->exec("DELETE FROM `$tabela`");
    $stmt = $pdo->prepare("INSERT INTO `$tabela` (palavra, significado) VALUES (:a, :b)");
    $n = 0;
    foreach ($dados as [$palavra, $significado]) {
        $stmt->execute(['a' => $palavra, 'b' => $significado]);
        $n++;
    }
    return $n;
}

$total = [];

$total['hiragana'] = popularParDeColunas($pdo, 'hiragana_dados', require __DIR__ . '/../data/hiragana.php');
$total['katakana'] = popularParDeColunas($pdo, 'katakana_dados', require __DIR__ . '/../data/katakana.php');

// Kanji tem 4 colunas
$pdo->exec('DELETE FROM kanji_n5_dados');
$stmtKanji = $pdo->prepare('INSERT INTO kanji_n5_dados (kanji, significado, leitura_on, leitura_kun) VALUES (:k, :s, :on, :kun)');
$n = 0;
foreach (require __DIR__ . '/../data/kanji_n5.php' as [$k, $s, $on, $kun]) {
    $stmtKanji->execute(['k' => $k, 's' => $s, 'on' => $on, 'kun' => $kun]);
    $n++;
}
$total['kanji_n5'] = $n;

$total['palavras_hiragana'] = popularPalavraSignificado($pdo, 'palavras_hiragana_dados', require __DIR__ . '/../data/palavras_hiragana.php');
$total['palavras_katakana'] = popularPalavraSignificado($pdo, 'palavras_katakana_dados', require __DIR__ . '/../data/palavras_katakana.php');
$total['misto_n5']          = popularPalavraSignificado($pdo, 'misto_n5_dados', require __DIR__ . '/../data/misto_n5.php');

// Frases: colunas frase/traducao
$pdo->exec('DELETE FROM frases_n5_dados');
$stmtFrases = $pdo->prepare('INSERT INTO frases_n5_dados (frase, traducao) VALUES (:f, :t)');
$n = 0;
foreach (require __DIR__ . '/../data/frases_n5.php' as [$frase, $traducao]) {
    $stmtFrases->execute(['f' => $frase, 't' => $traducao]);
    $n++;
}
$total['frases_n5'] = $n;

// Gramática: colunas item/romaji/traducao/grupo
$pdo->exec('DELETE FROM gramatica_dados');
$stmtGramatica = $pdo->prepare('INSERT INTO gramatica_dados (item, romaji, traducao, grupo) VALUES (:i, :r, :t, :g)');
$n = 0;
foreach (require __DIR__ . '/../data/gramatica.php' as [$item, $romaji, $traducao, $grupo]) {
    $stmtGramatica->execute(['i' => $item, 'r' => $romaji, 't' => $traducao, 'g' => $grupo]);
    $n++;
}
$total['gramatica'] = $n;

header('Content-Type: text/plain; charset=utf-8');
echo "Conteúdo importado com sucesso:\n";
foreach ($total as $tabela => $qtd) {
    echo "- $tabela: $qtd registros\n";
}
echo "\nAgora apague este arquivo (seed/seed.php) do servidor por segurança.\n";
