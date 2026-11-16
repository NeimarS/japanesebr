<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database.php';

// Categorias válidas e a coluna correspondente na tabela `pontos`
const CATEGORIAS = [
    'hiragana'  => ['coluna' => 'hiragana',     'tabela' => 'hiragana_dados',          'titulo' => 'Hiragana',           'campo_pergunta' => 'caractere', 'campo_resposta' => 'romaji'],
    'katakana'  => ['coluna' => 'katakana',     'tabela' => 'katakana_dados',          'titulo' => 'Katakana',           'campo_pergunta' => 'caractere', 'campo_resposta' => 'romaji'],
    'kanji'     => ['coluna' => 'kanjiN5',      'tabela' => 'kanji_n5_dados',          'titulo' => 'Kanji N5',           'campo_pergunta' => 'kanji',     'campo_resposta' => 'significado'],
    'whiragana' => ['coluna' => 'whiragana',    'tabela' => 'palavras_hiragana_dados', 'titulo' => 'Palavras (Hiragana)','campo_pergunta' => 'palavra',   'campo_resposta' => 'significado'],
    'wkatakana' => ['coluna' => 'wkatakana',    'tabela' => 'palavras_katakana_dados', 'titulo' => 'Palavras (Katakana)','campo_pergunta' => 'palavra',   'campo_resposta' => 'significado'],
    'misto'     => ['coluna' => 'mistokanjiN5', 'tabela' => 'misto_n5_dados',          'titulo' => 'Misto N5',           'campo_pergunta' => 'palavra',   'campo_resposta' => 'significado'],
    'frases'    => ['coluna' => 'frasesN5',     'tabela' => 'frases_n5_dados',         'titulo' => 'Frases N5',          'campo_pergunta' => 'frase',     'campo_resposta' => 'traducao'],
];

function estaLogado(): bool
{
    return isset($_SESSION['usuario_id']);
}

function exigirLogin(): void
{
    if (!estaLogado()) {
        header('Location: login.php');
        exit;
    }
}

function ehAdmin(): bool
{
    if (!estaLogado()) {
        return false;
    }
    if (!isset($_SESSION['is_admin'])) {
        $pdo = getConexao();
        $stmt = $pdo->prepare('SELECT is_admin FROM usuarios WHERE id = :id');
        $stmt->execute(['id' => $_SESSION['usuario_id']]);
        $linha = $stmt->fetch();
        $_SESSION['is_admin'] = $linha ? (bool) $linha['is_admin'] : false;
    }
    return $_SESSION['is_admin'];
}

function exigirAdmin(): void
{
    exigirLogin();
    if (!ehAdmin()) {
        header('Location: index.php');
        exit;
    }
}

function e(string $texto): string
{
    return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
}
