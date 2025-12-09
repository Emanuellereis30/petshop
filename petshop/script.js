// Dados dos produtos (será carregado do banco de dados)
let produtos = [];

// Carrinho
let carrinho = JSON.parse(localStorage.getItem('carrinho')) || [];

// Normalizar carrinho ao carregar (garantir que preços e quantidades sejam números)
function normalizarCarrinho() {
    carrinho = carrinho.map(item => ({
        ...item,
        id: parseInt(item.id) || item.id,
        preco: parseFloat(item.preco) || 0,
        quantidade: parseInt(item.quantidade) || 1
    }));
    salvarCarrinho();
}

// Normalizar ao carregar
if (carrinho.length > 0) {
    normalizarCarrinho();
}

// Carregar produtos do banco de dados
async function carregarProdutos() {
    try {
        const response = await fetch('listar_produtos.php');
        const result = await response.json();
        
        if (result.success) {
            produtos = result.data;
            // Normalizar produtos (garantir que preços sejam números)
            produtos = produtos.map(produto => ({
                ...produto,
                id: parseInt(produto.id) || produto.id,
                preco: parseFloat(produto.preco) || 0
            }));
            renderizarProdutos();
            // Atualizar carrinho após carregar produtos
            normalizarCarrinho();
            atualizarCarrinho();
        } else {
            console.error('Erro ao carregar produtos:', result.message);
            // Usar produtos padrão em caso de erro
            produtos = [
                {
                    id: 1,
                    nome: "Ração Premium para Cães",
                    descricao: "Ração super premium com proteínas de alta qualidade e nutrientes essenciais",
                    preco: 189.90,
                    imagem: "🐕",
                    categoria: "Alimentação"
                },
                {
                    id: 2,
                    nome: "Ração Premium para Gatos",
                    descricao: "Alimento completo e balanceado para gatos adultos, rico em taurina",
                    preco: 159.90,
                    imagem: "🐱",
                    categoria: "Alimentação"
                }
            ];
            renderizarProdutos();
        }
    } catch (error) {
        console.error('Erro ao carregar produtos:', error);
        renderizarProdutos();
    }
}

// Verificar e atualizar status de login
function atualizarStatusLogin() {
    const loggedUser = JSON.parse(localStorage.getItem('loggedUser'));
    const userInfo = document.getElementById('userInfo');
    const loginBtn = document.getElementById('loginBtn');
    const cadastroBtn = document.getElementById('cadastroBtn');
    const userName = document.getElementById('userName');

    if (loggedUser && loggedUser.id) {
        // Usuário logado
        if (userInfo) userInfo.style.display = 'flex';
        if (userName) userName.textContent = loggedUser.nome || loggedUser.email;
        if (loginBtn) loginBtn.style.display = 'none';
        if (cadastroBtn) cadastroBtn.style.display = 'none';
    } else {
        // Usuário não logado
        if (userInfo) userInfo.style.display = 'none';
        if (loginBtn) loginBtn.style.display = 'inline-flex';
        if (cadastroBtn) cadastroBtn.style.display = 'inline-flex';
    }
}

// Função de logout
function logout() {
    if (confirm('Deseja realmente sair?')) {
        localStorage.removeItem('loggedUser');
        window.location.href = 'index.html';
    }
}

// Função para abrir carrinho (fallback)
function abrirCarrinho() {
    const cartModal = document.getElementById('cartModal');
    if (cartModal) {
        cartModal.classList.add('active');
        atualizarCarrinho();
    } else {
        console.error('Modal do carrinho não encontrado!');
        alert('Erro ao abrir carrinho. Por favor, recarregue a página.');
    }
}

// Função para fechar carrinho (fallback)
function fecharCarrinho() {
    const cartModal = document.getElementById('cartModal');
    if (cartModal) {
        cartModal.classList.remove('active');
        console.log('Carrinho fechado');
    } else {
        console.error('Modal do carrinho não encontrado!');
    }
}

