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
if (empty($data['entrega_id']) || empty($data['status'])) {
    jsonResponse(false, 'ID da entrega e status são obrigatórios');
}

$entregaId = intval($data['entrega_id']);
$status = $data['status'];

// Validar status
$statusPermitidos = ['aceita', 'em-rota', 'entregue', 'cancelada'];
if (!in_array($status, $statusPermitidos)) {
    jsonResponse(false, 'Status inválido');
}

// Conectar ao banco
$conn = conectarDB();

// Verificar se a entrega existe
$stmt = $conn->prepare("SELECT id, entregador_id FROM entregas WHERE id = ?");
$stmt->bind_param("i", $entregaId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    jsonResponse(false, 'Entrega não encontrada');
}

$entrega = $result->fetch_assoc();
$stmt->close();

// Verificar se o entregador tem permissão (se fornecido)
if (isset($data['entregador_id'])) {
    $entregadorId = intval($data['entregador_id']);
    if ($entrega['entregador_id'] != $entregadorId) {
        $conn->close();
        jsonResponse(false, 'Você não tem permissão para atualizar esta entrega');
    }
}

// Determinar qual campo de data atualizar
$dataField = '';
switch ($status) {
    case 'em-rota':
        $dataField = 'data_saida = NOW()';
        break;
    case 'entregue':
        $dataField = 'data_entrega = NOW()';
        // Atualizar status do pedido para 'entregue'
        $stmtPedido = $conn->prepare("UPDATE pedidos SET status = 'entregue' WHERE id = (SELECT pedido_id FROM entregas WHERE id = ?)");
        $stmtPedido->bind_param("i", $entregaId);
        $stmtPedido->execute();
        $stmtPedido->close();
        break;
}

// Atualizar entrega
$sql = "UPDATE entregas SET status = ?";
if ($dataField) {
    $sql .= ", " . $dataField;
}
$sql .= " WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $status, $entregaId);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    
    jsonResponse(true, 'Status da entrega atualizado com sucesso', [
        'entrega_id' => $entregaId,
        'status' => $status
    ]);
} else {
    $error = $stmt->error;
    $stmt->close();
    $conn->close();
    jsonResponse(false, 'Erro ao atualizar status: ' . $error);
}
?>