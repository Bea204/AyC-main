# 📊 Módulo de Reportes - Sistema de Gestión de Inventarios

Este módulo proporciona reportes completos y análisis detallados del sistema de inventarios, permitiendo tomar decisiones informadas basadas en datos reales.

## 🚀 Características Principales

### ✅ **Reportes Disponibles**
- **Reporte Principal**: Dashboard general con estadísticas del sistema
- **Reporte Semanal**: Análisis de la semana actual
- **Reporte Mensual**: Análisis del mes actual
- **Reporte Personalizado**: Análisis por período específico

### ✅ **Funcionalidades Avanzadas**
- Gráficas interactivas con Chart.js
- Exportación a PDF, Excel y CSV (en desarrollo)
- Alertas de stock bajo
- Análisis de tendencias
- Comparativas por períodos

## 📁 Estructura de Archivos

```
reports/
├── index.php              # Reporte principal (dashboard)
├── semanal.php            # Reporte semanal
├── mensual.php            # Reporte mensual
├── personalizado.php      # Reporte personalizado
├── functions.php          # Funciones auxiliares
├── setup_test_data.php    # Script para datos de prueba
└── README.md              # Este archivo
```

## 🛠️ Instalación y Configuración

### **Requisitos Previos**
- Sistema de inventarios funcionando
- Base de datos configurada
- Usuario con permisos de acceso

### **Configuración Inicial**

1. **Ejecutar script de datos de prueba** (opcional):
   ```bash
   # Acceder al script desde el navegador
   http://localhost/AyC-main/reports/setup_test_data.php
   ```

2. **Verificar permisos de usuario**:
   - El usuario debe tener acceso al módulo de reportes
   - Permisos de lectura en todas las tablas del sistema

## 📊 Tipos de Reportes

### **1. Reporte Principal (`index.php`)**

**Funcionalidades:**
- Estadísticas generales del sistema
- Gráfica de movimientos de los últimos 6 meses
- Top categorías por valor
- Top proveedores
- Productos más vendidos
- Productos con stock bajo
- Alertas automáticas

**Métricas incluidas:**
- Total de productos
- Valor total del inventario
- Productos con stock bajo
- Productos sin stock
- Total de cotizaciones
- Clientes únicos
- Total de bobinas
- Total de insumos

### **2. Reporte Semanal (`semanal.php`)**

**Funcionalidades:**
- Análisis de la semana actual (lunes a domingo)
- Gráfica de movimientos diarios
- Categorías más movidas
- Productos más movidos
- Cotizaciones de la semana

**Métricas incluidas:**
- Total movimientos
- Entradas vs Salidas
- Productos movidos
- Unidades vendidas
- Valor de ventas
- Cotizaciones generadas

### **3. Reporte Mensual (`mensual.php`)**

**Funcionalidades:**
- Análisis del mes actual
- Gráfica de movimientos por semana
- Categorías más movidas
- Proveedores más utilizados
- Productos más vendidos
- Cotizaciones del mes

**Métricas incluidas:**
- Total movimientos del mes
- Entradas vs Salidas
- Productos movidos
- Unidades vendidas
- Valor de ventas
- Total cotizaciones

### **4. Reporte Personalizado (`personalizado.php`)**

**Funcionalidades:**
- Selección de período personalizado
- Gráfica de movimientos diarios
- Categorías más movidas
- Proveedores más utilizados
- Productos más movidos
- Cotizaciones del período

**Características:**
- Validación de fechas
- Interfaz intuitiva
- Análisis detallado por período

## 🔧 Funciones Auxiliares (`functions.php`)

### **Funciones de Estadísticas**
- `getSystemStats()`: Estadísticas generales del sistema
- `getQuotesStats()`: Estadísticas de cotizaciones
- `getBobinasStats()`: Estadísticas de bobinas
- `getInsumosStats()`: Estadísticas de insumos
- `getEquiposStats()`: Estadísticas de equipos
- `getUsersStats()`: Estadísticas de usuarios