// Inicialização
document.addEventListener('DOMContentLoaded', () => {
    normalizarCarrinho(); // Garantir que o carrinho está normalizado
    atualizarStatusLogin();
    carregarProdutos();
    atualizarCarrinho();
    setupEventListeners();
});

// Renderizar produtos
function renderizarProdutos() {
    const produtosGrid = document.getElementById('produtosGrid');

    produtosGrid.innerHTML = produtos.map(produto => {

        // --- IMAGEM FINAL DE VERDADE ---
        // Se no banco vier emoji, vamos trocar por imagens reais
        let imagemFinal = produto.imagem ? produto.imagem.trim() : "";

        // Mapeamento caso ainda existam emojis no banco:
        const mapaEmojisParaImagens = {
            "🐕": "img/racao.png",
            "🐶": "img/brinquedos.png",
            "🐱": "img/racaogato.png",
            "🐾": "img/petisco.png"
        };

        // Remover caracteres invisíveis (caso existam emojis compostos)
        imagemFinal = imagemFinal.replace(/\u200D/g, "");

        // Verificar se é emoji REAL
        const isEmoji = /\p{Extended_Pictographic}/u.test(imagemFinal);

        if (isEmoji) {
            // Se for emoji → troca pela imagem PNG
            imagemFinal = mapaEmojisParaImagens[imagemFinal] || "img/produto.png";
        }

        // Se NÃO for emoji → é caminho de imagem mesmo (ex: img/racao1.png)
        return `
        <div class="produto-card">
            <div class="produto-image">
                <img src="${imagemFinal}" alt="${produto.nome}" class="produto-img-real">
            </div>

            <div class="produto-info">
                <div class="produto-categoria">${produto.categoria || 'Produto'}</div>
                <h3 class="produto-nome">${produto.nome}</h3>
                <p class="produto-descricao">${produto.descricao}</p>
                <div class="produto-preco">
                    R$ ${produto.preco.toFixed(2).replace('.', ',')}
                </div>
                <button class="btn-add-cart" onclick="adicionarAoCarrinho(${produto.id})">
                    <i class="fas fa-cart-plus"></i> Adicionar ao Carrinho
                </button>
            </div>
        </div>`;
    }).join('');
}



// Adicionar ao carrinho
function adicionarAoCarrinho(produtoId) {
    const produto = produtos.find(p => p.id === produtoId);
    
    if (!produto) {
        console.error('Produto não encontrado:', produtoId);
        alert('Erro: Produto não encontrado!');
        return;
    }

    const itemExistente = carrinho.find(item => item.id === produtoId);

    if (itemExistente) {
        itemExistente.quantidade = (itemExistente.quantidade || 0) + 1;
    } else {
        carrinho.push({ 
            ...produto, 
            quantidade: 1,
            preco: parseFloat(produto.preco) || 0
        });
    }

    salvarCarrinho();
    atualizarCarrinho();
    mostrarNotificacao(`${produto.nome} adicionado ao carrinho!`);
    
    console.log('Item adicionado:', produto.nome, 'Carrinho:', carrinho);
}

// Remover do carrinho
function removerDoCarrinho(produtoId) {
    carrinho = carrinho.filter(item => item.id !== produtoId);
    salvarCarrinho();
    atualizarCarrinho();
    mostrarNotificacao('Item removido do carrinho!');
}

// Limpar carrinho
function limparCarrinho() {
    carrinho = [];
    salvarCarrinho();
    atualizarCarrinho();
    resetPaymentForm();
    mostrarNotificacao('Carrinho limpo!');
}

