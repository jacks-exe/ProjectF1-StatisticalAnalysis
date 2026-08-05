CREATE DATABASE IF NOT EXISTS moneyball_f1 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE moneyball_f1;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    nivel_acesso ENUM('admin','comum') NOT NULL DEFAULT 'comum',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE pilotos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    equipe VARCHAR(150) NOT NULL,
    numero INT NOT NULL,
    nacionalidade VARCHAR(100) NOT NULL,
    UNIQUE KEY uk_nome_equipe_numero (nome, equipe, numero)
) ENGINE=InnoDB;

CREATE TABLE estatisticas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    piloto_id INT NOT NULL,
    temporada INT NOT NULL,
    pontos DECIMAL(8,2) NOT NULL DEFAULT 0,
    vitorias INT NOT NULL DEFAULT 0,
    podios INT NOT NULL DEFAULT 0,
    poles INT NOT NULL DEFAULT 0,
    voltas_rapidas INT NOT NULL DEFAULT 0,
    posicao_media_chegada DECIMAL(5,2) NOT NULL DEFAULT 0,
    abandonos INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_estatisticas_piloto FOREIGN KEY (piloto_id) REFERENCES pilotos(id) ON DELETE CASCADE,
    UNIQUE KEY uk_piloto_temporada (piloto_id, temporada)
) ENGINE=InnoDB;

CREATE INDEX idx_estat_temporada ON estatisticas (temporada);
CREATE INDEX idx_estat_pontos ON estatisticas (pontos);
