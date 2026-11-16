<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
exigirLogin();

$pontos = obterPontos((int) $_SESSION['usuario_id']);

$rotulos = [
    'hiragana'     => 'Hiragana',
    'katakana'     => 'Katakana',
    'kanjiN5'      => 'Kanji N5',
    'whiragana'    => 'Palavras (Hiragana)',
    'wkatakana'    => 'Palavras (Katakana)',
    'mistokanjiN5' => 'Misto N5',
    'frasesN5'     => 'Frases N5',
    'exercicioN5'  => 'Exercício N5',
];
$cores = ['#d64541','#2c3e50','#27ae60','#f39c12','#8e44ad','#16a085','#2980b9','#f1c40f'];

$totalGeral = array_sum($pontos);
$semDados = $totalGeral <= 0 && count(array_filter($pontos, fn($v) => $v > 0)) === 0;

$tituloPagina = 'Meu Progresso';
require __DIR__ . '/includes/header.php';
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/chartjs-plugin-datalabels/2.2.0/chartjs-plugin-datalabels.min.js"></script>

<div class="container container-admin">
    <a class="voltar-link" href="index.php">&larr; Voltar ao início</a>
    <h1 class="admin-titulo">Meu Progresso</h1>

    <div class="admin-resumo">
        <div class="resumo-card">
            <span class="resumo-numero"><?= (int) $totalGeral ?></span>
            <span class="resumo-label">Pontos no total</span>
        </div>
        <div class="resumo-card">
            <span class="resumo-numero"><?= (int) max($pontos) ?></span>
            <span class="resumo-label">Melhor categoria (pontos)</span>
        </div>
    </div>

    <div class="card admin-grafico-geral">
        <h2>Distribuição dos meus pontos por categoria</h2>
        <div class="grafico-wrapper">
            <canvas id="graficoMeuProgresso"></canvas>
        </div>
    </div>

    <div class="card">
        <h2>Pontuação por categoria</h2>
        <table class="tabela-pontos">
            <?php foreach ($rotulos as $chave => $rotulo): ?>
            <tr>
                <td><?= e($rotulo) ?></td>
                <td class="valor-ponto"><?= (int) $pontos[$chave] ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>

<script>
if (typeof Chart === 'undefined') {
    console.error('Chart.js não carregou.');
    document.querySelector('.grafico-wrapper').innerHTML =
        '<p style="color:#e74c3c">Não foi possível carregar a biblioteca de gráficos (Chart.js).</p>';
} else {
    const rotulos = <?= json_encode(array_values($rotulos)) ?>;
    const cores = <?= json_encode($cores) ?>;
    const valoresBrutos = <?= json_encode(array_values($pontos)) ?>;
    const valores = valoresBrutos.map(v => Math.max(0, v));
    const semDados = valores.every(v => v === 0);

    if (typeof ChartDataLabels !== 'undefined') {
        Chart.register(ChartDataLabels);
    }

    function formatarRotuloPercentual(value, context) {
        const dados = context.chart.data.datasets[0].data;
        const total = dados.reduce((soma, v) => soma + v, 0);
        if (total <= 0 || value <= 0) return '';
        const percentual = ((value / total) * 100).toFixed(1);
        return value + '\n(' + percentual + '%)';
    }

    new Chart(document.getElementById('graficoMeuProgresso'), {
        type: 'pie',
        data: {
            labels: rotulos,
            datasets: [{
                data: semDados ? rotulos.map(() => 1) : valores,
                backgroundColor: semDados ? rotulos.map(() => '#ecf0f1') : cores
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: {
                    enabled: !semDados,
                    callbacks: {
                        label: function (ctx) {
                            const total = ctx.dataset.data.reduce((s, v) => s + v, 0);
                            const pct = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : '0.0';
                            return ctx.label + ': ' + ctx.parsed + ' pts (' + pct + '%)';
                        }
                    }
                },
                datalabels: semDados ? { display: false } : {
                    color: '#fff',
                    font: { weight: '700', size: 11 },
                    textAlign: 'center',
                    formatter: formatarRotuloPercentual
                }
            }
        }
    });
}
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
