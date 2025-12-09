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

if (!$data) {
    $data = $_POST;
}

// Validar campos obrigatórios
if (empty($data['pedido_id']) || empty($data['endereco_entrega'])) {
    jsonResponse(false, 'ID do pedido e endereço de entrega são obrigatórios');
}

$pedidoId = intval($data['pedido_id']);
$enderecoEntrega = trim($data['endereco_entrega']);
$observacoes = isset($data['observacoes']) ? trim($data['observacoes']) : '';

// Conectar ao banco
$conn = conectarDB();

// Verificar se o pedido existe
$stmt = $conn->prepare("SELECT id, status FROM pedidos WHERE id = ?");
$stmt->bind_param("i", $pedidoId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    jsonResponse(false, 'Pedido não encontrado');
}

$pedido = $result->fetch_assoc();

// Verificar se já existe uma entrega para este pedido
$stmt = $conn->prepare("SELECT id FROM entregas WHERE pedido_id = ?");
$stmt->bind_param("i", $pedidoId);
$stmt->execute();
$resultEntrega = $stmt->get_result();

if ($resultEntrega->num_rows > 0) {
    $stmt->close();
    $conn->close();
    jsonResponse(false, 'Já existe uma entrega para este pedido');
}

$stmt->close();

// Criar entrega
$stmt = $conn->prepare("INSERT INTO entregas (pedido_id, endereco_entrega, observacoes, status) VALUES (?, ?, ?, 'disponivel')");
$stmt->bind_param("iss", $pedidoId, $enderecoEntrega, $observacoes);

if ($stmt->execute()) {
    $entregaId = $conn->insert_id;
    
    // Atualizar status do pedido para 'processando'
    $stmtPedido = $conn->prepare("UPDATE pedidos SET status = 'processando' WHERE id = ?");
    $stmtPedido->bind_param("i", $pedidoId);
    $stmtPedido->execute();
    $stmtPedido->close();
    
    $stmt->close();
    $conn->close();
    
    jsonResponse(true, 'Entrega criada com sucesso', [
        'id' => $entregaId,
        'pedido_id' => $pedidoId,
        'status' => 'disponivel'
    ]);
} else {
    $error = $stmt->error;
    $stmt->close();
    $conn->close();
    jsonResponse(false, 'Erro ao criar entrega: ' . $error);
}
?>