# 📦 Sistema de Gestión de Inventarios - Maquetados

Sistema completo de gestión de inventarios para **Alarmas y Cámaras de seguridad del sureste** con maquetados funcionales sin necesidad de base de datos.

## 🚀 Módulos Implementados

### ✅ **Módulos Principales (Ya Funcionales)**
- ✅ **Dashboard** - Panel principal con estadísticas
- ✅ **Productos** - CRUD completo de productos
- ✅ **Movimientos** - Gestión de entradas/salidas
- ✅ **Configuración** - Gestión de usuarios

### 🎨 **Nuevos Maquetados Creados**

#### 1. **Gestión de Proveedores** (`/suppliers/`)
- Lista de proveedores con información detallada
- Estadísticas de productos y valor total
- Sistema de calificación y estado
- Formulario para agregar nuevos proveedores
- Gestión de límites de crédito

#### 2. **Reportes y Estadísticas** (`/reports/`)
- Dashboard de reportes con gráficas interactivas
- Estadísticas de movimientos mensuales
- Top categorías por valor
- Top proveedores
- Exportación a PDF, Excel y CSV
- Reportes personalizables por fecha

#### 3. **Gestión de Clientes** (`/clients/`)
- Registro completo de clientes
- Información de contacto y ubicación
- Historial de órdenes y gastos
- Sistema de límites de crédito
- Estado de cuenta y alertas
- Gestión de múltiples contactos

#### 4. **Órdenes de Compra** (`/purchase_orders/`)
- Creación y gestión de órdenes de compra
- Estados: Pendiente, Aprobada, Entregada, Cancelada
- Filtros por proveedor, estado y fecha
- Seguimiento de entregas
- Notas y comentarios
- Historial completo de órdenes

#### 5. **Gestión de Almacenes** (`/warehouses/`)
- Múltiples ubicaciones de almacén
- Control de capacidad y utilización
- Encargados por almacén
- Inventario por ubicación
- Auditorías por almacén
- Estadísticas de ocupación

#### 6. **Auditorías de Inventario** (`/audit/`)
- Auditorías programadas y manuales
- Comparación stock físico vs sistema
- Cálculo de precisión
- Reportes de discrepancias
- Diferentes tipos de auditoría
- Historial de auditorías

#### 7. **Configuración del Sistema** (`/system_config/`)
- Información de la empresa
- Configuración regional (moneda, zona horaria)
- Parámetros de inventario
- Configuración de seguridad
- Gestión de respaldos
- Información técnica del sistema

## 📁 Estructura de Archivos

```
inventory-management-system-main/
├── auth/                          # Autenticación
├── dashboard/                     # Panel principal
├── products/                      # Gestión de productos
├── movements/                     # Movimientos de inventario
├── configuracion/                # Gestión de usuarios
├── suppliers/                     # 🆕 Gestión de proveedores
│   └── index.php
├── clients/                       # 🆕 Gestión de clientes
│   └── index.php
├── purchase_orders/               # 🆕 Órdenes de compra
│   └── index.php
├── warehouses/                    # 🆕 Gestión de almacenes
│   └── index.php
├── audit/                         # 🆕 Auditorías de inventario
│   └── index.php
├── reports/                       # 🆕 Reportes y estadísticas
│   └── index.php
├── system_config/                 # 🆕 Configuración del sistema
│   └── index.php
├── assets/                        # Recursos estáticos
├── includes/                      # Componentes reutilizables
└── uploads/                       # Archivos subidos
```

## 🎯 Características de los Maquetados

### **Diseño Moderno y Responsivo**
- Interfaz limpia y profesional
- Diseño responsive para móviles
- Iconografía Bootstrap Icons
- Paleta de colores corporativa
- Animaciones y transiciones suaves

### **Funcionalidades Avanzadas**
- Filtros y búsquedas dinámicas
- Gráficas interactivas con Chart.js
- Modales para confirmaciones
- Validación de formularios
- Alertas y notificaciones
- Estados visuales (activo/inactivo)

### **Datos Simulados Realistas**
- Información de empresa real
- Productos típicos del sector
- Proveedores conocidos (Syscom, PCH, Dahua)
- Estadísticas coherentes
- Fechas y cantidades realistas

