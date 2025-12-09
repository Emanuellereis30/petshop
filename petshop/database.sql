-- Banco de dados para o sistema Petshop
-- Execute este script no MySQL para criar o banco e as tabelas

CREATE DATABASE IF NOT EXISTS petshop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE petshop;

-- Tabela de usuários
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    tipo ENUM('cliente', 'entregador', 'admin') DEFAULT 'cliente',
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de produtos
CREATE TABLE IF NOT EXISTS produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    preco DECIMAL(10, 2) NOT NULL,
    categoria VARCHAR(100) NOT NULL,
    descricao TEXT,
    imagem VARCHAR(255),
    estoque INT DEFAULT 0,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de pedidos
CREATE TABLE IF NOT EXISTS pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL,
    total DECIMAL(10, 2) NOT NULL,
    status ENUM('pendente', 'processando', 'enviado', 'entregue', 'cancelado') DEFAULT 'pendente',
    nome_cliente VARCHAR(255) NOT NULL,
    email_cliente VARCHAR(255) NOT NULL,
    endereco_entrega TEXT NOT NULL,
    metodo_pagamento ENUM('cartao', 'pix') DEFAULT 'cartao',
    status_pagamento ENUM('processando', 'aprovado', 'recusado') DEFAULT 'processando',
    observacoes TEXT NULL,
    data_pedido TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de itens do pedido (opcional, para detalhar produtos do pedido)
CREATE TABLE IF NOT EXISTS pedido_itens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    produto_id INT NOT NULL,
    quantidade INT NOT NULL,
    preco_unitario DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de pagamentos
CREATE TABLE IF NOT EXISTS pagamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    metodo ENUM('cartao', 'pix') NOT NULL,
    valor DECIMAL(10, 2) NOT NULL,
    cartao_final VARCHAR(4) NULL,
    cartao_validade VARCHAR(7) NULL,
    pix_codigo VARCHAR(255) NULL,
    status ENUM('pendente', 'aprovado', 'recusado') DEFAULT 'pendente',
    data_pagamento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

-- Inserir alguns produtos de exemplo
INSERT INTO produtos (nome, preco, categoria, descricao, imagem, estoque) VALUES
('Ração Premium para Cães', 189.90, 'Alimentação', 'Ração super premium com proteínas de alta qualidade e nutrientes essenciais', 'img/racao.png', 50),
('Ração Premium para Gatos', 159.90, 'Alimentação', 'Alimento completo e balanceado para gatos adultos, rico em taurina', 'img/racaogato.png', 40),
('Brinquedo Interativo', 45.90, 'Brinquedos', 'Brinquedo resistente que estimula a atividade física e mental do seu pet', 'img/brinquedos.png', 30),
('Coleira Antipulgas', 79.90, 'Acessórios', 'Coleira com tecnologia de proteção contra pulgas e carrapatos por até 8 meses', 'img/coleira.png', 25),
('Cama Ortopédica', 249.90, 'Conforto', 'Cama confortável com espuma viscoelástica, ideal para pets idosos', 'img/cama.png', 15),
('Shampoo Hidratante', 34.90, 'Higiene', 'Shampoo especial para pelagem macia e brilhante, sem ressecamento', 'img/shamppo.png', 60),
('antipulgas', 54.90, 'Higiene', 'seja livre das pulgas', 'img/antipulgas.png', 80);