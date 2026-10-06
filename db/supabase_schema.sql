CREATE TABLE IF NOT EXISTS usuario (
	id SERIAL PRIMARY KEY,
	username VARCHAR(255) NOT NULL UNIQUE,
	senha VARCHAR(255) NOT NULL,
	perfil INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS cliente (
	id SERIAL PRIMARY KEY,
	id_usuario INTEGER NOT NULL REFERENCES usuario(id),
	nome VARCHAR(255) NOT NULL,
	telefone_contato VARCHAR(255),
	email VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS barbeiro (
	id SERIAL PRIMARY KEY,
	nome VARCHAR(255) NOT NULL,
	especialidade VARCHAR(255) NOT NULL,
	telefone VARCHAR(255) NOT NULL,
	email VARCHAR(255) NOT NULL,
	instagram VARCHAR(255),
	id_usuario INTEGER REFERENCES usuario(id)
);

CREATE TABLE IF NOT EXISTS servico (
	id SERIAL PRIMARY KEY,
	nome VARCHAR(255) NOT NULL,
	descricao VARCHAR(255) NOT NULL,
	valor INTEGER NOT NULL,
	duracao INTEGER NOT NULL,
	id_barbeiro INTEGER REFERENCES barbeiro(id)
);

CREATE TABLE IF NOT EXISTS agendamento (
	id SERIAL PRIMARY KEY,
	data TIMESTAMP NOT NULL,
	id_cliente INTEGER NOT NULL REFERENCES cliente(id),
	id_barbeiro INTEGER NOT NULL REFERENCES barbeiro(id),
	id_servico INTEGER NOT NULL REFERENCES servico(id),
	status INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS produto (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    descricao TEXT,
    preco DECIMAL(10,2) NOT NULL,
    desconto DECIMAL(10,2) DEFAULT 0,
    foto VARCHAR(255),
    id_barbeiro INTEGER NOT NULL REFERENCES barbeiro(id)
);

CREATE TABLE IF NOT EXISTS horario_trabalho (
    id SERIAL PRIMARY KEY,
    id_barbeiro INTEGER NOT NULL REFERENCES barbeiro(id),
    dia_semana INTEGER NOT NULL,
    trabalha INTEGER NOT NULL DEFAULT 1,
    hora_inicio TIME NOT NULL,
    hora_fim TIME NOT NULL
);
