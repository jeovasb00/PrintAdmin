# PrintAdmin — Sistema de Gestão para Copiadoras e Gráficas Rápidas

Sistema completo de gestão operacional, financeiro e de estoque desenvolvido sob medida para o dia a dia de copiadoras e gráficas rápidas. Construído com arquitetura desacoplada: **Backend API RESTful em Laravel 11** e **Frontend SPA reativo em Vue 3**.

---

## Tecnologias Utilizadas

### Backend (`api`)

- **Linguagem e Framework**: PHP 8.2+ / Laravel 11
- **Autenticação**: Laravel Sanctum (Bearer Token)
- **Banco de Dados**: PostgreSQL com Migrations e Relacionamentos Eloquent
- **Arquitetura**: RESTful API com Controllers, Validações e Transactions no banco

### Frontend (`frontend`)

- **Framework**: Vue.js 3 (Composition API / `<script setup>`)
- **Build Tool**: Vite 6
- **Estilização**: Tailwind CSS v4
- **Gerenciamento de Estado**: Pinia
- **Roteamento e Proteção**: Vue Router 4 com Navigation Guards globais
- **Cliente HTTP**: Axios com Interceptors centralizados

---

## Principais Funcionalidades

### 1. Autenticação e Controle de Acesso

- Tela unificada de login e cadastro com alternância entre abas
- Tokens Sanctum persistidos no `localStorage` com controle de sessão
- Proteção global de rotas via Navigation Guards — sem autenticação, sem acesso
- Interceptors Axios: injeção automática do header `Authorization: Bearer <token>` e redirecionamento em caso de token expirado (`401 Unauthorized`)
- Exibição do operador conectado com iniciais, e-mail e botão de logout na Navbar e Sidebar

### 2. Gestão de Ordens de Serviço com Baixa Automática de Insumos

- Fluxo completo com 7 status operacionais: `Pendente / Fila`, `Em Produção`, `Aguardando Pagamento`, `Pronto p/ Retirada`, `Saiu p/ Entrega`, `Concluído` e `Cancelado`
- Vínculo de múltiplos insumos a cada OS com validação de saldo em tempo real
- Debito automático do estoque ao concluir a OS e estorno automático ao reabrir ou cancelar
- Divisão do valor total entre múltiplas formas de pagamento por pedido (Pix, Dinheiro, Cartão de Crédito, Cartão de Débito, A Prazo)
- Botão de integração com WhatsApp que gera um resumo formatado da OS para envio direto ao cliente

### 3. Calculadora de Orçamentos

- Cálculo de preço em tempo real com desconto progressivo por volume (P&B e colorido)
- Suporte a formatos A4, A3 e A5; tipos de papel (Sulfite, Couché, Kraft, Fotográfico) e acabamentos (encadernação, plastificação, grampo)
- Desconto frente e verso
- Exportação do orçamento formatado para WhatsApp
- Botão "Criar Ordem de Serviço" que transfere os dados calculados diretamente para o formulário de OS

### 4. Controle Financeiro e Fechamento de Caixa

- Total faturado no dia com divisão por forma de pagamento (valores e porcentagens)
- Seletor de data para consulta e auditoria de fechamentos anteriores

### 5. Gestão de Estoque de Insumos

- CRUD completo de matérias-primas e suprimentos
- Filtro de busca por nome
- Alerta visual para itens em nível crítico (5 unidades ou menos)

### 6. Interface e Barra Lateral Colapsável

- Sidebar retrátil com modo compacto de ícones e tooltips
- Persistência do estado recolhido/expandido no `localStorage`

---

## Como Executar o Projeto

### Pré-requisitos

- PHP 8.2+ e Composer
- Node.js 18+ e NPM
- PostgreSQL

---

### 1. Backend (Laravel)

```bash
cd api

# Instalar dependências PHP
composer install

# Copiar o arquivo de variáveis de ambiente e configurar
cp .env.example .env

# Editar o .env com suas credenciais do PostgreSQL:
# DB_CONNECTION=pgsql
# DB_HOST=127.0.0.1
# DB_PORT=5432
# DB_DATABASE=nome_do_banco
# DB_USERNAME=seu_usuario
# DB_PASSWORD=sua_senha

# Gerar a chave da aplicação
php artisan key:generate

# Executar as migrações
php artisan migrate

# Iniciar o servidor da API
php artisan serve
```

> O backend estará disponível em: `http://127.0.0.1:8000`

---

### 2. Frontend (Vue 3 + Vite)

```bash
cd frontend

# Instalar dependências
npm install

# Iniciar o servidor de desenvolvimento
npm run dev
```

> O frontend estará disponível em: `http://localhost:5173`

---

## Endpoints da API REST

### Autenticação

| Método | Endpoint | Autenticação | Descrição |
|--------|----------|:---:|-----------|
| POST | `/api/register` | Não | Cadastrar novo usuário |
| POST | `/api/login` | Não | Autenticar e obter Bearer Token |
| POST | `/api/logout` | Sim | Revogar token atual |
| GET | `/api/user` | Sim | Dados do usuário autenticado |

### Ordens de Serviço

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api/ordens-servico` | Listar OS (filtros por `status`, `busca`, `data`) |
| POST | `/api/ordens-servico` | Criar nova OS com insumos e pagamentos |
| GET | `/api/ordens-servico/{id}` | Detalhes de uma OS |
| PUT | `/api/ordens-servico/{id}` | Atualizar OS (com baixa/estorno de estoque) |
| DELETE | `/api/ordens-servico/{id}` | Excluir OS com estorno de insumos |
| GET | `/api/financeiro/resumo-diario?data=YYYY-MM-DD` | Resumo financeiro diário |

### Estoque

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api/estoque` | Listar todos os itens |
| POST | `/api/estoque` | Cadastrar novo item |
| PUT | `/api/estoque/{id}` | Atualizar nome e saldo |
| DELETE | `/api/estoque/{id}` | Remover item |

> Todos os endpoints de Ordens de Serviço e Estoque requerem autenticação via `Authorization: Bearer <token>`.

---

## Licença

Este projeto é de uso privado e foi desenvolvido como sistema interno para uso comercial.
