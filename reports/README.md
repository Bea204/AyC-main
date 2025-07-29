# Módulo de Reportes y Estadísticas

Este módulo proporciona funcionalidades completas para generar reportes y estadísticas del sistema de inventarios, incluyendo exportación a PDF, Excel y CSV.

## Funcionalidades Implementadas

### 📊 Estadísticas Principales
- **Total de Productos**: Cuenta todos los productos activos
- **Valor Total del Inventario**: Suma del valor de todos los productos
- **Productos con Stock Bajo**: Productos que están en o por debajo del stock mínimo
- **Productos Sin Stock**: Productos con cantidad 0
- **Total Cotizaciones**: Número total de cotizaciones en el sistema
- **Clientes Únicos**: Número de clientes que han recibido cotizaciones
- **Total Bobinas**: Número total de bobinas registradas
- **Total Insumos**: Número total de insumos activos

### 📈 Gráficas y Visualizaciones
- **Movimientos Mensuales**: Gráfica de barras mostrando entradas y salidas de los últimos 6 meses
- **Top Categorías**: Tabla con las categorías más valiosas
- **Top Proveedores**: Tabla con los proveedores más importantes
- **Productos Más Vendidos**: Lista de productos ordenados por movimientos
- **Productos con Stock Bajo**: Alerta de productos que requieren atención

### 📤 Funciones de Exportación

#### Formatos Soportados:
1. **PDF**: Genera un documento HTML optimizado para impresión
2. **Excel**: Genera una tabla HTML compatible con Excel
3. **CSV**: Archivo de texto separado por comas con codificación UTF-8

#### Tipos de Reportes Exportables:
- `movements`: Movimientos mensuales
- `categories`: Top categorías por valor
- `suppliers`: Top proveedores
- `products`: Productos más vendidos
- `low-stock`: Productos con stock bajo

### 🔧 Archivos Principales

#### `index.php`
- Página principal de reportes
- Muestra todas las estadísticas y gráficas
- Botones de exportación integrados

#### `export.php`
- Maneja todas las exportaciones
- Parámetros: `type` (tipo de reporte) y `format` (formato)
- Ejemplo: `export.php?type=products&format=excel`

#### `functions.php`
- Contiene todas las funciones de consulta a la base de datos
- Funciones optimizadas para mostrar datos incluso sin movimientos

### 🚀 Cómo Usar las Exportaciones

#### Desde la Interfaz Web:
1. Ve a **Reportes y Estadísticas**
2. Encuentra la sección que quieres exportar
3. Haz clic en el botón correspondiente:
   - 📄 **PDF**: Para imprimir o guardar como PDF
   - 📊 **Excel**: Para abrir en Excel
   - 📋 **CSV**: Para importar en otras aplicaciones

#### URLs Directas:
```
export.php?type=products&format=excel
export.php?type=categories&format=csv
export.php?type=suppliers&format=pdf
export.php?type=movements&format=excel
export.php?type=low-stock&format=csv
```

### 🛠️ Archivos de Prueba

#### `test_export.php`
- Archivo de prueba para verificar que los datos se obtienen correctamente
- Muestra enlaces directos a las exportaciones
- Útil para debugging

### 📋 Correcciones Implementadas

#### Problema de Productos No Visibles:
- **Antes**: Solo mostraba productos con movimientos de salida
- **Ahora**: Muestra todos los productos activos, incluso sin movimientos
- **Cambio**: Uso de `LEFT JOIN` en lugar de `JOIN` y `COALESCE` para valores nulos

#### Problema de Categorías Vacías:
- **Antes**: Solo mostraba categorías con valor total > 0
- **Ahora**: Muestra categorías con al menos un producto
- **Cambio**: Uso de `HAVING product_count > 0` en lugar de `HAVING total_value > 0`

#### Problema de Proveedores Vacíos:
- **Antes**: Solo mostraba proveedores con valor total > 0
- **Ahora**: Muestra proveedores con al menos un producto
- **Cambio**: Uso de `HAVING product_count > 0` en lugar de `HAVING total_value > 0`

### 🔒 Seguridad

- Todas las exportaciones requieren autenticación
- Verificación de sesión en cada archivo
- Sanitización de datos antes de la exportación
- Headers de seguridad para descargas

### 📱 Responsive Design

- Las tablas se adaptan a dispositivos móviles
- Botones de exportación reorganizados en pantallas pequeñas
- Gráficas responsivas con Chart.js

### 🎨 Estilo Consistente

- Mantiene el diseño del sistema principal
- Colores corporativos (#121866)
- Iconos de Bootstrap Icons
- Alertas profesionales para stock bajo

## Próximas Mejoras

1. **Exportación Avanzada de PDF**: Integración con TCPDF para PDFs más profesionales
2. **Filtros de Fecha**: Permitir exportar datos por rangos de fecha
3. **Reportes Personalizados**: Permitir al usuario crear reportes a medida
4. **Envío por Email**: Opción para enviar reportes por correo electrónico
5. **Programación de Reportes**: Generación automática de reportes periódicos 