<?php
require_once 'config.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Método não permitido');
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    $data = $_POST;
}

if (empty($data['email']) || empty($data['nova_senha'])) {
    jsonResponse(false, 'Email e nova senha são obrigatórios');
}

$email = trim($data['email']);
$novaSenha = $data['nova_senha'];

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, 'Email inválido');
}

if (strlen($novaSenha) < 6) {
    jsonResponse(false, 'A nova senha deve ter pelo menos 6 caracteres');
}

$conn = conectarDB();

$stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    jsonResponse(false, 'Email não encontrado');
}

$stmt->close();

$novaSenhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);

$updateStmt = $conn->prepare("UPDATE usuarios SET senha = ? WHERE email = ?");
$updateStmt->bind_param("ss", $novaSenhaHash, $email);

if ($updateStmt->execute()) {
    $updateStmt->close();
    $conn->close();
    jsonResponse(true, 'Senha redefinida com sucesso');
} else {
    $erro = $updateStmt->error;
    $updateStmt->close();
    $conn->close();
    jsonResponse(false, 'Erro ao atualizar senha: ' . $erro);
}
?>