// Atualizar carrinho
function atualizarCarrinho() {
    const cartCount = document.getElementById('cartCount');
    const totalItens = carrinho.reduce((sum, item) => sum + (item.quantidade || 0), 0);
    
    if (cartCount) {
        cartCount.textContent = totalItens;
    }

    const cartItems = document.getElementById('cartItems');
    const cartTotal = document.getElementById('cartTotal');

    if (!cartItems || !cartTotal) {
        console.warn('Elementos do carrinho não encontrados');
        return;
    }

    if (carrinho.length === 0) {
        cartItems.innerHTML = '<div class="empty-cart">Seu carrinho está vazio</div>';
        cartTotal.textContent = 'R$ 0,00';
    } else {
        cartItems.innerHTML = carrinho.map(item => {
            const preco = parseFloat(item.preco) || 0;
            const quantidade = parseInt(item.quantidade) || 0;
            const subtotal = preco * quantidade;
            
            return `
            <div class="cart-item">
                <div class="cart-item-info">
                    <div class="cart-item-name">${item.nome || 'Produto'}</div>
                    <div class="cart-item-price">
                        R$ ${preco.toFixed(2).replace('.', ',')} x ${quantidade} = R$ ${subtotal.toFixed(2).replace('.', ',')}
                    </div>
                </div>
                <button class="cart-item-remove" onclick="removerDoCarrinho(${item.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
        }).join('');

        // Calcular total
        const total = carrinho.reduce((sum, item) => {
            const preco = parseFloat(item.preco) || 0;
            const quantidade = parseInt(item.quantidade) || 0;
            return sum + (preco * quantidade);
        }, 0);
        
        cartTotal.textContent = `R$ ${total.toFixed(2).replace('.', ',')}`;
        console.log('Carrinho atualizado - Total:', total, 'Itens:', carrinho.length);
    }
}

// Salvar carrinho no localStorage
function salvarCarrinho() {
    localStorage.setItem('carrinho', JSON.stringify(carrinho));
}

// Setup event listeners
function setupEventListeners() {
    // Modal do carrinho
    const cartBtn = document.getElementById('cartBtn');
    const cartModal = document.getElementById('cartModal');
    const closeCart = document.getElementById('closeCart');
    const clearCart = document.getElementById('clearCart');
    const checkoutBtn = document.getElementById('checkoutBtn');

    // Verificar se os elementos existem
    if (!cartBtn) {
        console.error('Botão do carrinho não encontrado!');
        return;
    }

    if (!cartModal) {
        console.error('Modal do carrinho não encontrado!');
        return;
    }

    // Abrir modal do carrinho
    cartBtn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        console.log('Botão do carrinho clicado');
        if (cartModal) {
            cartModal.classList.add('active');
            atualizarCarrinho(); // Atualizar conteúdo ao abrir
        }
    });

    // Fechar modal - botão X
    if (closeCart) {
        closeCart.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            fecharCarrinho();
        });
    }

    // Fechar ao clicar fora do modal (no fundo escuro)
    if (cartModal) {
        cartModal.addEventListener('click', (e) => {
            // Fechar apenas se clicar diretamente no modal (fundo), não no conteúdo
            if (e.target === cartModal) {
                e.preventDefault();
                e.stopPropagation();
                fecharCarrinho();
            }
        });
        
        // Prevenir que cliques no conteúdo fechem o modal
        const modalContent = cartModal.querySelector('.modal-content');
        if (modalContent) {
            modalContent.addEventListener('click', (e) => {
                e.stopPropagation();
            });
        }
    }

    // Fechar com tecla ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && cartModal && cartModal.classList.contains('active')) {
            fecharCarrinho();
        }
    });

    // Limpar carrinho
    if (clearCart) {
        clearCart.addEventListener('click', () => {
            if (carrinho.length > 0) {
                if (confirm('Tem certeza que deseja limpar o carrinho?')) {
                    limparCarrinho();
                }
            }
        });
    }

    // Finalizar compra
    if (!checkoutBtn) {
        console.error('Botão de checkout não encontrado!');
    } else {
        checkoutBtn.addEventListener('click', async () => {
            if (carrinho.length === 0) {
                alert('Seu carrinho está vazio!');
                return;
            }

            const loggedUser = JSON.parse(localStorage.getItem('loggedUser'));
            if (!loggedUser || !loggedUser.id) {
                alert('Por favor, faça login para finalizar a compra!');
                window.location.href = 'login.html';
                return;
            }

            const total = carrinho.reduce((sum, item) => sum + (item.preco * item.quantidade), 0);
            const validation = coletarDadosPagamento();

            if (!validation.valid) {
                mostrarMensagemPagamento('error', validation.message);
                return;
            }

            const payload = {
                usuario_id: loggedUser.id,
                cliente: validation.data.cliente,
                pagamento: validation.data.pagamento,
                itens: carrinho.map(item => ({
                    id: item.id,
                    nome: item.nome,
                    quantidade: item.quantidade,
                    preco: item.preco
                })),
                total: total,
                observacoes: validation.data.observacoes
            };

            checkoutBtn.disabled = true;
            checkoutBtn.textContent = 'Processando...';
            mostrarMensagemPagamento('info', '<i class="fas fa-spinner fa-spin"></i> Processando seu pagamento, aguarde...');

            try {
                const response = await fetch('process_payment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (result.success) {
                    mostrarMensagemPagamento('success', `<i class="fas fa-check-circle"></i> Pagamento confirmado! Pedido #${result?.data?.pedido_id}`);
                    setTimeout(() => {
                        limparCarrinho();
                        fecharCarrinho();
                        checkoutBtn.disabled = false;
                        checkoutBtn.textContent = 'Finalizar Compra';
                    }, 1200);
                } else {
                    mostrarMensagemPagamento('error', `<i class="fas fa-exclamation-circle"></i> ${result.message || 'Não foi possível finalizar o pagamento.'}`);
                    checkoutBtn.disabled = false;
                    checkoutBtn.textContent = 'Finalizar Compra';
                }
            } catch (error) {
                mostrarMensagemPagamento('error', `<i class="fas fa-exclamation-circle"></i> Erro ao processar pagamento: ${error.message}`);
                checkoutBtn.disabled = false;
                checkoutBtn.textContent = 'Finalizar Compra';
            }
        });
    }

    // Formulário de contato
    const contatoForm = document.getElementById('contatoForm');
    if (contatoForm) {
        contatoForm.addEventListener('submit', (e) => {
            e.preventDefault();
            alert('Mensagem enviada com sucesso! Entraremos em contato em breve.');
            contatoForm.reset();
        });
    }

    // Menu mobile
    const menuToggle = document.getElementById('menuToggle');
    const nav = document.querySelector('.nav');
    
    if (menuToggle && nav) {
        menuToggle.addEventListener('click', () => {
            nav.style.display = nav.style.display === 'block' ? 'none' : 'block';
        });
    }

    // Smooth scroll
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            if (!targetId || targetId.length <= 1) {
                return;
            }
            const target = document.querySelector(targetId);
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
                if (window.innerWidth <= 768 && nav) {
                    nav.style.display = 'none';
                }
            }
        });
    });

    initializePaymentFormControles();
}