### **Funciones de Movimientos**
- `getMovementsByPeriod()`: Movimientos por período
- `getMonthlyMovements()`: Movimientos mensuales
- `getTopProducts()`: Productos más vendidos
- `getLowStockProducts()`: Productos con stock bajo

### **Funciones de Análisis**
- `getTopCategories()`: Top categorías por valor
- `getTopSuppliers()`: Top proveedores
- `getQuotesByPeriod()`: Cotizaciones por período

### **Funciones de Utilidad**
- `formatNumber()`: Formatear números
- `formatCurrency()`: Formatear moneda
- `getPercentage()`: Calcular porcentajes
- `validateReportDates()`: Validar fechas
- `getReportPeriod()`: Obtener períodos predefinidos

## 📈 Gráficas y Visualizaciones

### **Chart.js Integration**
- Gráficas de barras para movimientos
- Colores consistentes con el tema
- Responsive design
- Interactividad completa

### **Tipos de Gráficas**
- **Movimientos Mensuales**: Entradas vs Salidas por mes
- **Movimientos Semanales**: Entradas vs Salidas por semana
- **Movimientos Diarios**: Entradas vs Salidas por día

## 🎨 Diseño y UX

### **Características de Diseño**
- Interfaz moderna y limpia
- Diseño responsive
- Paleta de colores corporativa
- Iconografía Bootstrap Icons
- Animaciones suaves

### **Componentes Reutilizables**
- Tarjetas de estadísticas
- Tablas responsivas
- Gráficas interactivas
- Botones de exportación
- Alertas de stock

## 📱 Responsive Design

### **Breakpoints**
- **Desktop**: > 900px
- **Tablet**: 768px - 900px
- **Mobile**: < 768px

### **Adaptaciones**
- Sidebar colapsable
- Gráficas responsivas
- Tablas con scroll horizontal
- Botones apilados en móvil

## 🔒 Seguridad

### **Validaciones**
- Validación de fechas
- Sanitización de datos
- Prepared statements
- Control de acceso por middleware

### **Permisos**
- Verificación de autenticación
- Control de roles
- Logs de acceso

## 🚀 Funcionalidades Futuras

### **En Desarrollo**
- [ ] Exportación a PDF
- [ ] Exportación a Excel
- [ ] Exportación a CSV
- [ ] Reportes programados
- [ ] Notificaciones automáticas
- [ ] Dashboard personalizable

### **Planeadas**
- [ ] Reportes comparativos
- [ ] Análisis de tendencias
- [ ] Predicciones de stock
- [ ] Reportes de rentabilidad
- [ ] Análisis de clientes

## 🛠️ Mantenimiento

### **Optimización de Consultas**
- Índices en base de datos
- Consultas preparadas
- Caché de resultados
- Paginación de datos

### **Monitoreo**
- Logs de errores
- Métricas de rendimiento
- Alertas de sistema

## 📞 Soporte

### **Documentación**
- Comentarios en código
- Funciones documentadas
- Ejemplos de uso

### **Troubleshooting**
- Validación de datos
- Manejo de errores
- Mensajes informativos

## 🎯 Casos de Uso

### **Para Administradores**
- Análisis general del sistema
- Toma de decisiones estratégicas
- Monitoreo de rendimiento

### **Para Vendedores**
- Análisis de ventas
- Productos más populares
- Tendencias de mercado

### **Para Almacén**
- Control de stock
- Alertas de inventario
- Optimización de espacio

---

## 📝 Notas de Desarrollo

### **Versión Actual**: 1.0.0
### **Última Actualización**: Enero 2025
### **Compatibilidad**: PHP 7.4+, MySQL 5.7+

### **Dependencias**
- Chart.js 3.x
- Bootstrap 5.x
- Bootstrap Icons 1.x
- PHP MySQLi

---

**Desarrollado para Alarmas y Cámaras de seguridad del sureste** 