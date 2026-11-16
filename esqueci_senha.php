<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/SmtpMailer.php';
require_once __DIR__ . '/config/mail.php';

$mensagem = '';
$erro = '';
$erroTecnico = ''; // só aparece se DEBUG_EMAIL estiver ligado, para você depurar

const DEBUG_EMAIL = true; // <-- mude para false quando o envio estiver funcionando

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Informe um e-mail válido.';
    } else {
        try {
            $token = gerarTokenRedefinicao($email);
        } catch (Throwable $ex) {
            error_log('Falha ao gerar token de redefinição: ' . $ex->getMessage());
            $erro = 'Não foi possível processar sua solicitação no momento.';
            if (DEBUG_EMAIL) {
                $erro .= ' Erro técnico: ' . $ex->getMessage();
            }
            $token = null;
        }

        if ($erro === '' && $token !== null) {
            $link = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://')
                . $_SERVER['HTTP_HOST']
                . dirname($_SERVER['PHP_SELF'])
                . '/redefinir_senha.php?token=' . $token;

            $assunto = 'Redefinição de senha - Nihongo App';
            $corpo = "Você solicitou a redefinição da sua senha.\n\n"
                   . "Clique no link abaixo para criar uma nova senha (válido por 1 hora):\n"
                   . $link . "\n\n"
                   . "Se você não solicitou isso, ignore este e-mail.";

            try {
                if (!defined('SMTP_HOST')) {
                    throw new RuntimeException('Configuração de e-mail ausente (verifique se config/mail.php foi enviado ao servidor).');
                }

                $mailer = new SmtpMailer(
                    SMTP_HOST,
                    SMTP_PORT,
                    SMTP_USUARIO,
                    SMTP_SENHA,
                    SMTP_SECURE,
                    SMTP_USUARIO,
                    SMTP_FROM_NOME
                );

                $resultado = $mailer->enviar($email, $assunto, $corpo);

                if (!$resultado['sucesso']) {
                    error_log('Falha ao enviar e-mail de redefinição: ' . $resultado['erro']);
                    if (DEBUG_EMAIL) {
                        $erroTecnico = $resultado['erro'];
                    }
                }
            } catch (Throwable $ex) {
                error_log('Falha ao preparar/enviar e-mail de redefinição: ' . $ex->getMessage());
                if (DEBUG_EMAIL) {
                    $erroTecnico = $ex->getMessage();
                }
            }
        }

        // Mensagem genérica por segurança (não revela se o e-mail existe ou não),
        // só exibida quando não houve erro técnico ao processar a solicitação
        if ($erro === '') {
            $mensagem = 'Se o e-mail informado estiver cadastrado, enviamos um link de redefinição de senha para ele.';
        }
    }
}

$tituloPagina = 'Esqueci minha senha';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-wrapper">
  <div class="card">
    <h1>Esqueci minha senha</h1>

    <?php if ($erro): ?><div class="alerta erro"><?= e($erro) ?></div><?php endif; ?>
    <?php if ($mensagem): ?><div class="alerta sucesso"><?= e($mensagem) ?></div><?php endif; ?>
    <?php if ($erroTecnico): ?>
        <div class="alerta erro">Erro técnico (visível só com DEBUG_EMAIL=true): <?= e($erroTecnico) ?></div>
    <?php endif; ?>

    <?php if (!$mensagem): ?>
    <form method="post" novalidate>
        <div class="form-grupo">
            <label for="email">Digite o e-mail da sua conta</label>
            <input type="email" id="email" name="email" required>
        </div>
        <button type="submit" class="btn">Enviar link de redefinição</button>
    </form>
    <?php endif; ?>

    <p class="links-auth"><a href="login.php">Voltar para o login</a></p>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