function initializePaymentFormControles() {
    const paymentForm = document.getElementById('paymentForm');
    if (!paymentForm) {
        return;
    }

    const metodoInputs = paymentForm.querySelectorAll('input[name="paymentMethod"]');
    metodoInputs.forEach(input => {
        input.addEventListener('change', () => toggleCamposPagamento(input.value));
    });

    const metodoInicial = paymentForm.querySelector('input[name="paymentMethod"]:checked');
    toggleCamposPagamento(metodoInicial ? metodoInicial.value : 'cartao');

    const pixCodeText = document.getElementById('pixCodeText');
    if (pixCodeText) {
        pixCodeText.textContent = gerarCodigoPix();
    }
}

function toggleCamposPagamento(metodo) {
    const cardFields = document.getElementById('cardFields');
    const pixFields = document.getElementById('pixFields');

    if (cardFields) {
        cardFields.classList.toggle('active', metodo === 'cartao');
    }
    if (pixFields) {
        pixFields.classList.toggle('active', metodo === 'pix');
    }

    document.querySelectorAll('.payment-radio').forEach(label => {
        const input = label.querySelector('input[name="paymentMethod"]');
        if (input) {
            label.classList.toggle('active', input.value === metodo);
        }
    });

    if (metodo === 'pix') {
        const pixCodeText = document.getElementById('pixCodeText');
        if (pixCodeText) {
            pixCodeText.textContent = gerarCodigoPix();
        }
    }
}

