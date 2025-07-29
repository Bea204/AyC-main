-- Script para actualizar la estructura de la tabla insumos
-- Cambiar de dependiente de productos a entidades independientes

-- 1. Agregar nuevas columnas necesarias
ALTER TABLE insumos 
ADD COLUMN category_id INT(11) DEFAULT NULL AFTER product_id,
ADD COLUMN supplier_id INT(11) DEFAULT NULL AFTER category_id;

-- Agregar columna para imagen de insumo
ALTER TABLE insumos ADD COLUMN imagen VARCHAR(255) DEFAULT NULL AFTER unidad;

-- 2. Agregar índices para las nuevas columnas
ALTER TABLE insumos 
ADD INDEX idx_category_id (category_id),
ADD INDEX idx_supplier_id (supplier_id);

-- 3. Agregar restricciones de clave foránea
ALTER TABLE insumos 
ADD CONSTRAINT insumos_ibfk_2 FOREIGN KEY (category_id) REFERENCES categories (category_id) ON DELETE SET NULL,
ADD CONSTRAINT insumos_ibfk_3 FOREIGN KEY (supplier_id) REFERENCES suppliers (supplier_id) ON DELETE SET NULL;

-- 4. Hacer la columna product_id opcional (NULL) en lugar de obligatoria
-- Esto permite que los insumos existan sin estar vinculados a un producto
ALTER TABLE insumos 
MODIFY COLUMN product_id INT(11) NULL;

-- 5. Actualizar la restricción de clave foránea de product_id para permitir NULL
-- Primero eliminar la restricción existente
ALTER TABLE insumos 
DROP FOREIGN KEY insumos_ibfk_1;

-- Luego agregar la nueva restricción que permite NULL
ALTER TABLE insumos 
ADD CONSTRAINT insumos_ibfk_1 FOREIGN KEY (product_id) REFERENCES products (product_id) ON DELETE SET NULL;

-- 6. Comentario explicativo
-- La tabla insumos ahora puede funcionar de dos formas:
-- 1. Como entidades independientes (product_id = NULL, category_id y supplier_id definidos)
-- 2. Como derivados de productos (product_id definido, category_id y supplier_id pueden ser NULL) 