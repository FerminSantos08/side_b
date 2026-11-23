CREATE TABLE IF NOT EXISTS solicitud_factura (
    pedido_id INT NOT NULL PRIMARY KEY,
    rfc VARCHAR(13) NOT NULL,
    nombre_fiscal VARCHAR(254) NOT NULL,
    codigo_postal CHAR(5) NOT NULL,
    regimen_fiscal CHAR(3) NOT NULL,
    uso_cfdi CHAR(4) NOT NULL,
    solicitada_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_solicitud_factura_pedido FOREIGN KEY (pedido_id)
        REFERENCES pedido (id_pedido) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
