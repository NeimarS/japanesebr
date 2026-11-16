<?php
require_once __DIR__ . '/includes/auth.php';

$token = $_GET['token'] ?? ($_POST['token'] ?? '');
$erro = '';
$sucesso = false;

if ($token === '') {
    $erro = 'Link inválido.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $novaSenha = $_POST['senha'] ?? '';
    $confirmar = $_POST['confirmar_senha'] ?? '';

    $erro = redefinirSenhaComToken($token, $novaSenha, $confirmar);
    if ($erro === '') {
        $sucesso = true;
    }
}

$tituloPagina = 'Redefinir senha';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-wrapper">
  <div class="card">
    <h1>Definir nova senha</h1>

    <?php if ($erro): ?><div class="alerta erro"><?= e($erro) ?></div><?php endif; ?>

    <?php if ($sucesso): ?>
        <div class="alerta sucesso">Senha redefinida com sucesso! Você já pode <a href="login.php">fazer login</a>.</div>
    <?php elseif ($token !== ''): ?>
    <form method="post" novalidate>
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <div class="form-grupo">
            <label for="senha">Nova senha</label>
            <input type="password" id="senha" name="senha" minlength="6" required>
        </div>
        <div class="form-grupo">
            <label for="confirmar_senha">Confirmar nova senha</label>
            <input type="password" id="confirmar_senha" name="confirmar_senha" minlength="6" required>
        </div>
        <button type="submit" class="btn">Salvar nova senha</button>
    </form>
    <?php endif; ?>

    <p class="links-auth"><a href="login.php">Voltar para o login</a></p>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
