-- Horario y jornada ordinaria por trabajador (opcional; hay valores por defecto).
ALTER TABLE usuarios
    ADD COLUMN usu_hora_inicio TIME NULL DEFAULT '07:00:00',
    ADD COLUMN usu_hora_fin TIME NULL DEFAULT '15:00:00',
    ADD COLUMN usu_jornada_diaria_minutos INT NULL DEFAULT 420,
    ADD COLUMN usu_jornada_semanal_minutos INT NULL DEFAULT 2520,
    ADD COLUMN usu_dia_descanso TINYINT NULL DEFAULT 0;
