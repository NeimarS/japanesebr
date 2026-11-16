<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!estaLogado()) {
    http_response_code(401);
    echo json_encode(['erro' => 'Sessão expirada. Faça login novamente.']);
    exit;
}

$categoria = $_GET['categoria'] ?? '';
$pergunta = sortearPergunta($categoria);

if ($pergunta === null) {
    http_response_code(400);
    echo json_encode(['erro' => 'Não foi possível carregar a pergunta. Verifique se o banco foi populado (seed/seed.php).']);
    exit;
}

echo json_encode($pergunta, JSON_UNESCAPED_UNICODE);
