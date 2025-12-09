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
if (empty($data['entrega_id']) || empty($data['entregador_id'])) {
    jsonResponse(false, 'ID da entrega e ID do entregador são obrigatórios');
}

$entregaId = intval($data['entrega_id']);
$entregadorId = intval($data['entregador_id']);

// Conectar ao banco
$conn = conectarDB();

// Verificar se a entrega existe e está disponível
$stmt = $conn->prepare("SELECT id, status FROM entregas WHERE id = ?");
$stmt->bind_param("i", $entregaId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    jsonResponse(false, 'Entrega não encontrada');
}

$entrega = $result->fetch_assoc();

if ($entrega['status'] !== 'disponivel') {
    $stmt->close();
    $conn->close();
    jsonResponse(false, 'Esta entrega não está mais disponível');
}

$stmt->close();

// Verificar se o usuário é entregador
$stmt = $conn->prepare("SELECT id, tipo FROM usuarios WHERE id = ? AND tipo = 'entregador'");
$stmt->bind_param("i", $entregadorId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    jsonResponse(false, 'Usuário não é um entregador válido');
}

$stmt->close();

// Atualizar entrega
$stmt = $conn->prepare("UPDATE entregas SET entregador_id = ?, status = 'aceita', data_aceite = NOW() WHERE id = ?");
$stmt->bind_param("ii", $entregadorId, $entregaId);

if ($stmt->execute()) {
    // Atualizar status do pedido para 'enviado'
    $stmtPedido = $conn->prepare("UPDATE pedidos SET status = 'enviado' WHERE id = (SELECT pedido_id FROM entregas WHERE id = ?)");
    $stmtPedido->bind_param("i", $entregaId);
    $stmtPedido->execute();
    $stmtPedido->close();
    
    $stmt->close();
    $conn->close();
    
    jsonResponse(true, 'Entrega aceita com sucesso', ['entrega_id' => $entregaId]);
} else {
    $error = $stmt->error;
    $stmt->close();
    $conn->close();
    jsonResponse(false, 'Erro ao aceitar entrega: ' . $error);
}
?>