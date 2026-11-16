<?php
require_once __DIR__ . '/includes/auth.php';

if (estaLogado()) {
    header('Location: index.php');
    exit;
}

$erro = '';
$sucesso = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    $confirmar = $_POST['confirmar_senha'] ?? '';

    $erro = cadastrarUsuario($email, $senha, $confirmar);
    if ($erro === '') {
        $sucesso = true;
    }
}

$tituloPagina = 'Cadastro';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-wrapper">
  <div class="card">
    <h1>Criar conta</h1>

    <?php if ($erro): ?><div class="alerta erro"><?= e($erro) ?></div><?php endif; ?>
    <?php if ($sucesso): ?>
      <div class="alerta sucesso">Cadastro realizado com sucesso! Você já pode <a href="login.php">fazer login</a>.</div>
    <?php else: ?>
    <form method="post" novalidate>
        <div class="form-grupo">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
        </div>
        <div class="form-grupo">
            <label for="senha">Senha</label>
            <input type="password" id="senha" name="senha" minlength="6" required>
        </div>
        <div class="form-grupo">
            <label for="confirmar_senha">Confirmar senha</label>
            <input type="password" id="confirmar_senha" name="confirmar_senha" minlength="6" required>
        </div>
        <button type="submit" class="btn">Cadastrar</button>
    </form>
    <?php endif; ?>

    <p class="links-auth">Já tem conta? <a href="login.php">Entrar</a></p>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
