-- ChukoStock · estructura de la base de datos
-- Reconstruida a partir de los archivos .frm/.ibd de la base "chukostock" (XAMPP / MariaDB).
-- Los tipos, índices, enums y el CHECK salen de esos archivos tal cual.
-- Las FOREIGN KEY no se pueden leer desde los .frm (viven en el diccionario de InnoDB),
-- así que se declararon según los nombres de columnas e índices (ej: idx_prenda_marca, uq_envio_pedido).
--
-- Uso (base nueva):  phpMyAdmin > Importar > sql/schema.sql   y luego sql/datos_iniciales.sql
-- Si ya tenés la base "chukostock" funcionando en tu XAMPP, NO hace falta importar nada.

CREATE DATABASE IF NOT EXISTS chukostock CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE chukostock;

CREATE TABLE usuario (
  ID_Usuario      INT UNSIGNED NOT NULL,              -- en la BD original NO es AUTO_INCREMENT (la app calcula MAX+1)
  Nombre          VARCHAR(60)  NOT NULL,
  Apellido        VARCHAR(60)  NOT NULL,
  DNI             INT UNSIGNED NOT NULL,
  Dni_Verificado  TINYINT UNSIGNED NOT NULL,
  Email           VARCHAR(120) NOT NULL,
  Contrasena_hash VARCHAR(255) NOT NULL,
  Telefono        INT UNSIGNED NOT NULL,
  AliasPublico    VARCHAR(50)  NOT NULL,
  Biografia       VARCHAR(300) NOT NULL,
  Fecha_Registro  DATETIME     NOT NULL,
  PRIMARY KEY (ID_Usuario)
) ENGINE=InnoDB;

CREATE TABLE categoria (
  ID_Categoria INT UNSIGNED NOT NULL AUTO_INCREMENT,
  Nombre       VARCHAR(50)  NOT NULL,
  Descripcion  VARCHAR(240) NOT NULL,
  PRIMARY KEY (ID_Categoria)
) ENGINE=InnoDB;

CREATE TABLE marca (
  ID_Marca INT UNSIGNED NOT NULL AUTO_INCREMENT,
  Nombre   VARCHAR(50)  NOT NULL,
  PRIMARY KEY (ID_Marca),
  UNIQUE KEY uq_marca_nombre (Nombre)
) ENGINE=InnoDB;

CREATE TABLE direccion (
  ID_Direccion  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ID_Usuario    INT UNSIGNED NOT NULL,
  Calle         VARCHAR(100) NOT NULL,
  Numero        VARCHAR(10)  NOT NULL,
  Piso_depto    VARCHAR(20)  NOT NULL,
  Localidad     VARCHAR(80)  NOT NULL,
  Provincia     VARCHAR(60)  NOT NULL,
  Codigo_Postal VARCHAR(10)  NOT NULL,
  PRIMARY KEY (ID_Direccion),
  KEY ID_Usuario (ID_Usuario),
  CONSTRAINT fk_direccion_usuario FOREIGN KEY (ID_Usuario) REFERENCES usuario (ID_Usuario)
) ENGINE=InnoDB;

CREATE TABLE prenda (
  ID_Prenda           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ID_Vendedor         INT UNSIGNED NOT NULL,
  ID_Categoria        INT UNSIGNED NOT NULL,
  ID_Marca            INT UNSIGNED NULL,
  Titulo              VARCHAR(100) NULL,
  Descripcion         TEXT         NOT NULL,
  Talle               VARCHAR(10)  NOT NULL,
  Color               VARCHAR(30)  NOT NULL,
  Material            VARCHAR(60)  NOT NULL,
  Estado_Conversacion ENUM('Excelente','Muy bueno','Bueno','Decente') NOT NULL,
  Precio              DECIMAL(10,2) UNSIGNED NOT NULL,
  Estado              ENUM('Pendiente','Aprobada','Rechazada','Vendida','Pausada') NOT NULL,
  FechaPublicacion    DATETIME     NOT NULL,
  PRIMARY KEY (ID_Prenda),
  KEY ID_Vendedor (ID_Vendedor),
  KEY ID_Categoria (ID_Categoria),
  KEY idx_prenda_marca (ID_Marca),
  CONSTRAINT fk_prenda_vendedor  FOREIGN KEY (ID_Vendedor)  REFERENCES usuario (ID_Usuario),
  CONSTRAINT fk_prenda_categoria FOREIGN KEY (ID_Categoria) REFERENCES categoria (ID_Categoria),
  CONSTRAINT fk_prenda_marca     FOREIGN KEY (ID_Marca)     REFERENCES marca (ID_Marca)
) ENGINE=InnoDB;

