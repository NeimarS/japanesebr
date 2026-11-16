<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
exigirLogin();

$pdo = getConexao();

$hiragana = $pdo->query('SELECT caractere, romaji FROM hiragana_dados ORDER BY id')->fetchAll();
$katakana = $pdo->query('SELECT caractere, romaji FROM katakana_dados ORDER BY id')->fetchAll();
$kanji    = $pdo->query('SELECT kanji, significado, leitura_on, leitura_kun FROM kanji_n5_dados ORDER BY id')->fetchAll();

$tituloPagina = 'Tabelas de Referência';
require __DIR__ . '/includes/header.php';
?>
<div class="container container-referencia">
    <a class="voltar-link" href="index.php">&larr; Voltar ao início</a>
    <h1 class="admin-titulo">Tabelas de Referência</h1>
    <p class="ref-instrucao">Toque em qualquer caractere para ouvir a pronúncia.</p>

    <div class="abas-ref">
        <button type="button" class="aba-btn ativa" data-alvo="painel-hiragana">Hiragana (<?= count($hiragana) ?>)</button>
        <button type="button" class="aba-btn" data-alvo="painel-katakana">Katakana (<?= count($katakana) ?>)</button>
        <button type="button" class="aba-btn" data-alvo="painel-kanji">Kanji N5 (<?= count($kanji) ?>)</button>
    </div>

    <div id="painel-hiragana" class="painel-ref">
        <div class="grid-caracteres">
            <?php foreach ($hiragana as $item): ?>
            <button type="button" class="tile-caractere" data-falar="<?= e($item['caractere']) ?>">
                <span class="tile-char"><?= e($item['caractere']) ?></span>
                <span class="tile-romaji"><?= e($item['romaji']) ?></span>
            </button>
            <?php endforeach; ?>
        </div>
    </div>

    <div id="painel-katakana" class="painel-ref" hidden>
        <div class="grid-caracteres">
            <?php foreach ($katakana as $item): ?>
            <button type="button" class="tile-caractere" data-falar="<?= e($item['caractere']) ?>">
                <span class="tile-char"><?= e($item['caractere']) ?></span>
                <span class="tile-romaji"><?= e($item['romaji']) ?></span>
            </button>
            <?php endforeach; ?>
        </div>
    </div>

    <div id="painel-kanji" class="painel-ref" hidden>
        <div class="grid-caracteres grid-kanji">
            <?php foreach ($kanji as $item): ?>
            <button type="button" class="tile-caractere tile-kanji" data-falar="<?= e($item['kanji']) ?>"
                    title="<?= e($item['significado']) ?> — On: <?= e($item['leitura_on'] ?? '—') ?> / Kun: <?= e($item['leitura_kun'] ?? '—') ?>">
                <span class="tile-char"><?= e($item['kanji']) ?></span>
                <span class="tile-romaji"><?= e($item['significado']) ?></span>
                <span class="tile-leitura"><?= e($item['leitura_on'] ?? '') ?><?= $item['leitura_kun'] ? ' / ' . e($item['leitura_kun']) : '' ?></span>
            </button>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const abas = document.querySelectorAll('.aba-btn');
    console.log('[referencia] abas encontradas:', abas.length);

    function falarJapones(texto) {
        if (!('speechSynthesis' in window)) {
            alert('Seu navegador não suporta a leitura de voz (Web Speech API). Tente no Chrome ou Edge.');
            return;
        }
        window.speechSynthesis.cancel();
        const utter = new SpeechSynthesisUtterance(texto);
        utter.lang = 'ja-JP';
        utter.rate = 0.8;
        window.speechSynthesis.speak(utter);
    }

    // Alternância entre as abas (Hiragana / Katakana / Kanji)
    abas.forEach(function (botao) {
        botao.addEventListener('click', function () {
            abas.forEach(function (b) { b.classList.remove('ativa'); });
            botao.classList.add('ativa');

            document.querySelectorAll('.painel-ref').forEach(function (painel) {
                painel.hidden = (painel.id !== botao.dataset.alvo);
            });
        });
    });

    // Clique em qualquer caractere toca a pronúncia
    const tiles = document.querySelectorAll('.tile-caractere');
    console.log('[referencia] caracteres encontrados:', tiles.length);

    tiles.forEach(function (tile) {
        tile.addEventListener('click', function () {
            falarJapones(tile.dataset.falar);
            tile.classList.add('tile-tocando');
            setTimeout(function () { tile.classList.remove('tile-tocando'); }, 300);
        });
    });
});
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
