CREATE DATABASE IF NOT EXISTS moneyball_f1
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE moneyball_f1;

-- =====================================================
-- USUÁRIOS
-- =====================================================

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    nivel_acesso ENUM('admin','comum') NOT NULL DEFAULT 'comum',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- EQUIPES
-- =====================================================

CREATE TABLE equipes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    nacionalidade VARCHAR(100) NOT NULL,
    sede VARCHAR(150),
    ano_fundacao INT
) ENGINE=InnoDB;

-- =====================================================
-- PILOTOS
-- =====================================================

CREATE TABLE pilotos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    nacionalidade VARCHAR(100) NOT NULL,
    data_nascimento DATE,
    numero INT NOT NULL,

    UNIQUE(nome, numero)
) ENGINE=InnoDB;

-- =====================================================
-- TEMPORADAS
-- =====================================================

CREATE TABLE temporadas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ano INT NOT NULL UNIQUE,
    data_inicio DATE,
    data_fim DATE
) ENGINE=InnoDB;

-- =====================================================
-- CIRCUITOS
-- =====================================================

CREATE TABLE circuitos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    pais VARCHAR(100),
    cidade VARCHAR(100),
    comprimento_km DECIMAL(5,2)
) ENGINE=InnoDB;

-- =====================================================
-- CORRIDAS
-- =====================================================

CREATE TABLE corridas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,

    temporada_id INT NOT NULL,
    circuito_id INT NOT NULL,

    data_corrida DATE,
    voltas INT,

    CONSTRAINT fk_corrida_temporada
        FOREIGN KEY (temporada_id)
        REFERENCES temporadas(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT fk_corrida_circuito
        FOREIGN KEY (circuito_id)
        REFERENCES circuitos(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- RESULTADO DA CORRIDA
-- =====================================================

CREATE TABLE resultado_corrida (

    id INT AUTO_INCREMENT PRIMARY KEY,

    corrida_id INT NOT NULL,

    piloto_id INT NOT NULL,

    posicao_final INT,

    pontos DECIMAL(8,2) DEFAULT 0,

    tempo_total TIME(3),

    volta_mais_rapida TIME(3),

    colocacao_grid INT,

    status VARCHAR(50),

    CONSTRAINT fk_resultado_corrida
        FOREIGN KEY (corrida_id)
        REFERENCES corridas(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_resultado_piloto
        FOREIGN KEY (piloto_id)
        REFERENCES pilotos(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE

) ENGINE=InnoDB;

-- =====================================================
-- VOLTAS
-- =====================================================

CREATE TABLE voltas (

    id INT AUTO_INCREMENT PRIMARY KEY,

    resultado_id INT NOT NULL,

    numero_volta INT NOT NULL,

    tempo_volta TIME(3),

    CONSTRAINT fk_volta_resultado
        FOREIGN KEY (resultado_id)
        REFERENCES resultado_corrida(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE

) ENGINE=InnoDB;

-- =====================================================
-- HISTÓRICO PILOTO-EQUIPE
-- =====================================================

CREATE TABLE historico_piloto_equipe (

    piloto_id INT NOT NULL,

    equipe_id INT NOT NULL,

    data_inicio DATE NOT NULL,

    data_fim DATE,

    PRIMARY KEY (piloto_id, equipe_id, data_inicio),

    CONSTRAINT fk_hist_piloto
        FOREIGN KEY (piloto_id)
        REFERENCES pilotos(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_hist_equipe
        FOREIGN KEY (equipe_id)
        REFERENCES equipes(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE

) ENGINE=InnoDB;

-- =====================================================
-- ESTATÍSTICAS
-- =====================================================

CREATE TABLE estatisticas (

    id INT AUTO_INCREMENT PRIMARY KEY,

    piloto_id INT NOT NULL,

    temporada_id INT NOT NULL,

    pontos DECIMAL(8,2) DEFAULT 0,

    vitorias INT DEFAULT 0,

    podios INT DEFAULT 0,

    poles INT DEFAULT 0,

    voltas_rapidas INT DEFAULT 0,

    corridas_disputadas INT DEFAULT 0,

    posicao_media_chegada DECIMAL(5,2) DEFAULT 0,

    abandonos INT DEFAULT 0,

    CONSTRAINT fk_estatisticas_piloto
        FOREIGN KEY (piloto_id)
        REFERENCES pilotos(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_estatisticas_temporada
        FOREIGN KEY (temporada_id)
        REFERENCES temporadas(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    UNIQUE (piloto_id, temporada_id)

) ENGINE=InnoDB;

-- =====================================================
-- ÍNDICES
-- =====================================================

CREATE INDEX idx_piloto_nome
ON pilotos(nome);

CREATE INDEX idx_temporada_ano
ON temporadas(ano);

CREATE INDEX idx_corrida_data
ON corridas(data_corrida);

CREATE INDEX idx_resultado_pontos
ON resultado_corrida(pontos);

CREATE INDEX idx_estatisticas_pontos
ON estatisticas(pontos);
