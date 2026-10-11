# Podoclinic API

API REST para gestão operacional de uma clínica de podologia, desenvolvida com **PHP 8.3**, **CodeIgniter 4** e **MariaDB**.

> **Estado do projeto:** desenvolvimento. As funcionalidades abaixo foram implementadas e testadas durante o desenvolvimento; antes de publicar, confira os comandos e as rotas contra a versão atual do código.

## Objetivo

Centralizar o cadastro de clientes, produtos, movimentações de estoque, vendas, lançamentos financeiros e lembretes de reposição. O sistema foi projetado inicialmente para uma clínica com uma única profissional, com possibilidade de interfaces web e mobile consumirem a mesma API REST.

## Tecnologias

- PHP 8.3
- CodeIgniter 4 (versão 4.7.4 utilizada durante o desenvolvimento)
- MariaDB 11.8
- Composer
- Git
- Ambiente local de desenvolvimento: Windows e XAMPP

## Funcionalidades implementadas

| Módulo | Funcionalidades |
| --- | --- |
| Autenticação | Login com e-mail e senha, autenticação por token Bearer e logout com revogação da sessão atual |
| Autorização | Perfis `OWNER` e `EMPLOYEE` e controle granular de permissões |
| Clientes | Cadastro e manutenção de clientes |
| Produtos | Cadastro de produtos de consumo interno (`CONSUMPTION`) e revenda (`RESALE`) |
| Estoque | Entradas, saídas, ajustes e histórico de movimentações |
| Compras | Registro de compras e entrada de estoque |
| Vendas | Registro de vendas de produtos e integração com estoque e financeiro |
| Financeiro | Categorias, receitas, despesas, consulta de movimentações e resumo financeiro |
| Lembretes | Lembretes de reposição associados a vendas e apoio ao contato via WhatsApp |
| Cancelamento de vendas | Cancelamento por erro de lançamento (`ENTRY_ERROR`) ou devolução efetiva ao cliente (`CUSTOMER_REFUND`) |

## Regras de negócio relevantes

- O projeto foi dimensionado inicialmente para uma única clínica/profissional, sem suporte multiunidade ou multiprofissional.
- Produtos de consumo interno e produtos destinados à revenda são diferenciados por tipo.
- Os pagamentos previstos são **PIX** e **dinheiro** (`PIX` e `CASH` na API); não há parcelamento na versão atual.
- O estoque de materiais de consumo é controlado conforme a regra de abertura de embalagens definida para a clínica.
- A venda de um produto de revenda gera a correspondente saída de estoque e uma receita financeira vinculada.
- Um lembrete de reposição pode ser associado à venda de acordo com a duração média cadastrada para o produto.
- O cancelamento de venda é restrito a usuários autorizados, com a permissão `sale.cancel`.
- O cancelamento restaura o estoque e registra uma movimentação de entrada com motivo `SALE_CANCELLATION`.
- Em `ENTRY_ERROR`, a receita original é marcada como revertida e deixa de compor o resumo financeiro, sem ser apagada do histórico.
- Em `CUSTOMER_REFUND`, a receita original é preservada e uma despesa de devolução é registrada na categoria `Devolução de vendas`.
- O cancelamento utiliza transação de banco de dados e bloqueio de registros para preservar a consistência das operações.
- O fluxo de cancelamento atual prevê devolução integral com produto retornado ao estoque; devoluções parciais não estão contempladas.
- Para clientes e produtos, as regras de exclusão respeitam vínculos existentes; quando houver vínculos, deve-se preservar o histórico por meio de inativação.

## Arquitetura

O projeto utiliza a estrutura do CodeIgniter 4, com separação de responsabilidades:

```text
app/
├── Config/
│   └── Routes.php
├── Controllers/
│   └── Api/
├── Database/
│   ├── Migrations/
│   └── Seeds/           # quando aplicável
├── Exceptions/
├── Filters/
├── Models/
└── Services/
```

Os **Controllers** recebem as requisições HTTP, os **Services** concentram regras de negócio e os **Models** oferecem acesso às entidades persistidas. Os filtros controlam autenticação e permissões; as migrations versionam alterações de banco.

### Convenções

- Tabelas e colunas do banco: `snake_case`.
- Chaves primárias: inteiros sem sinal (`INT UNSIGNED`) nas tabelas desenvolvidas.
- Relacionamentos sensíveis ao histórico: chaves estrangeiras com `ON DELETE NO ACTION`, conforme aplicável.
- Respostas da API: JSON; os campos de resposta utilizam `camelCase` onde implementado.
- Mensagens de validação e erros de negócio: português brasileiro.
- Histórico de alterações: Conventional Commits, com mensagens em inglês.