CREATE TABLE foto_prenda (
  ID_Foto   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ID_Prenda INT UNSIGNED NOT NULL,
  URL       VARCHAR(255) NOT NULL,
  Orden     TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (ID_Foto),
  KEY ID_Prenda (ID_Prenda),
  CONSTRAINT fk_foto_prenda FOREIGN KEY (ID_Prenda) REFERENCES prenda (ID_Prenda)
) ENGINE=InnoDB;

CREATE TABLE favorito (
  ID_Usuario     INT UNSIGNED NOT NULL,
  ID_Prenda      INT UNSIGNED NOT NULL,
  Fecha_Agregado DATETIME     NOT NULL,
  PRIMARY KEY (ID_Usuario, ID_Prenda),
  KEY idx_favorito_prenda (ID_Prenda),
  CONSTRAINT fk_favorito_usuario FOREIGN KEY (ID_Usuario) REFERENCES usuario (ID_Usuario),
  CONSTRAINT fk_favorito_prenda  FOREIGN KEY (ID_Prenda)  REFERENCES prenda (ID_Prenda)
) ENGINE=InnoDB;

CREATE TABLE pedido (
  ID_Pedido    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ID_Comprador INT UNSIGNED NOT NULL,
  ID_Direccion INT UNSIGNED NOT NULL,
  Fecha_pedido DATETIME     NOT NULL,
  Subtotal     DECIMAL(10,2) UNSIGNED NOT NULL,
  Costo_Envio  DECIMAL(10,2) UNSIGNED NOT NULL,
  Total        DECIMAL(10,2) UNSIGNED NOT NULL,
  Metodo_Pago  VARCHAR(30)  NOT NULL,
  Estado       ENUM('Pendiente_Pago','Pagado','Enviado','Entregado','Cancelado') NOT NULL,
  PRIMARY KEY (ID_Pedido),
  KEY ID_Comprador (ID_Comprador),
  KEY ID_Direccion (ID_Direccion),
  CONSTRAINT fk_pedido_comprador FOREIGN KEY (ID_Comprador) REFERENCES usuario (ID_Usuario),
  CONSTRAINT fk_pedido_direccion FOREIGN KEY (ID_Direccion) REFERENCES direccion (ID_Direccion)
) ENGINE=InnoDB;

CREATE TABLE detalle_pedido (
  ID_Pedido      INT UNSIGNED NOT NULL,
  ID_Prenda      INT UNSIGNED NOT NULL,
  Precio_Venta   DECIMAL(10,2) UNSIGNED NOT NULL,
  Comision_Chuko DECIMAL(10,2) UNSIGNED NOT NULL,
  Monto_Vendedor DECIMAL(10,2) UNSIGNED NOT NULL,
  PRIMARY KEY (ID_Pedido, ID_Prenda),
  KEY ID_Prenda (ID_Prenda),
  CONSTRAINT fk_detalle_pedido FOREIGN KEY (ID_Pedido) REFERENCES pedido (ID_Pedido),
  CONSTRAINT fk_detalle_prenda FOREIGN KEY (ID_Prenda) REFERENCES prenda (ID_Prenda)
) ENGINE=InnoDB;

CREATE TABLE envio (
  ID_Envio           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ID_Pedido          INT UNSIGNED NOT NULL,
  Empresa_Envio      VARCHAR(50)  NOT NULL,
  Codigo_Seguimiento VARCHAR(40)  NOT NULL,
  Fecha_Despacho     DATETIME     NOT NULL,
  Fecha_Entrega      DATETIME     NULL,
  PRIMARY KEY (ID_Envio),
  UNIQUE KEY uq_envio_pedido (ID_Pedido),
  CONSTRAINT fk_envio_pedido FOREIGN KEY (ID_Pedido) REFERENCES pedido (ID_Pedido)
) ENGINE=InnoDB;

CREATE TABLE valoracion (
  ID_Valoracion    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ID_Pedido        INT UNSIGNED NOT NULL,
  ID_Vendedor      INT UNSIGNED NOT NULL,
  Puntaje          TINYINT UNSIGNED NOT NULL,
  Comentario       VARCHAR(300) NULL,
  Fecha_Valoracion DATETIME     NOT NULL,
  PRIMARY KEY (ID_Valoracion),
  UNIQUE KEY uq_valoracion_pedido_vendedor (ID_Pedido, ID_Vendedor),
  KEY idx_valoracion_vendedor (ID_Vendedor),
  CONSTRAINT fk_valoracion_pedido   FOREIGN KEY (ID_Pedido)   REFERENCES pedido (ID_Pedido),
  CONSTRAINT fk_valoracion_vendedor FOREIGN KEY (ID_Vendedor) REFERENCES usuario (ID_Usuario),
  CONSTRAINT chk_valoracion_puntaje CHECK (Puntaje BETWEEN 1 AND 5)
) ENGINE=InnoDB;
