# Barbearia System

Este é um projeto de sistema de gerenciamento para uma barbearia, desenvolvido para rodar em um ambiente XAMPP. Atualmente, o projeto está em sua fase inicial, focando na definição da estrutura do banco de dados.

## Visão Geral do Projeto

O sistema visa gerenciar agendamentos, clientes, barbeiros e serviços. A estrutura de dados é definida em SQL e está localizada no diretório `db/`.

### Tecnologias Principais
- **Banco de Dados:** MySQL / MariaDB
- **Ambiente:** XAMPP (Apache + MySQL)
- **Linguagem:** Provavelmente PHP (devido ao uso do htdocs), com termos em Português.

## Configuração do Banco de Dados

Para configurar o banco de dados:
1. Certifique-se de que o MySQL está rodando no XAMPP.
2. Crie um novo banco de dados (ex: `barbearia`).
3. Importe o arquivo `db/baseDATABASE.sql` para o banco de dados criado.

## Estrutura de Diretórios

- `/db`: Contém scripts SQL para criação e inicialização do banco de dados.
  - `baseDATABASE.sql`: Esquema principal do banco de dados (tabelas: `cliente`, `barbeiro`, `agendamento`, `servico`, `usuario`).

## Convenções de Desenvolvimento

- **Idioma:** O código (nomes de tabelas e colunas) está em Português.
- **Nomenclatura:** Tabelas e colunas seguem o padrão lowercase.
- **Controle de Versão:** O projeto utiliza Git.

<critical>
  - VOCÊ DEVE SEMPRE SEGUIR AS REGRAS NA PASTA `rules/`.
</critical>

## Roadmap (TODO)
- [ ] Implementar a conexão com o banco de dados em PHP.
- [ ] Criar interface de login baseada na tabela `usuario`.
- [ ] Implementar CRUD de clientes e serviços.
- [ ] Implementar sistema de agendamento.