## Exemplos de API

> Os caminhos abaixo são exemplos de rotas confirmadas durante o desenvolvimento. Consulte `app/Config/Routes.php` para a lista completa e atualizada de endpoints, filtros e métodos.

### Criar uma venda

```http
POST /api/sales
Authorization: Bearer <TOKEN>
Content-Type: application/json
```

O corpo deve respeitar o contrato de criação da versão atual. Durante os testes, a API exigiu uma categoria financeira válida por meio do campo `financial_category_id`.

### Cancelar uma venda

```http
PATCH /api/sales/{id}/cancel
Authorization: Bearer <OWNER_TOKEN>
Content-Type: application/json
```

Cancelamento por erro de lançamento:

```json
{
  "type": "ENTRY_ERROR",
  "reason": "Venda cadastrada incorretamente e pagamento não recebido."
}
```

Cancelamento com reembolso:

```json
{
  "type": "CUSTOMER_REFUND",
  "reason": "Produto devolvido em condições de revenda e valor reembolsado integralmente."
}
```

A justificativa deve conter entre 10 e 500 caracteres. A API exige autorização e não permite cancelar uma venda já cancelada.

## Preparação do ambiente local

### Pré-requisitos

1. PHP compatível com a versão do CodeIgniter utilizada (o projeto foi desenvolvido com PHP 8.3).
2. Composer instalado.
3. MariaDB disponível.
4. Extensões PHP exigidas pelo CodeIgniter e pelas dependências do projeto.

### Instalação

```bash
git clone <URL_DO_REPOSITORIO>
cd podoclinic-api
composer install
```

Crie seu arquivo local de configuração de ambiente a partir do modelo do CodeIgniter ou de um `.env.example` sanitizado mantido no repositório. Configure a conexão com o banco e as demais variáveis necessárias **sem versionar segredos**.

Em instalações padrão do CodeIgniter 4, o arquivo `env` fornecido pelo framework pode ser copiado para `.env`; confirme se esse arquivo existe na versão do projeto antes de executar a cópia.

Crie o banco MariaDB e aplique as migrations:

```bash
php spark migrate
```

Execute os seeds necessários para o primeiro usuário `OWNER`, permissões e categorias, **somente se esses seeds estiverem disponíveis e preparados para seu ambiente**. Não há cadastro público do primeiro proprietário.

Para iniciar o servidor de desenvolvimento:

```bash
php spark serve
```

O endereço e a porta efetivos são informados pelo comando. Em alguns testes do projeto, a API foi acessada em `http://localhost:8080`.

## Segurança

- Nunca publique `.env`, credenciais de banco, senhas, tokens de acesso, chaves privadas ou backups com dados reais.
- Não versione logs, cache, uploads privados ou arquivos gerados em tempo de execução.
- Configure as credenciais exclusivamente no ambiente de execução.
- Não use credenciais de teste em produção.
- Revise CORS, HTTPS, permissões, mensagens de erro e configuração de ambiente antes de disponibilizar a API publicamente.
- Um repositório privado **não substitui** a proteção de segredos: arquivos sensíveis não devem ser enviados ao GitHub nem mesmo temporariamente.

## Testes realizados

Durante o desenvolvimento foram validados, entre outros cenários:

- Requisições sem token, com token inválido e sem permissão.
- Validações de entrada e tratamento de recurso inexistente.
- Cancelamento por `ENTRY_ERROR` e por `CUSTOMER_REFUND`.
- Rejeição de cancelamento duplicado.
- Reposição do estoque, lançamentos financeiros e cancelamento de lembretes.
- Resumo financeiro e preservação do histórico de movimentações.
- Rollback transacional com exceção controlada após operações de escrita.
- Teste de regressão após remoção do código temporário.

Essas verificações foram executadas manualmente durante o desenvolvimento e **não significam que exista uma suíte de testes automatizados**.

## Evoluções possíveis

- Documentação OpenAPI/Swagger dos endpoints.
- Testes automatizados unitários e de integração.
- Pipeline CI para validação de sintaxe e testes.
- Interface web em React e aplicativo mobile consumindo a API.
- Observabilidade, auditoria e melhorias de implantação.

## Licença

Nenhuma licença de uso público foi definida. Até que um arquivo `LICENSE` seja adicionado, não há concessão explícita de direitos de reutilização do código.
