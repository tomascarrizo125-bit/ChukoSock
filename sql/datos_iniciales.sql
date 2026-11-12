-- Datos de referencia recuperados de la base original (categorías y marcas).
USE chukostock;

INSERT INTO categoria (ID_Categoria, Nombre, Descripcion) VALUES
(1,'Remeras y Tops','Remeras, musculosas, tops y bodies de uso diario.'),
(2,'Camisas y Blusas','Camisas, blusas y chombas casuales o de vestir'),
(3,'Buzos y Sweaters','Buzos, hoodies, sweaters y cardigans'),
(4,'Camperas y Abrigos','Camperas, tapados, parkas y chalecos'),
(5,'Jeans','Jeans de todos los cortes: mom, recto, oxford, skinny'),
(6,'Pantalones y Shorts','Pantalones de vestir, joggers, cargo, bermudas y shorts'),
(7,'Vestidos y Polleras','Vestidos, polleras y monos'),
(8,'Calzado','Zapatillas, botas, sandalias y zapatos'),
(9,'Accesorios','Carteras, cinturones, bufandas, gorros y bijouterie'),
(10,'Ropa Deportiva','Calzas, camisetas, conjuntos y ropa de entrenamiento');

INSERT INTO marca (Nombre) VALUES
('47 Street'),('Adidas'),('Ayres'),('Camper'),('Cheeky'),('Complot'),('Converse'),('Cuesta Blanca'),
('Fila'),('H&M'),('Kosiuko'),('Legacy'),('Levi''s'),('Mango'),('Mimo'),('Montagne'),('Nike'),('Puma'),
('Rapsodia'),('Reebok'),('Tascani'),('The North Face'),('Topper'),('Under Armour'),('Uniqlo'),('Vans'),
('Vitamina'),('Wrangler'),('Zara');
