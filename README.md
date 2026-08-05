# 🏎️ Moneyball F1 - Documentação do Banco de Dados e Backend

Este documento detalha a arquitetura do banco de dados, regras de negócio e estrutura do backend do sistema Moneyball F1, um painel analítico focado em telemetria e desempenho de pilotos.

---

## 1. Estrutura do Banco de Dados (MySQL)

O sistema utiliza um banco de dados relacional (MySQL) com tabelas interligadas para garantir a integridade das estatísticas. O banco utiliza o *character set* `utf8mb4` para suporte completo a caracteres especiais[cite: 2].

### Tabelas Principais

#### `usuarios`
Gerencia o acesso ao sistema.
* **id:** Identificador único (Primary Key)[cite: 2].
* **nome / email:** Dados de identificação do usuário[cite: 2]. O e-mail possui uma restrição `UNIQUE` para evitar duplicações[cite: 2].
* **senha:** Armazena o hash criptografado da senha[cite: 2].
* **nivel_acesso:** Define as permissões, podendo ser `admin` ou `comum`[cite: 2].
* **criado_em:** Timestamp automático de criação[cite: 2].

#### `pilotos`
Cadastro unificado dos pilotos.
* **id:** Identificador único (Primary Key)[cite: 2].
* **nome / equipe / numero / nacionalidade:** Informações básicas do piloto[cite: 2].
* **Restrição:** Possui uma chave única (Unique Key) combinando `nome`, `equipe` e `numero` para evitar cadastros duplicados[cite: 2].

#### `estatisticas`
Armazena o desempenho de cada piloto por temporada.
* **piloto_id:** Chave Estrangeira (Foreign Key) ligada à tabela `pilotos` com exclusão em cascata (`ON DELETE CASCADE`)[cite: 2].
* **Métricas coletadas:** temporada, pontos, vitórias, pódios, poles, voltas rápidas, posição média de chegada e abandonos[cite: 2].
* **Índices de Performance:** Foram criados índices (`INDEX`) nas colunas `temporada` e `pontos` para acelerar as consultas analíticas do painel[cite: 2].

---

## 2. Configuração e Conexão (MySQLi Procedural)

A comunicação com o banco de dados é feita de forma estritamente procedural utilizando a extensão MySQLi do PHP, atendendo aos requisitos do projeto.
* O sistema se conecta ao host `127.0.0.1` na porta padrão `3306`[cite: 1].
* Caso a conexão falhe, o servidor aborta a execução e retorna um erro HTTP `500` formatado em JSON[cite: 1].
* O charset da conexão é forçado para `utf8mb4` logo após a autenticação[cite: 1].
* Todas as consultas são executadas utilizando funções nativas como `mysqli_query` ou `mysqli_prepare` para `statements` preparados, sem qualquer uso de orientação a objetos[cite: 1, 4, 6].

---

## 3. Segurança e Autenticação

O sistema foi construído com múltiplas camadas de segurança:
* **Criptografia:** Todas as senhas cadastradas são processadas utilizando o algoritmo `PASSWORD_BCRYPT` (`password_hash`)[cite: 7].
* **Proteção de Sessão:** As sessões ativas utilizam cookies configurados com `httponly = true` (para mitigar ataques XSS) e `samesite = 'Lax'`[cite: 9].
* **Controle de Acesso:** Existem funções dedicadas para proteger as rotas, como `exigir_login()` (para qualquer usuário) e `exigir_admin()` (restrito ao nível de acesso de administradores)[cite: 9].

---

## 4. Importação de Dados (CSV)

O sistema suporta a importação em massa de estatísticas via arquivos CSV.
* **Colunas Obrigatórias:** O cabeçalho do arquivo deve conter exatamente: `nome`, `equipe`, `numero`, `nacionalidade`, `temporada`, `pontos`, `vitorias`, `podios`, `poles`, `voltas_rapidas`, `posicao_media_chegada`, e `abandonos`[cite: 5].
* **Integridade (Transactions):** O processamento de leitura utiliza controle transacional através de `mysqli_begin_transaction`[cite: 5]. Se ocorrer qualquer falha durante a inserção, o sistema executa um `mysqli_rollback` e nenhuma informação corrompida é salva no banco[cite: 5].
* **Atualização Inteligente:** Se um registro com a mesma temporada e piloto já existir, o sistema utiliza a cláusula `ON DUPLICATE KEY UPDATE` para atualizar os valores ao invés de duplicá-los[cite: 5].

---

## 5. Consultas Analíticas (Dashboard)

O backend possui funções prontas para responder às principais questões da temporada:
* **Melhor/Pior Desempenho:** Calcula quem teve o maior e menor somatório de pontos[cite: 4].
* **Maior Regularidade:** Cruza a menor média de posições de chegada com a menor quantidade de abandonos[cite: 4].
* **Taxa de Conversão:** Analisa eficiência dividindo o total de vitórias pelo total de poles positions conquistadas por cada piloto[cite: 4].
* **Diferencial (Comparador):** O sistema consegue extrair a diferença matemática exata entre dois pilotos lado a lado utilizando a função `comparar_pilotos()`[cite: 4].

---

## 6. Guia Rápido de Instalação e Testes

1. Inicie o serviço MySQL (ex: pelo painel do XAMPP).
2. Crie um banco de dados vazio chamado `moneyball`.
3. Importe o arquivo `database.sql` para gerar as tabelas.
4. Acesse a rota `/setup_admin.php` no navegador. Isso criará automaticamente o usuário administrador padrão com o e-mail `admin@moneyball.com` e a senha `admin123`[cite: 3].
5. Inicie o servidor embutido do PHP pelo terminal (`php -S localhost:8080`) apontando para a raiz do projeto.