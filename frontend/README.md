# frontend — Frontend SPA

Interface do sistema PrintAdmin, desenvolvida em Vue 3 com Vite e Tailwind CSS.

## Tecnologias

- Vue.js 3 (Composition API / `<script setup>`)
- Vite 6
- Tailwind CSS v4
- Pinia (gerenciamento de estado)
- Vue Router 4 (com Navigation Guards)
- Axios (com Interceptors)

## Configuração e Execução

```bash
# Instalar dependências
npm install

# Iniciar em modo de desenvolvimento
npm run dev

# Gerar build de produção
npm run build
```

O frontend estará disponível em `http://localhost:5173`.

## Observações

- O frontend consome a API em `http://127.0.0.1:8000`. Certifique-se de que o backend está em execução antes de iniciar o frontend.
- A autenticação é gerenciada via token Bearer armazenado no `localStorage`.
- Todas as rotas protegidas redirecionam para `/login` caso o usuário não esteja autenticado.
