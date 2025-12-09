-- Script para adicionar a tabela de entregas em um banco de dados existente
-- Execute este script se você já criou o banco de dados anteriormente

USE petshop;

-- Tabela de entregas
CREATE TABLE IF NOT EXISTS entregas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    entregador_id INT,
    status ENUM('disponivel', 'aceita', 'em-rota', 'entregue', 'cancelada') DEFAULT 'disponivel',
    endereco_entrega TEXT NOT NULL,
    observacoes TEXT,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_aceite TIMESTAMP NULL,
    data_saida TIMESTAMP NULL,
    data_entrega TIMESTAMP NULL,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (entregador_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_entregador (entregador_id),
    INDEX idx_pedido (pedido_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;