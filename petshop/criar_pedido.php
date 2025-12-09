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
if (empty($data['usuario_id']) || empty($data['total'])) {
    jsonResponse(false, 'Usuário ID e total são obrigatórios');
}

$usuarioId = intval($data['usuario_id']);
$total = floatval($data['total']);
$status = isset($data['status']) ? $data['status'] : 'pendente';
$itens = isset($data['itens']) ? $data['itens'] : [];
$enderecoEntrega = isset($data['endereco_entrega']) ? trim($data['endereco_entrega']) : '';
$observacoes = isset($data['observacoes']) ? trim($data['observacoes']) : '';

// Validar total
if ($total <= 0) {
    jsonResponse(false, 'Total deve ser maior que zero');
}

// Validar status
$statusPermitidos = ['pendente', 'processando', 'enviado', 'entregue', 'cancelado'];
if (!in_array($status, $statusPermitidos)) {
    $status = 'pendente';
}

// Conectar ao banco
$conn = conectarDB();

// Verificar se usuário existe
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $usuarioId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    jsonResponse(false, 'Usuário não encontrado');
}

$stmt->close();

// Iniciar transação
$conn->begin_transaction();

try {
    // Inserir pedido
    $stmt = $conn->prepare("INSERT INTO pedidos (usuario_id, total, status) VALUES (?, ?, ?)");
    $stmt->bind_param("ids", $usuarioId, $total, $status);
    
    if (!$stmt->execute()) {
        throw new Exception('Erro ao criar pedido: ' . $stmt->error);
    }
    
    $pedidoId = $conn->insert_id;
    $stmt->close();
    
    // Inserir itens do pedido (se houver)
    if (!empty($itens) && is_array($itens)) {
        $stmt = $conn->prepare("INSERT INTO pedido_itens (pedido_id, produto_id, quantidade, preco_unitario) VALUES (?, ?, ?, ?)");
        
        foreach ($itens as $item) {
            $produtoId = intval($item['produto_id']);
            $quantidade = intval($item['quantidade']);
            $precoUnitario = floatval($item['preco_unitario']);
            
            $stmt->bind_param("iiid", $pedidoId, $produtoId, $quantidade, $precoUnitario);
            
            if (!$stmt->execute()) {
                throw new Exception('Erro ao inserir item do pedido: ' . $stmt->error);
            }
        }
        
        $stmt->close();
    }
    
    // Criar entrega automaticamente se endereço foi fornecido
    if (!empty($enderecoEntrega)) {
        $stmt = $conn->prepare("INSERT INTO entregas (pedido_id, endereco_entrega, observacoes, status) VALUES (?, ?, ?, 'disponivel')");
        $stmt->bind_param("iss", $pedidoId, $enderecoEntrega, $observacoes);
        
        if (!$stmt->execute()) {
            throw new Exception('Erro ao criar entrega: ' . $stmt->error);
        }
        
        $stmt->close();
    }
    
    // Confirmar transação
    $conn->commit();
    $conn->close();
    
    jsonResponse(true, 'Pedido criado com sucesso', [
        'id' => $pedidoId,
        'usuario_id' => $usuarioId,
        'total' => $total,
        'status' => $status
    ]);
    
} catch (Exception $e) {
    // Reverter transação em caso de erro
    $conn->rollback();
    $conn->close();
    jsonResponse(false, $e->getMessage());
}
?>