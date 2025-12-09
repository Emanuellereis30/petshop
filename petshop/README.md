# Sistema Petshop - Integração Front-End e Back-End

Sistema de e-commerce para petshop desenvolvido com HTML, CSS, JavaScript, PHP e MySQL.

## Estrutura do Projeto

### Arquivos Front-End
- `index.html` - Página principal com listagem de produtos
- `login.html` - Página de login
- `cadastro.html` - Cadastro de usuários e produtos
- `registro-produto.html` - Registro de novos produtos
- `listagem-produtos.html` - Listagem completa de produtos
- `pedidos.html` - Acompanhamento de pedidos
- `entregador.html` - Área do entregador
- `script.js` - JavaScript principal
- `styles.css` - Estilos CSS

### Arquivos Back-End
- `config.php` - Configuração de conexão com banco de dados
- `cadastro_usuario.php` - API para cadastro de usuários
- `login.php` - API para autenticação
- `cadastro_produto.php` - API para cadastro de produtos
- `listar_produtos.php` - API para listar produtos
- `criar_pedido.php` - API para criar pedidos
- `database.sql` - Script SQL para criar o banco de dados

## Instalação

### Pré-requisitos
- XAMPP (ou similar) com PHP e MySQL
- Navegador web moderno

### Passos para Instalação

1. **Configurar o Banco de Dados**
   - Abra o phpMyAdmin (http://localhost/phpmyadmin)
   - Execute o arquivo `database.sql` para criar o banco de dados e as tabelas
   - Ou execute via linha de comando:
     ```bash
     mysql -u root -p < database.sql
     ```

2. **Configurar a Conexão**
   - Abra o arquivo `config.php`
   - Ajuste as credenciais do banco de dados se necessário:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_NAME', 'petshop');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     ```

3. **Colocar Arquivos no Servidor**
   - Copie todos os arquivos para a pasta `htdocs` do XAMPP:
     ```
     C:\XAMPP\htdocs\avaliacao4\
     ```

4. **Acessar o Sistema**
   - Abra o navegador e acesse:
     ```
     http://localhost/avaliacao4/
     ```

## Funcionalidades Implementadas

### ✅ Cadastro de Usuários
- Formulário de cadastro com validação
- Senha criptografada com `password_hash()`
- Verificação de email duplicado
- Tipos de usuário: cliente, entregador, admin

### ✅ Login
- Autenticação via email e senha
- Verificação de credenciais no banco de dados
- Redirecionamento baseado no tipo de usuário

### ✅ Cadastro de Produtos
- Formulário completo com validação
- Campos: nome, preço, categoria, descrição, imagem, estoque
- Salvamento no banco de dados MySQL

### ✅ Listagem de Produtos
- Carregamento dinâmico do banco de dados
- Filtros por categoria e estoque
- Busca por nome

### ✅ Criação de Pedidos
- Carrinho de compras funcional
- Finalização de pedido com salvamento no banco
- Associação com usuário logado

## Estrutura do Banco de Dados

### Tabela: usuarios
- `id` (INT, AUTO_INCREMENT, PRIMARY KEY)
- `nome` (VARCHAR 255)
- `email` (VARCHAR 255, UNIQUE)
- `senha` (VARCHAR 255) - Hash da senha
- `tipo` (ENUM: 'cliente', 'entregador', 'admin')
- `data_cadastro` (TIMESTAMP)

### Tabela: produtos
- `id` (INT, AUTO_INCREMENT, PRIMARY KEY)
- `nome` (VARCHAR 255)
- `preco` (DECIMAL 10,2)
- `categoria` (VARCHAR 100)
- `descricao` (TEXT)
- `imagem` (VARCHAR 255)
- `estoque` (INT)
- `data_cadastro` (TIMESTAMP)

### Tabela: pedidos
- `id` (INT, AUTO_INCREMENT, PRIMARY KEY)
- `usuario_id` (INT, FOREIGN KEY)
- `total` (DECIMAL 10,2)
- `status` (ENUM: 'pendente', 'processando', 'enviado', 'entregue', 'cancelado')
- `data_pedido` (TIMESTAMP)

### Tabela: pedido_itens
- `id` (INT, AUTO_INCREMENT, PRIMARY KEY)
- `pedido_id` (INT, FOREIGN KEY)
- `produto_id` (INT, FOREIGN KEY)
- `quantidade` (INT)
- `preco_unitario` (DECIMAL 10,2)

### Tabela: entregas
- `id` (INT, AUTO_INCREMENT, PRIMARY KEY)
- `pedido_id` (INT, FOREIGN KEY)
- `entregador_id` (INT, FOREIGN KEY, NULL)
- `status` (ENUM: 'disponivel', 'aceita', 'em-rota', 'entregue', 'cancelada')
- `endereco_entrega` (TEXT)
- `observacoes` (TEXT)
- `data_criacao` (TIMESTAMP)
- `data_aceite` (TIMESTAMP, NULL)
- `data_saida` (TIMESTAMP, NULL)
- `data_entrega` (TIMESTAMP, NULL)

## APIs Disponíveis

### POST /cadastro_usuario.php
Cadastra um novo usuário.
```json
{
  "nome": "João Silva",
  "email": "joao@email.com",
  "senha": "senha123",
  "tipo": "cliente"
}
```

### POST /login.php
Autentica um usuário.
```json
{
  "email": "joao@email.com",
  "senha": "senha123"
}
```

### POST /cadastro_produto.php
Cadastra um novo produto.
```json
{
  "nome": "Ração Premium",
  "preco": 189.90,
  "categoria": "Alimentação",
  "descricao": "Descrição do produto",
  "imagem": "🐕",
  "estoque": 50
}
```

### GET /listar_produtos.php
Lista todos os produtos cadastrados.

### POST /criar_pedido.php
Cria um novo pedido.
```json
{
  "usuario_id": 1,
  "total": 425.70,
  "status": "pendente",
  "endereco_entrega": "Rua Exemplo, 123 - São Paulo, SP",
  "itens": [
    {
      "produto_id": 1,
      "quantidade": 2,
      "preco_unitario": 189.90
    }
  ]
}
```

### GET /listar_entregas.php
Lista entregas. Parâmetros opcionais:
- `entregador_id` - Filtrar por entregador
- `status` - Filtrar por status

### POST /aceitar_entrega.php
Aceita uma entrega disponível.
```json
{
  "entrega_id": 1,
  "entregador_id": 2
}
```

### POST /atualizar_status_entrega.php
Atualiza o status de uma entrega.
```json
{
  "entrega_id": 1,
  "status": "em-rota",
  "entregador_id": 2
}
```

### POST /criar_entrega.php
Cria uma nova entrega manualmente.
```json
{
  "pedido_id": 1,
  "endereco_entrega": "Rua Exemplo, 123 - São Paulo, SP",
  "observacoes": "Observações opcionais"
}
```

## Notas Importantes

- As senhas são criptografadas usando `password_hash()` do PHP
- Todos os scripts PHP retornam JSON com estrutura:
  ```json
  {
    "success": true/false,
    "message": "Mensagem de resposta",
    "data": { ... }
  }
  ```
- O sistema usa `fetch()` para comunicação entre front-end e back-end
- O carrinho ainda usa `localStorage` para persistência temporária
- Algumas funcionalidades como listar pedidos do banco ainda precisam ser implementadas

## Desenvolvido para

Avaliação Formadora 04 - Back-End
Curso: Análise e Desenvolvimento de Sistemas
Módulo II - Back-End