function coletarDadosPagamento() {
    const paymentForm = document.getElementById('paymentForm');
    if (!paymentForm) {
        return { valid: false, message: 'Formulário de pagamento não encontrado.' };
    }

    const nome = document.getElementById('clienteNome')?.value.trim() || '';
    const email = document.getElementById('clienteEmail')?.value.trim() || '';
    const endereco = document.getElementById('clienteEndereco')?.value.trim() || '';
    const metodo = paymentForm.querySelector('input[name="paymentMethod"]:checked')?.value;
    const observacoes = document.getElementById('orderNotes')?.value.trim() || '';

    if (nome.length < 3) {
        return { valid: false, message: 'Informe um nome completo válido.' };
    }

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        return { valid: false, message: 'Informe um e-mail válido.' };
    }

    if (endereco.length < 10) {
        return { valid: false, message: 'Informe o endereço completo com rua, número e cidade.' };
    }

    if (!metodo) {
        return { valid: false, message: 'Selecione um método de pagamento.' };
    }

    const data = {
        cliente: { nome, email, endereco },
        pagamento: { metodo },
        observacoes
    };

    if (metodo === 'cartao') {
        const numero = (document.getElementById('cardNumber')?.value || '').replace(/\s|-/g, '');
        const validade = document.getElementById('cardExpiry')?.value.trim() || '';
        const cvv = document.getElementById('cardCvv')?.value.trim() || '';

        if (!/^\d{13,19}$/.test(numero)) {
            return { valid: false, message: 'Número do cartão inválido.' };
        }

        if (!/^(0[1-9]|1[0-2])\/?([0-9]{2})$/.test(validade)) {
            return { valid: false, message: 'Validade do cartão inválida. Use o formato MM/AA.' };
        }

        if (!/^\d{3,4}$/.test(cvv)) {
            return { valid: false, message: 'CVV inválido.' };
        }

        data.pagamento.numero_cartao = numero;
        data.pagamento.validade = validade;
        data.pagamento.cvv = cvv;
    } else {
        const pixCodeText = document.getElementById('pixCodeText');
        data.pagamento.pix_codigo = pixCodeText ? pixCodeText.textContent.trim() : gerarCodigoPix();
    }

    return { valid: true, data };
}

function mostrarMensagemPagamento(tipo, mensagem) {
    const feedback = document.getElementById('paymentFeedback');
    if (!feedback) {
        return;
    }

    const tons = {
        success: { bg: '#d4edda', color: '#155724' },
        error: { bg: '#f8d7da', color: '#721c24' },
        info: { bg: '#d1ecf1', color: '#0c5460' }
    };

    const estilo = tons[tipo] || tons.info;
    feedback.style.display = 'block';
    feedback.style.background = estilo.bg;
    feedback.style.color = estilo.color;
    feedback.innerHTML = mensagem;
}

function resetPaymentForm() {
    const paymentForm = document.getElementById('paymentForm');
    if (paymentForm) {
        paymentForm.reset();
        toggleCamposPagamento('cartao');
    }
    const feedback = document.getElementById('paymentFeedback');
    if (feedback) {
        feedback.style.display = 'none';
        feedback.textContent = '';
    }
}

function gerarCodigoPix() {
    const random = Math.random().toString(36).substring(2, 8).toUpperCase();
    return `PETSHOPPIX-${random}`;
}

// Mostrar notificação
function mostrarNotificacao(mensagem) {
    const notificacao = document.createElement('div');
    notificacao.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        background: var(--primary-color);
        color: white;
        padding: 1rem 2rem;
        border-radius: 8px;
        box-shadow: 0 5px 15px rgba(147, 112, 219, 0.3);
        z-index: 3000;
        animation: slideInRight 0.3s;
    `;
    notificacao.textContent = mensagem;
    document.body.appendChild(notificacao);

    setTimeout(() => {
        notificacao.style.animation = 'slideOutRight 0.3s';
        setTimeout(() => {
            document.body.removeChild(notificacao);
        }, 300);
    }, 2000);
}

// Adicionar estilos de animação para notificação
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);