## 🛠️ Tecnologías Utilizadas

- **PHP 8.2** - Backend
- **MySQL/MariaDB** - Base de datos
- **HTML5/CSS3** - Frontend
- **JavaScript ES6** - Interactividad
- **Bootstrap 5** - Framework CSS
- **Chart.js** - Gráficas
- **Bootstrap Icons** - Iconografía

## 🚀 Instalación y Uso

### **Requisitos**
- PHP 7.4 o superior
- MySQL 5.7 o superior
- Servidor web (Apache/Nginx)

### **Configuración**
1. Clonar el repositorio
2. Configurar la base de datos
3. Ajustar `connection.php` con credenciales
4. Acceder al sistema

### **Acceso**
- URL: `http://localhost/inventory-management-system`
- Usuario: `admin`
- Contraseña: `admin123`

## 📊 Funcionalidades por Módulo

### **Proveedores**
- ✅ Lista con información detallada
- ✅ Estadísticas de productos
- ✅ Sistema de calificación
- ✅ Gestión de crédito
- ✅ Estados activo/inactivo

### **Clientes**
- ✅ Registro completo
- ✅ Historial de compras
- ✅ Límites de crédito
- ✅ Estados de cuenta
- ✅ Múltiples contactos

### **Órdenes de Compra**
- ✅ Estados de orden
- ✅ Filtros avanzados
- ✅ Seguimiento de entregas
- ✅ Notas y comentarios
- ✅ Historial completo

### **Almacenes**
- ✅ Múltiples ubicaciones
- ✅ Control de capacidad
- ✅ Encargados por almacén
- ✅ Inventario por ubicación
- ✅ Auditorías específicas

### **Auditorías**
- ✅ Tipos de auditoría
- ✅ Cálculo de precisión
- ✅ Reportes de discrepancias
- ✅ Estados de auditoría
- ✅ Historial completo

### **Reportes**
- ✅ Gráficas interactivas
- ✅ Exportación múltiple
- ✅ Filtros por fecha
- ✅ Estadísticas detalladas
- ✅ Reportes personalizables

### **Configuración Sistema**
- ✅ Información empresa
- ✅ Configuración regional
- ✅ Parámetros inventario
- ✅ Configuración seguridad
- ✅ Gestión respaldos

## 🎨 Diseño y UX

### **Paleta de Colores**
- **Primario**: #121866 (Azul corporativo)
- **Secundario**: #232a7c (Azul claro)
- **Éxito**: #28a745 (Verde)
- **Advertencia**: #ffc107 (Amarillo)
- **Peligro**: #dc3545 (Rojo)
- **Info**: #17a2b8 (Cian)

### **Componentes Reutilizables**
- Cards con hover effects
- Modales de confirmación
- Barras de progreso
- Badges de estado
- Formularios consistentes
- Tablas responsivas

## 🔒 Seguridad

- Autenticación con sesiones
- Middleware de protección
- Validación de formularios
- Sanitización de datos
- Prepared statements
- Control de acceso por roles

## 📈 Próximas Mejoras

### **Funcionalidades Planificadas**
- [ ] API REST para integraciones
- [ ] Notificaciones push
- [ ] Reportes en PDF
- [ ] Backup automático
- [ ] Auditoría de cambios
- [ ] Dashboard móvil

### **Mejoras Técnicas**
- [ ] Caché de consultas
- [ ] Optimización de imágenes
- [ ] Compresión de archivos
- [ ] Logs detallados
- [ ] Monitoreo de rendimiento

## 👨‍💻 Desarrollo

### **Estructura de Código**
- Código limpio y bien documentado
- Separación de responsabilidades
- Reutilización de componentes
- Estándares de codificación
- Comentarios explicativos

### **Mantenimiento**
- Archivos organizados por módulo
- Configuración centralizada
- Fácil actualización
- Documentación completa
- Testing manual

## 📞 Soporte

Para soporte técnico o consultas sobre el sistema:

- **Empresa**: Alarmas y Cámaras de seguridad del sureste
- **Email**: info@alarmasycamaras.com
- **Teléfono**: 55-1234-5678

---

**Desarrollado con ❤️ para Alarmas y Cámaras de seguridad del sureste**

*Sistema de Gestión de Inventarios v2.1.0* 