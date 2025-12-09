<?php
require_once 'config.php';

// Permitir requisições de diferentes origens (CORS)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Conectar ao banco
$conn = conectarDB();

// Buscar todos os produtos
$sql = "SELECT id, nome, preco, categoria, descricao, imagem, estoque, data_cadastro FROM produtos ORDER BY data_cadastro DESC";
$result = $conn->query($sql);

$produtos = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Converter preço para float
        $row['preco'] = floatval($row['preco']);
        $row['estoque'] = intval($row['estoque']);
        $produtos[] = $row;
    }
}

$conn->close();

jsonResponse(true, 'Produtos listados com sucesso', $produtos);
?>