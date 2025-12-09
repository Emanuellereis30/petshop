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

$cliente = $data['cliente'] ?? [];
$pagamento = $data['pagamento'] ?? [];
$itens = $data['itens'] ?? [];
$total = isset($data['total']) ? floatval($data['total']) : 0;
$usuarioId = isset($data['usuario_id']) ? intval($data['usuario_id']) : null;
$observacoes = trim($data['observacoes'] ?? '');

if (empty($cliente['nome']) || strlen($cliente['nome']) < 3) {
    jsonResponse(false, 'Nome do cliente é obrigatório.');
}

if (empty($cliente['email']) || !filter_var($cliente['email'], FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, 'Email do cliente inválido.');
}

if (empty($cliente['endereco']) || strlen($cliente['endereco']) < 10) {
    jsonResponse(false, 'Endereço completo é obrigatório.');
}

$metodo = $pagamento['metodo'] ?? '';
if (!in_array($metodo, ['cartao', 'pix'])) {
    jsonResponse(false, 'Método de pagamento inválido.');
}

if ($metodo === 'cartao') {
    $numeroCartao = preg_replace('/\D/', '', $pagamento['numero_cartao'] ?? '');
    $validade = $pagamento['validade'] ?? '';
    $cvv = $pagamento['cvv'] ?? '';

    if (!preg_match('/^\d{13,19}$/', $numeroCartao)) {
        jsonResponse(false, 'Número do cartão inválido.');
    }
    if (!preg_match('/^(0[1-9]|1[0-2])\/?([0-9]{2})$/', $validade)) {
        jsonResponse(false, 'Validade do cartão inválida.');
    }
    if (!preg_match('/^\d{3,4}$/', $cvv)) {
        jsonResponse(false, 'CVV inválido.');
    }
} else {
    if (empty($pagamento['pix_codigo'])) {
        jsonResponse(false, 'Código PIX não informado.');
    }
}

if ($total <= 0 || empty($itens)) {
    jsonResponse(false, 'Carrinho inválido.');
}

$conn = conectarDB();
$conn->begin_transaction();

try {
    $stmtPedido = $conn->prepare("
        INSERT INTO pedidos (usuario_id, total, status, nome_cliente, email_cliente, endereco_entrega, metodo_pagamento, status_pagamento, observacoes)
        VALUES (?, ?, 'pendente', ?, ?, ?, ?, 'processando', ?)
    ");
    $stmtPedido->bind_param(
        "idsssss",
        $usuarioId,
        $total,
        $cliente['nome'],
        $cliente['email'],
        $cliente['endereco'],
        $metodo,
        $observacoes
    );
    $stmtPedido->execute();
    $pedidoId = $stmtPedido->insert_id;
    $stmtPedido->close();

    $stmtItem = $conn->prepare("
        INSERT INTO pedido_itens (pedido_id, produto_id, quantidade, preco_unitario)
        VALUES (?, ?, ?, ?)
    ");

    foreach ($itens as $item) {
        $produtoId = isset($item['produto_id']) ? intval($item['produto_id']) : intval($item['id'] ?? 0);
        $quantidade = intval($item['quantidade'] ?? 0);
        $preco = floatval($item['preco'] ?? 0);

        if ($produtoId <= 0 || $quantidade <= 0 || $preco < 0) {
            throw new Exception('Item do carrinho inválido.');
        }

        $stmtItem->bind_param("iiid", $pedidoId, $produtoId, $quantidade, $preco);
        $stmtItem->execute();
    }
    $stmtItem->close();

    $cartaoFinal = null;
    $cartaoValidade = null;
    $pixCodigo = null;

    if ($metodo === 'cartao') {
        $numeroCartao = preg_replace('/\D/', '', $pagamento['numero_cartao']);
        $cartaoFinal = substr($numeroCartao, -4);
        $cartaoValidade = $pagamento['validade'];
    } else {
        $pixCodigo = $pagamento['pix_codigo'];
    }

    $stmtPagamento = $conn->prepare("
        INSERT INTO pagamentos (pedido_id, metodo, valor, cartao_final, cartao_validade, pix_codigo, status)
        VALUES (?, ?, ?, ?, ?, ?, 'pendente')
    ");
    $stmtPagamento->bind_param(
        "isdsss",
        $pedidoId,
        $metodo,
        $total,
        $cartaoFinal,
        $cartaoValidade,
        $pixCodigo
    );
    $stmtPagamento->execute();
    $stmtPagamento->close();

    $conn->commit();

    jsonResponse(true, 'Pagamento registrado com sucesso!', [
        'pedido_id' => $pedidoId
    ]);
} catch (Exception $e) {
    $conn->rollback();
    jsonResponse(false, 'Erro ao registrar pagamento: ' . $e->getMessage());
}
?>

