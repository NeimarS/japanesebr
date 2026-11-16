-- =====================================================================
-- Banco de dados: neimar60_japones_app
-- App de estudo de japonês (Hiragana, Katakana, Kanji N5, Palavras, Frases)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS neimar60_japones_app
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE neimar60_japones_app;

-- ---------------------------------------------------------------------
-- Tabela: usuarios
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
    id     INT AUTO_INCREMENT PRIMARY KEY,
    email  VARCHAR(255) NOT NULL UNIQUE,
    senha  VARCHAR(255) NOT NULL, -- senha criptografada com password_hash() (bcrypt)
    is_admin TINYINT(1) NOT NULL DEFAULT 0, -- 1 = tem acesso ao painel admin.php
    reset_token VARCHAR(255) NULL,
    reset_expira DATETIME NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Se o banco já existia antes desta versão, rode esta linha uma vez para
-- adicionar a coluna sem perder os dados:
-- ALTER TABLE usuarios ADD COLUMN is_admin TINYINT(1) NOT NULL DEFAULT 0;

-- ---------------------------------------------------------------------
-- Tabela: pontos
-- id é ao mesmo tempo PK desta tabela e FK para usuarios.id
-- (relação 1 para 1: cada usuário tem uma linha de pontuação)
-- Obs: frasesN5 e exercicioN5 são VARCHAR(255) conforme solicitado;
-- guardam o valor numérico de pontos como texto.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pontos (
    id            INT(20)      NOT NULL PRIMARY KEY,
    hiragana      INT(20)      NOT NULL DEFAULT 0,
    katakana      INT(20)      NOT NULL DEFAULT 0,
    kanjiN5       INT(20)      NOT NULL DEFAULT 0,
    whiragana     INT(20)      NOT NULL DEFAULT 0,
    wkatakana     INT(20)      NOT NULL DEFAULT 0,
    mistokanjiN5  INT(20)      NOT NULL DEFAULT 0,
    frasesN5      VARCHAR(255) NOT NULL DEFAULT '0',
    exercicioN5   VARCHAR(255) NOT NULL DEFAULT '0',
    CONSTRAINT fk_pontos_usuarios
        FOREIGN KEY (id) REFERENCES usuarios(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Tabelas de CONTEÚDO (silabários, kanji, palavras e frases)
-- Necessárias para que o conteúdo estudado também fique salvo no banco,
-- como pedido, e não "hardcoded" só no front-end.
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS hiragana_dados (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    caractere VARCHAR(10)  NOT NULL,
    romaji    VARCHAR(20)  NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS katakana_dados (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    caractere VARCHAR(10)  NOT NULL,
    romaji    VARCHAR(20)  NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS kanji_n5_dados (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    kanji        VARCHAR(10)  NOT NULL,
    significado  VARCHAR(150) NOT NULL,
    leitura_on   VARCHAR(80)  NULL,
    leitura_kun  VARCHAR(80)  NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS palavras_hiragana_dados (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    palavra     VARCHAR(100) NOT NULL,
    significado VARCHAR(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS palavras_katakana_dados (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    palavra     VARCHAR(100) NOT NULL,
    significado VARCHAR(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS misto_n5_dados (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    palavra     VARCHAR(100) NOT NULL,
    significado VARCHAR(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS frases_n5_dados (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    frase    VARCHAR(255) NOT NULL,
    traducao VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS gramatica_dados (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    item     VARCHAR(255) NOT NULL, -- palavra/expressão em japonês (kana/kanji)
    romaji   VARCHAR(255) NULL,
    traducao VARCHAR(255) NOT NULL,
    grupo    VARCHAR(100) NOT NULL DEFAULT 'Geral' -- ex.: Cumprimentos, Sala de aula, Números
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Tabela: duvidas_sugestoes
-- Guarda as mensagens enviadas pelo formulário "Dúvidas/Sugestões",
-- além de tentar notificar o administrador por e-mail.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS duvidas_sugestoes (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL,
    email      VARCHAR(255) NOT NULL,
    assunto    VARCHAR(255) NOT NULL,
    mensagem   TEXT NOT NULL,
    criado_em  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_duvidas_usuarios
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
