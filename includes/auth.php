<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

/**
 * Cadastra um novo usuário. Retorna string vazia em caso de sucesso,
 * ou uma mensagem de erro.
 */
function cadastrarUsuario(string $email, string $senha, string $confirmarSenha): string
{
    $email = trim($email);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'Informe um e-mail válido.';
    }
    if (strlen($senha) < 6) {
        return 'A senha deve ter pelo menos 6 caracteres.';
    }
    if ($senha !== $confirmarSenha) {
        return 'As senhas não coincidem.';
    }

    $pdo = getConexao();

    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = :email');
    $stmt->execute(['email' => $email]);
    if ($stmt->fetch()) {
        return 'Este e-mail já está cadastrado.';
    }

    $hash = password_hash($senha, PASSWORD_BCRYPT);

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('INSERT INTO usuarios (email, senha) VALUES (:email, :senha)');
        $stmt->execute(['email' => $email, 'senha' => $hash]);
        $novoId = (int) $pdo->lastInsertId();

        // Cria a linha de pontos correspondente (relação 1-1 via FK)
        $stmt = $pdo->prepare('INSERT INTO pontos (id) VALUES (:id)');
        $stmt->execute(['id' => $novoId]);

        $pdo->commit();
    } catch (Exception $ex) {
        $pdo->rollBack();
        return 'Erro ao cadastrar. Tente novamente.';
    }

    return '';
}

/**
 * Autentica o usuário. Retorna string vazia em caso de sucesso (e inicia a sessão),
 * ou uma mensagem de erro.
 */
function autenticarUsuario(string $email, string $senha): string
{
    $pdo = getConexao();
    $stmt = $pdo->prepare('SELECT id, senha FROM usuarios WHERE email = :email');
    $stmt->execute(['email' => trim($email)]);
    $usuario = $stmt->fetch();

    if (!$usuario || !password_verify($senha, $usuario['senha'])) {
        return 'E-mail ou senha inválidos.';
    }

    session_regenerate_id(true);
    $_SESSION['usuario_id'] = (int) $usuario['id'];
    $_SESSION['usuario_email'] = trim($email);

    return '';
}

function encerrarSessao(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie('PHPSESSID', '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

/**
 * Gera um token de redefinição de senha para o e-mail informado.
 * Retorna o token em caso de sucesso, ou null se o e-mail não existir.
 */
function gerarTokenRedefinicao(string $email): ?string
{
    $pdo = getConexao();
    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = :email');
    $stmt->execute(['email' => trim($email)]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        return null;
    }

    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expira = date('Y-m-d H:i:s', time() + 3600); // válido por 1 hora

    $stmt = $pdo->prepare('UPDATE usuarios SET reset_token = :token, reset_expira = :expira WHERE id = :id');
    $stmt->execute(['token' => $tokenHash, 'expira' => $expira, 'id' => $usuario['id']]);

    return $token;
}

/**
 * Redefine a senha usando um token válido. Retorna string vazia em caso de sucesso,
 * ou uma mensagem de erro.
 */
function redefinirSenhaComToken(string $token, string $novaSenha, string $confirmarSenha): string
{
    if (strlen($novaSenha) < 6) {
        return 'A senha deve ter pelo menos 6 caracteres.';
    }
    if ($novaSenha !== $confirmarSenha) {
        return 'As senhas não coincidem.';
    }

    $pdo = getConexao();
    $tokenHash = hash('sha256', $token);

    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE reset_token = :token AND reset_expira > NOW()');
    $stmt->execute(['token' => $tokenHash]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        return 'Link inválido ou expirado. Solicite a recuperação novamente.';
    }

    $hash = password_hash($novaSenha, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare('UPDATE usuarios SET senha = :senha, reset_token = NULL, reset_expira = NULL WHERE id = :id');
    $stmt->execute(['senha' => $hash, 'id' => $usuario['id']]);

    return '';
}
