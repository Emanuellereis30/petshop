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
$required = ['nome', 'preco', 'categoria'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        jsonResponse(false, "Campo obrigatório: $field");
    }
}

$nome = trim($data['nome']);
$preco = floatval($data['preco']);
$categoria = trim($data['categoria']);
$descricao = isset($data['descricao']) ? trim($data['descricao']) : '';
$imagem = isset($data['imagem']) ? trim($data['imagem']) : '📦';
$estoque = isset($data['estoque']) ? intval($data['estoque']) : 0;

// Validar preço
if ($preco <= 0) {
    jsonResponse(false, 'Preço deve ser maior que zero');
}

// Conectar ao banco
$conn = conectarDB();

// Inserir produto
$stmt = $conn->prepare("INSERT INTO produtos (nome, preco, categoria, descricao, imagem, estoque) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sdsssi", $nome, $preco, $categoria, $descricao, $imagem, $estoque);

if ($stmt->execute()) {
    $produtoId = $conn->insert_id;
    $stmt->close();
    $conn->close();
    
    jsonResponse(true, 'Produto cadastrado com sucesso', [
        'id' => $produtoId,
        'nome' => $nome,
        'preco' => $preco,
        'categoria' => $categoria,
        'descricao' => $descricao,
        'imagem' => $imagem,
        'estoque' => $estoque
    ]);
} else {
    $error = $stmt->error;
    $stmt->close();
    $conn->close();
    jsonResponse(false, 'Erro ao cadastrar produto: ' . $error);
}
?>