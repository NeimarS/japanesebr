<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
exigirLogin();

$categoria = $_GET['c'] ?? '';
if (!isset(CATEGORIAS[$categoria])) {
    header('Location: index.php');
    exit;
}

$info = CATEGORIAS[$categoria];
$pontosAtuais = obterPontos((int) $_SESSION['usuario_id']);
$pontoInicial = (int) $pontosAtuais[$info['coluna']];

$tituloPagina = $info['titulo'];
require __DIR__ . '/includes/header.php';
?>
<div class="container">
    <a class="voltar-link" href="index.php">&larr; Voltar ao início</a>

    <div class="quiz-header">
        <h2><?= e($info['titulo']) ?></h2>
        <span class="placar" id="placar">Pontos: <?= $pontoInicial ?></span>
    </div>

    <div id="quiz" data-categoria="<?= e($categoria) ?>">
        <div class="card pergunta-card">
            <div id="pergunta-texto" class="pergunta-texto">...</div>
            <button type="button" id="btn-falar" class="btn-falar" title="Ouvir pronúncia">🔊</button>

            <div id="alternativas" class="alternativas"></div>

            <div id="feedback" class="feedback-inline"></div>

            <div class="acoes-quiz">
                <button type="button" id="btn-proxima" class="btn secundario pequeno">Próxima pergunta &rarr;</button>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
