-- Adiciona as colunas `cnpj` e `nome_empresa` à tabela `usuarios`.

-- 1. Adiciona as colunas permitindo valores nulos temporariamente
ALTER TABLE `usuarios`
ADD COLUMN `cnpj` VARCHAR(18) NULL AFTER `email`,
ADD COLUMN `nome_empresa` VARCHAR(255) NULL AFTER `cnpj`;

-- 2. Atualiza os registros existentes com valores padrão para evitar erros.
UPDATE `usuarios`
SET 
    `cnpj` = '00.000.000/0000-00', -- CNPJ padrão
    `nome_empresa` = 'Empresa não informada' -- Nome empresa padrão
WHERE `cnpj` IS NULL;

-- 3. Altera as colunas para serem NOT NULL (obrigatórias)
ALTER TABLE `usuarios`
MODIFY COLUMN `cnpj` VARCHAR(18) NOT NULL,
MODIFY COLUMN `nome_empresa` VARCHAR(255) NOT NULL;

-- 4. Adiciona um índice único no CNPJ para evitar duplicatas
ALTER TABLE `usuarios` ADD UNIQUE INDEX `idx_cnpj` (`cnpj`);

-- Fim da migração
