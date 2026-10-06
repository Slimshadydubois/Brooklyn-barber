-- Vincular barbeiro ao usuário do sistema
ALTER TABLE `barbeiro` ADD COLUMN `id_usuario` INT DEFAULT NULL;
ALTER TABLE `barbeiro` ADD FOREIGN KEY (`id_usuario`) REFERENCES `usuario`(`id`) ON DELETE SET NULL;

-- Vincular serviço a um barbeiro específico
ALTER TABLE `servico` ADD COLUMN `id_barbeiro` INT DEFAULT NULL;
ALTER TABLE `servico` ADD FOREIGN KEY (`id_barbeiro`) REFERENCES `barbeiro`(`id`) ON DELETE CASCADE;

-- Criar tabela de horários de funcionamento diários por barbeiro
CREATE TABLE IF NOT EXISTS `horario_trabalho` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `id_barbeiro` INT NOT NULL,
    `dia_semana` INT NOT NULL, -- 0 = Domingo, 1 = Segunda, ..., 6 = Sábado
    `trabalha` TINYINT(1) DEFAULT 0, -- 1 = Trabalha neste dia, 0 = Folga
    `hora_inicio` TIME NOT NULL DEFAULT '09:00:00',
    `hora_fim` TIME NOT NULL DEFAULT '18:00:00',
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_barbeiro_dia` (`id_barbeiro`, `dia_semana`),
    FOREIGN KEY (`id_barbeiro`) REFERENCES `barbeiro`(`id`) ON DELETE CASCADE
);
