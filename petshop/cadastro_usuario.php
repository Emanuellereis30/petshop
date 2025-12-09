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
$required = ['nome', 'email', 'senha'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        jsonResponse(false, "Campo obrigatório: $field");
    }
}

$nome = trim($data['nome']);
$email = trim($data['email']);
$senha = $data['senha'];
$tipo = isset($data['tipo']) ? $data['tipo'] : 'cliente';

// Validar email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, 'Email inválido');
}

// Validar tipo de usuário
$tiposPermitidos = ['cliente', 'entregador', 'admin'];
if (!in_array($tipo, $tiposPermitidos)) {
    $tipo = 'cliente';
}

// Conectar ao banco
$conn = conectarDB();

// Verificar se email já existe
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $stmt->close();
    $conn->close();
    jsonResponse(false, 'Email já cadastrado');
}

// Hash da senha
$senhaHash = password_hash($senha, PASSWORD_DEFAULT);

// Inserir usuário
$stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $nome, $email, $senhaHash, $tipo);

if ($stmt->execute()) {
    $userId = $conn->insert_id;
    $stmt->close();
    $conn->close();
    
    jsonResponse(true, 'Usuário cadastrado com sucesso', [
        'id' => $userId,
        'nome' => $nome,
        'email' => $email,
        'tipo' => $tipo
    ]);
} else {
    $error = $stmt->error;
    $stmt->close();
    $conn->close();
    jsonResponse(false, 'Erro ao cadastrar usuário: ' . $error);
}
?>