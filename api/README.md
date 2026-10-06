# api — Backend API REST

API RESTful do sistema PrintAdmin, desenvolvida em Laravel 11 com autenticação via Laravel Sanctum.

## Tecnologias

- PHP 8.2+
- Laravel 11
- Laravel Sanctum (Bearer Token)
- PostgreSQL
- Eloquent ORM

## Configuração e Execução

```bash
# Instalar dependências
composer install

# Copiar e configurar variáveis de ambiente
cp .env.example .env

# Gerar a chave da aplicação
php artisan key:generate

# Executar as migrações
php artisan migrate

# Iniciar o servidor
php artisan serve
```

A API estará disponível em `http://127.0.0.1:8000`.

## Variáveis de Ambiente

Edite o arquivo `.env` após copiá-lo do `.env.example`. As variáveis principais a configurar são:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=nome_do_banco
DB_USERNAME=seu_usuario
DB_PASSWORD=sua_senha
```

## Estrutura de Rotas

Consulte o [README principal](../README.md) para a listagem completa dos endpoints da API.
