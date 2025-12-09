<?php
// Configuração de conexão com o banco de dados MySQL

// Configurações do banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'petshop');
define('DB_USER', 'root');
define('DB_PASS', '');

// Função para conectar ao banco de dados
function conectarDB() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        // Verificar conexão
        if ($conn->connect_error) {
            die("Erro de conexão: " . $conn->connect_error);
        }
        
        // Definir charset para UTF-8
        $conn->set_charset("utf8mb4");
        
        return $conn;
    } catch (Exception $e) {
        die("Erro ao conectar ao banco de dados: " . $e->getMessage());
    }
}

// Função para retornar resposta JSON
function jsonResponse($success, $message, $data = null) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>