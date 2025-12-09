<?php
require_once 'config.php';

// Permitir requisições de diferentes origens (CORS)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Conectar ao banco
$conn = conectarDB();

// Verificar se há filtro de entregador
$entregadorId = isset($_GET['entregador_id']) ? intval($_GET['entregador_id']) : null;
$status = isset($_GET['status']) ? $_GET['status'] : null;

// Construir query
$sql = "SELECT 
    e.id,
    e.pedido_id,
    e.entregador_id,
    e.status,
    e.endereco_entrega,
    e.observacoes,
    e.data_criacao,
    e.data_aceite,
    e.data_saida,
    e.data_entrega,
    p.total,
    p.data_pedido,
    u_cliente.nome as cliente_nome,
    u_cliente.email as cliente_email,
    u_entregador.nome as entregador_nome
FROM entregas e
INNER JOIN pedidos p ON e.pedido_id = p.id
INNER JOIN usuarios u_cliente ON p.usuario_id = u_cliente.id
LEFT JOIN usuarios u_entregador ON e.entregador_id = u_entregador.id
WHERE 1=1";

$params = [];
$types = "";

if ($entregadorId !== null) {
    $sql .= " AND e.entregador_id = ?";
    $params[] = $entregadorId;
    $types .= "i";
}

if ($status !== null && in_array($status, ['disponivel', 'aceita', 'em-rota', 'entregue', 'cancelada'])) {
    $sql .= " AND e.status = ?";
    $params[] = $status;
    $types .= "s";
}

$sql .= " ORDER BY e.data_criacao DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$entregas = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Buscar itens do pedido
        $stmtItens = $conn->prepare("
            SELECT pi.quantidade, pi.preco_unitario, pr.nome as produto_nome, pr.imagem
            FROM pedido_itens pi
            INNER JOIN produtos pr ON pi.produto_id = pr.id
            WHERE pi.pedido_id = ?
        ");
        $stmtItens->bind_param("i", $row['pedido_id']);
        $stmtItens->execute();
        $resultItens = $stmtItens->get_result();
        
        $produtos = [];
        while ($item = $resultItens->fetch_assoc()) {
            $produtos[] = [
                'nome' => $item['produto_nome'],
                'quantidade' => intval($item['quantidade']),
                'preco' => floatval($item['preco_unitario']),
                'imagem' => $item['imagem']
            ];
        }
        $stmtItens->close();
        
        $row['total'] = floatval($row['total']);
        $row['produtos'] = $produtos;
        $entregas[] = $row;
    }
}

$stmt->close();
$conn->close();

jsonResponse(true, 'Entregas listadas com sucesso', $entregas);
?>