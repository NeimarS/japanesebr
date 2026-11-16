<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/SmtpMailer.php';
require_once __DIR__ . '/config/mail.php';
exigirLogin();

const DEBUG_EMAIL_DUVIDAS = true; // <-- mude para false quando o envio estiver funcionando

$erro = '';
$sucesso = false;
$erroTecnico = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $assunto = trim($_POST['assunto'] ?? '');
    $mensagem = trim($_POST['mensagem'] ?? '');
    $emailUsuario = $_SESSION['usuario_email'];

    if ($assunto === '' || $mensagem === '') {
        $erro = 'Preencha o assunto e a mensagem.';
    } elseif (mb_strlen($mensagem) > 4000) {
        $erro = 'Mensagem muito longa (máximo de 4000 caracteres).';
    } else {
        try {
            $pdo = getConexao();
            $stmt = $pdo->prepare(
                'INSERT INTO duvidas_sugestoes (usuario_id, email, assunto, mensagem) VALUES (:uid, :email, :assunto, :mensagem)'
            );
            $stmt->execute([
                'uid'      => $_SESSION['usuario_id'],
                'email'    => $emailUsuario,
                'assunto'  => $assunto,
                'mensagem' => $mensagem,
            ]);
        } catch (PDOException $ex) {
            error_log('Falha ao salvar dúvida/sugestão no banco: ' . $ex->getMessage());
            $erro = 'Não foi possível salvar sua mensagem no momento.';
            if (DEBUG_EMAIL_DUVIDAS) {
                $erro .= ' Erro técnico: ' . $ex->getMessage();
            }
        }

        if ($erro === '') {
        // Tenta notificar o administrador por e-mail (a mensagem já está salva no banco
        // de qualquer forma, então uma eventual falha aqui não trava a página nem é
        // mostrada como erro fatal ao usuário)
        try {
            if (!defined('SMTP_HOST') || !defined('ADMIN_EMAIL')) {
                throw new RuntimeException('Configuração de e-mail ausente (verifique se config/mail.php foi enviado ao servidor e contém SMTP_HOST e ADMIN_EMAIL).');
            }

            $mailer = new SmtpMailer(
                SMTP_HOST,
                SMTP_PORT,
                SMTP_USUARIO,
                SMTP_SENHA,
                SMTP_SECURE,
                SMTP_USUARIO,
                SMTP_FROM_NOME . ' - ' . $emailUsuario
            );

            $corpo = "Nova mensagem pelo formulário de Dúvidas/Sugestões do Nihongo App.\n\n"
                   . "De: {$emailUsuario}\n"
                   . "Assunto: {$assunto}\n\n"
                   . "Mensagem:\n{$mensagem}";

            $resultado = $mailer->enviar(ADMIN_EMAIL, '[Nihongo App] ' . $assunto, $corpo, $emailUsuario);

            if (!$resultado['sucesso']) {
                error_log('Falha ao enviar e-mail de dúvida/sugestão: ' . $resultado['erro']);
                if (DEBUG_EMAIL_DUVIDAS) {
                    $erroTecnico = $resultado['erro'];
                }
            }
        } catch (Throwable $ex) {
            error_log('Falha ao preparar/enviar e-mail de dúvida/sugestão: ' . $ex->getMessage());
            if (DEBUG_EMAIL_DUVIDAS) {
                $erroTecnico = $ex->getMessage();
            }
        }

        $sucesso = true;
        }
    }
}

$tituloPagina = 'Dúvidas e Sugestões';
require __DIR__ . '/includes/header.php';
?>
<div class="container">
    <a class="voltar-link" href="index.php">&larr; Voltar ao início</a>

    <div class="card">
        <h1 class="admin-titulo">Dúvidas e Sugestões</h1>
        <p>Encontrou algum problema no app ou tem alguma sugestão de melhoria? Escreva abaixo — sua
           mensagem é enviada direto para o administrador.</p>

        <?php if ($erro): ?><div class="alerta erro"><?= e($erro) ?></div><?php endif; ?>
        <?php if ($sucesso): ?>
            <div class="alerta sucesso">Sua mensagem foi enviada com sucesso. Obrigado pelo contato!</div>
        <?php endif; ?>
        <?php if ($erroTecnico): ?>
            <div class="alerta erro">Erro técnico no envio do e-mail (visível só com DEBUG_EMAIL_DUVIDAS=true, a mensagem já foi salva): <?= e($erroTecnico) ?></div>
        <?php endif; ?>

        <form method="post" novalidate>
            <div class="form-grupo">
                <label for="assunto">Assunto</label>
                <input type="text" id="assunto" name="assunto" maxlength="255" required
                       placeholder="Ex.: Sugestão de nova categoria, erro na tela de login...">
            </div>
            <div class="form-grupo">
                <label for="mensagem">Mensagem</label>
                <textarea id="mensagem" name="mensagem" rows="6" maxlength="4000" required
                          placeholder="Descreva sua dúvida ou sugestão com detalhes..."></textarea>
            </div>
            <button type="submit" class="btn">Enviar mensagem</button>
        </form>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
