<?php
require_once __DIR__ . '/includes/auth.php';

if (estaLogado()) {
    header('Location: index.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';

    $erro = autenticarUsuario($email, $senha);
    if ($erro === '') {
        header('Location: index.php');
        exit;
    }
}

$tituloPagina = 'Login';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-wrapper">
  <div class="card">
    <h1>Entrar</h1>

    <?php if ($erro): ?><div class="alerta erro"><?= e($erro) ?></div><?php endif; ?>

    <form method="post" novalidate>
        <div class="form-grupo">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
        </div>
        <div class="form-grupo">
            <label for="senha">Senha</label>
            <input type="password" id="senha" name="senha" required>
        </div>
        <button type="submit" class="btn">Entrar</button>
    </form>

    <p class="links-auth">
        <a href="esqueci_senha.php">Esqueci minha senha</a><br><br>
        Não tem conta? <a href="cadastro.php">Cadastre-se</a>
    </p>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
