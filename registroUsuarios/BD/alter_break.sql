-- Break de 15 minutos (sale / regresa), igual que almuerzo.
ALTER TABLE seguimientousers
    ADD COLUMN seg_ingresoBreak TIME NOT NULL DEFAULT '00:00:00',
    ADD COLUMN seg_salioBreak TIME NOT NULL DEFAULT '00:00:00';
