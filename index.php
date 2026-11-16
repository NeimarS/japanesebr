<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
exigirLogin();

$pontos = obterPontos((int) $_SESSION['usuario_id']);

$emojis = [
    'hiragana'  => '🈁',
    'katakana'  => '🈂️',
    'kanji'     => '漢',
    'whiragana' => '📖',
    'wkatakana' => '📗',
    'misto'     => '🔤',
    'frases'    => '💬',
];
$colunaPontos = [
    'hiragana'  => 'hiragana',
    'katakana'  => 'katakana',
    'kanji'     => 'kanjiN5',
    'whiragana' => 'whiragana',
    'wkatakana' => 'wkatakana',
    'misto'     => 'mistokanjiN5',
    'frases'    => 'frasesN5',
];

$tituloPagina = 'Início';
require __DIR__ . '/includes/header.php';
?>
<div class="container">
    <div class="saudacao">
        <h1>Olá! 👋</h1>
        <p><?= e($_SESSION['usuario_email']) ?></p>
    </div>

    <a class="btn secundario" style="display:block;margin-bottom:10px;" href="referencia.php">📚 Ver tabelas de Hiragana, Katakana e Kanji N5</a>
    <a class="btn secundario" style="display:block;margin-bottom:10px;" href="gramatica.php">📐 Gramática (cumprimentos, sala de aula e números)</a>
    <a class="btn secundario" style="display:block;margin-bottom:10px;" href="progresso.php">📊 Meu Progresso</a>
    <a class="btn secundario" style="display:block;margin-bottom:10px;" href="duvidas.php">✉️ Dúvidas/Sugestões</a>
    <a class="link-info-uso" href="informacoes.php">ℹ️ Informações de uso</a>

    <div class="grid-categorias">
        <?php foreach (CATEGORIAS as $chave => $info): ?>
        <a class="categoria-card" href="categoria.php?c=<?= urlencode($chave) ?>">
            <span class="emoji"><?= e($emojis[$chave] ?? '📚') ?></span>
            <span class="titulo"><?= e($info['titulo']) ?></span>
            <span class="pontos">Pontos: <?= (int) $pontos[$colunaPontos[$chave]] ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
