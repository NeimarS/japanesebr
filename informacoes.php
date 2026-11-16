<?php
require_once __DIR__ . '/includes/auth.php';
exigirLogin();

$tituloPagina = 'Informações de uso';
require __DIR__ . '/includes/header.php';
?>
<div class="container">
    <a class="voltar-link" href="index.php">&larr; Voltar ao início</a>

    <div class="card">
        <h1 class="admin-titulo">Informações de uso</h1>
        <p>Para aproveitar melhor o app, essa é a proporção recomendada de dedicação
           entre os grupos de estudo, do início ao domínio do nível N5:</p>

        <div class="info-uso-lista">
            <div class="info-uso-item">
                <div class="info-uso-cabecalho">
                    <span class="info-uso-nome">🈁 Hiragana</span>
                    <span class="info-uso-percentual">60% a 70%</span>
                </div>
                <div class="barra-uso"><div class="barra-uso-preenchida" style="width:65%"></div></div>
                <p class="info-uso-texto">É a base de tudo: usado para gramática, partículas e a leitura de
                   praticamente qualquer palavra japonesa. Deve ser a maior parte do seu tempo de estudo.</p>
            </div>

            <div class="info-uso-item">
                <div class="info-uso-cabecalho">
                    <span class="info-uso-nome">🈂️ Katakana</span>
                    <span class="info-uso-percentual">10% a 15%</span>
                </div>
                <div class="barra-uso"><div class="barra-uso-preenchida" style="width:12%"></div></div>
                <p class="info-uso-texto">Usado principalmente em palavras estrangeiras/empréstimos e nomes próprios.
                   Aparece com bem menos frequência que o hiragana no dia a dia.</p>
            </div>

            <div class="info-uso-item">
                <div class="info-uso-cabecalho">
                    <span class="info-uso-nome">漢 Kanji N5</span>
                    <span class="info-uso-percentual">20% a 30%</span>
                </div>
                <div class="barra-uso"><div class="barra-uso-preenchida" style="width:25%"></div></div>
                <p class="info-uso-texto">Reserve uma parte consistente do seu tempo para os 112 kanji do N5 — eles
                   aparecem em vocabulário, provas e textos, mas devem ser estudados depois de já ter uma boa base
                   de hiragana.</p>
            </div>
        </div>

        <p class="info-uso-rodape">Essas faixas são uma referência geral de dedicação de tempo entre os três
           grupos, não uma meta fixa — ajuste conforme sua evolução em cada categoria na tela
           <a href="index.php">Início</a>.</p>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
