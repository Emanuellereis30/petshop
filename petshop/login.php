<?php
require_once 'config.php';

// Permitir requisições de diferentes origens (CORS)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Método não permitido');
}

// Receber dados JSON
$data = json_decode(file_get_contents('php://input'), true);

// Se não vier JSON, tentar receber via POST tradicional
if (!$data) {
    $data = $_POST;
}

// Validar campos obrigatórios
if (empty($data['email']) || empty($data['senha'])) {
    jsonResponse(false, 'Email e senha são obrigatórios');
}

$email = trim($data['email']);
$senha = $data['senha'];

// Conectar ao banco
$conn = conectarDB();

// Buscar usuário
$stmt = $conn->prepare("SELECT id, nome, email, senha, tipo FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    jsonResponse(false, 'Email ou senha incorretos');
}

$usuario = $result->fetch_assoc();

// Verificar senha
if (!password_verify($senha, $usuario['senha'])) {
    $stmt->close();
    $conn->close();
    jsonResponse(false, 'Email ou senha incorretos');
}

// Login bem-sucedido
$stmt->close();
$conn->close();

// Retornar dados do usuário (sem a senha)
unset($usuario['senha']);

jsonResponse(true, 'Login realizado com sucesso', $usuario);
?>