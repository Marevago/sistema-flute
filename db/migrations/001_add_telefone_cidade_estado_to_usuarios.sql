-- Adiciona as colunas `telefone`, `cidade` e `estado` à tabela `usuarios`.

-- 1. Adiciona as colunas permitindo valores nulos temporariamente
ALTER TABLE `usuarios`
ADD COLUMN `telefone` VARCHAR(15) NULL AFTER `email`,
ADD COLUMN `cidade` VARCHAR(100) NULL AFTER `telefone`,
ADD COLUMN `estado` VARCHAR(2) NULL AFTER `cidade`;

-- 2. Atualiza os registros existentes com valores padrão para evitar erros.
-- Use valores que façam sentido para o seu contexto.
UPDATE `usuarios`
SET 
    `telefone` = '00000000000', -- Telefone padrão
    `cidade` = 'Não informado', -- Cidade padrão
    `estado` = 'NI' -- Estado 'Não Informado'
WHERE `telefone` IS NULL;

-- 3. Altera as colunas para serem NOT NULL (obrigatórias)
ALTER TABLE `usuarios`
MODIFY COLUMN `telefone` VARCHAR(15) NOT NULL,
MODIFY COLUMN `cidade` VARCHAR(100) NOT NULL,
MODIFY COLUMN `estado` VARCHAR(2) NOT NULL;

-- 4. (Opcional, mas recomendado) Adiciona um índice na coluna de e-mail se ainda não existir
-- Isso melhora o desempenho das buscas por e-mail.
ALTER TABLE `usuarios` ADD UNIQUE INDEX `idx_email` (`email`);

-- Fim da migração
