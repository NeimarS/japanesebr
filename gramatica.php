<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
exigirLogin();

$pdo = getConexao();
$itens = $pdo->query('SELECT item, romaji, traducao, grupo FROM gramatica_dados ORDER BY id')->fetchAll();

$grupos = [];
foreach ($itens as $item) {
    $grupos[$item['grupo']][] = $item;
}

$tituloPagina = 'Gramática';
require __DIR__ . '/includes/header.php';
?>
<div class="container container-referencia">
    <a class="voltar-link" href="index.php">&larr; Voltar ao início</a>
    <h1 class="admin-titulo">Gramática</h1>
    <p class="ref-instrucao">Conteúdo de consulta (não vale pontos). Toque em qualquer item para ouvir a pronúncia.</p>

    <?php foreach ($grupos as $nomeGrupo => $itensDoGrupo): ?>
    <div class="card grupo-gramatica">
        <h2 class="grupo-gramatica-titulo"><?= e($nomeGrupo) ?></h2>
        <div class="lista-gramatica">
            <?php foreach ($itensDoGrupo as $item): ?>
            <button type="button" class="item-gramatica" data-falar="<?= e($item['item']) ?>">
                <span class="item-gramatica-jp"><?= e($item['item']) ?></span>
                <span class="item-gramatica-romaji"><?= e($item['romaji']) ?></span>
                <span class="item-gramatica-traducao"><?= e($item['traducao']) ?></span>
            </button>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
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

    document.querySelectorAll('.item-gramatica').forEach(function (item) {
        item.addEventListener('click', function () {
            falarJapones(item.dataset.falar);
            item.classList.add('item-gramatica-tocando');
            setTimeout(function () { item.classList.remove('item-gramatica-tocando'); }, 300);
        });
    });
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
