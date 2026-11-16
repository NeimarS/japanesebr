<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
exigirAdmin();

// Token CSRF para proteger a ação de exclusão
if (empty($_SESSION['csrf_admin'])) {
    $_SESSION['csrf_admin'] = bin2hex(random_bytes(32));
}

// Exclusão de usuário (POST) -> processa, guarda mensagem na sessão e redireciona
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'excluir_usuario') {
    if (!hash_equals($_SESSION['csrf_admin'], (string) ($_POST['csrf'] ?? ''))) {
        $_SESSION['flash_admin'] = ['tipo' => 'erro', 'msg' => 'Requisição inválida. Recarregue a página e tente novamente.'];
    } else {
        try {
            $erroExclusao = excluirUsuario((int) ($_POST['usuario_id'] ?? 0), (int) $_SESSION['usuario_id']);
        } catch (Throwable $ex) {
            error_log('Falha ao excluir usuário: ' . $ex->getMessage());
            $erroExclusao = 'Não foi possível excluir o usuário no momento.';
        }
        $_SESSION['flash_admin'] = $erroExclusao === ''
            ? ['tipo' => 'sucesso', 'msg' => 'Usuário excluído com sucesso.']
            : ['tipo' => 'erro', 'msg' => $erroExclusao];
    }
    header('Location: admin.php');
    exit;
}

$flash = $_SESSION['flash_admin'] ?? null;
unset($_SESSION['flash_admin']);

$stats = obterEstatisticasAdmin();

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

$tituloPagina = 'Painel Admin';
require __DIR__ . '/includes/header.php';
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/chartjs-plugin-datalabels/2.2.0/chartjs-plugin-datalabels.min.js"></script>

<div class="container container-admin">
    <a class="voltar-link" href="index.php">&larr; Voltar ao início</a>

    <h1 class="admin-titulo">Painel do Administrador</h1>

    <?php if ($flash): ?>
        <div class="alerta <?= $flash['tipo'] === 'sucesso' ? 'sucesso' : 'erro' ?>"><?= e($flash['msg']) ?></div>
    <?php endif; ?>

    <div class="admin-resumo">
        <div class="resumo-card">
            <span class="resumo-numero"><?= (int) $stats['total_usuarios'] ?></span>
            <span class="resumo-label">Usuários cadastrados</span>
        </div>
        <div class="resumo-card">
            <span class="resumo-numero"><?= (int) array_sum($stats['agregado']) ?></span>
            <span class="resumo-label">Pontos somados (todas as categorias)</span>
        </div>
    </div>

    <div class="card admin-grafico-geral">
        <h2>Distribuição geral de pontos por categoria</h2>
        <div class="grafico-wrapper">
            <canvas id="graficoGeral"></canvas>
        </div>
    </div>

    <h2 class="admin-subtitulo">Progresso individual por usuário</h2>

    <?php if (empty($stats['usuarios'])): ?>
        <p>Nenhum usuário cadastrado ainda.</p>
    <?php endif; ?>

    <div class="grid-admin-usuarios">
        <?php foreach ($stats['usuarios'] as $u): ?>
        <div class="card admin-usuario-card">
            <div class="admin-usuario-topo">
                <strong><?= e($u['email']) ?></strong>
                <span class="admin-usuario-total">Total: <?= (int) $u['total'] ?> pts</span>
            </div>
            <div class="grafico-wrapper grafico-wrapper-usuario">
                <canvas class="grafico-usuario" data-pontos='<?= e(json_encode(array_values($u['pontos']))) ?>'></canvas>
            </div>
            <table class="tabela-pontos">
                <?php foreach ($rotulos as $chave => $rotulo): ?>
                <tr>
                    <td><?= e($rotulo) ?></td>
                    <td class="valor-ponto"><?= (int) $u['pontos'][$chave] ?></td>
                </tr>
                <?php endforeach; ?>
            </table>

            <?php if ($u['is_admin']): ?>
                <p class="admin-conta-protegida">Conta de administrador (não pode ser excluída aqui)</p>
            <?php else: ?>
                <form method="post" class="form-excluir"
                      onsubmit="return confirm('Tem certeza que deseja excluir este usuário? Essa ação não pode ser desfeita e apaga também todos os pontos dele.');">
                    <input type="hidden" name="acao" value="excluir_usuario">
                    <input type="hidden" name="usuario_id" value="<?= (int) $u['id'] ?>">
                    <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf_admin']) ?>">
                    <button type="submit" class="btn-excluir">🗑️ Excluir usuário</button>
                </form>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
if (typeof Chart === 'undefined') {
    console.error('Chart.js não carregou. Verifique se o link do CDN em admin.php está acessível (abra o link do <script src=...> direto no navegador).');
    document.querySelectorAll('.grafico-wrapper').forEach(function (w) {
        w.innerHTML = '<p style="color:#e74c3c">Não foi possível carregar a biblioteca de gráficos (Chart.js). Verifique a conexão com a internet do servidor/navegador.</p>';
    });
} else {
    const CORES = <?= json_encode($cores) ?>;
    const ROTULOS = <?= json_encode(array_values($rotulos)) ?>;

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

    function callbackTooltipPercentual(ctx) {
        const total = ctx.dataset.data.reduce((s, v) => s + v, 0);
        const pct = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : '0.0';
        return ctx.label + ': ' + ctx.parsed + ' pts (' + pct + '%)';
    }

    // Gráfico agregado geral (soma de todos os usuários por categoria)
    new Chart(document.getElementById('graficoGeral'), {
        type: 'pie',
        data: {
            labels: ROTULOS,
            datasets: [{
                data: <?= json_encode(array_map(fn($v) => max(0, (int) $v), array_values($stats['agregado']))) ?>,
                backgroundColor: CORES
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: { callbacks: { label: callbackTooltipPercentual } },
                datalabels: {
                    color: '#fff',
                    font: { weight: '700', size: 11 },
                    textAlign: 'center',
                    formatter: formatarRotuloPercentual
                }
            }
        }
    });

    // Um gráfico de pizza por usuário
    document.querySelectorAll('.grafico-usuario').forEach(function (canvas) {
        const pontos = JSON.parse(canvas.dataset.pontos).map(v => Math.max(0, v));
        const semDados = pontos.every(v => v === 0);

        new Chart(canvas, {
            type: 'pie',
            data: {
                labels: ROTULOS,
                datasets: [{
                    data: semDados ? ROTULOS.map(() => 1) : pontos,
                    backgroundColor: semDados ? ROTULOS.map(() => '#ecf0f1') : CORES
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: !semDados, callbacks: { label: callbackTooltipPercentual } },
                    datalabels: semDados ? { display: false } : {
                        color: '#fff',
                        font: { weight: '700', size: 9 },
                        textAlign: 'center',
                        formatter: formatarRotuloPercentual
                    }
                }
            }
        });
    });
}
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
