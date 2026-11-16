<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!estaLogado()) {
    http_response_code(401);
    echo json_encode(['erro' => 'Sessão expirada. Faça login novamente.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido.']);
    exit;
}

$categoria = $_POST['categoria'] ?? '';
$itemId = (int) ($_POST['item_id'] ?? 0);
$resposta = (string) ($_POST['resposta'] ?? '');

if (!isset(CATEGORIAS[$categoria]) || $itemId <= 0) {
    http_response_code(400);
    echo json_encode(['erro' => 'Dados inválidos.']);
    exit;
}

$resultado = responderPergunta((int) $_SESSION['usuario_id'], $categoria, $itemId, $resposta);

if ($resultado === null) {
    http_response_code(400);
    echo json_encode(['erro' => 'Item não encontrado.']);
    exit;
}

echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
