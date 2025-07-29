# 🚀 Configuración del Sistema de Gestión de Inventarios

## 📋 Requisitos Previos

- **Servidor Web**: XAMPP, WAMP, o similar
- **PHP**: 7.4 o superior
- **MySQL**: 5.7 o superior
- **Navegador**: Chrome, Firefox, Safari, Edge

## 🗄️ Paso 1: Configurar la Base de Datos

### 1.1 Crear la base de datos
```sql
CREATE DATABASE inventory_management_system2;
```

### 1.2 Importar la estructura completa
1. Abre phpMyAdmin
2. Selecciona la base de datos `inventory_management_system2`
3. Ve a la pestaña "Importar"
4. Selecciona el archivo: `inventory_management_system_complete.sql`
5. Haz clic en "Continuar"

### 1.3 Insertar datos iniciales
1. Abre tu navegador
2. Ve a: `http://localhost/inventory-management-system-main/setup_initial_data.php`
3. Ejecuta el script para insertar datos de prueba

## ⚙️ Paso 2: Configurar la Conexión

### 2.1 Verificar connection.php
El archivo `connection.php` ya está configurado para:
- Host: localhost
- Usuario: root
- Contraseña: (vacía)
- Base de datos: inventory_management_system2

### 2.2 Verificar config.php
El archivo `config/config.php` contiene la configuración de la aplicación.

## 🔐 Paso 3: Credenciales de Acceso

Después de ejecutar el script de configuración, tendrás acceso con:

### Usuario Administrador
- **Usuario**: `admin`
- **Contraseña**: `admin123`
- **Permisos**: Acceso completo al sistema

### Usuario Estándar
- **Usuario**: `user`
- **Contraseña**: `user123`
- **Permisos**: Acceso limitado (sin eliminar, sin configuración)

## 🎯 Paso 4: Probar el Sistema

### 4.1 Acceder al sistema
1. Ve a: `http://localhost/inventory-management-system-main/auth/login.php`
2. Inicia sesión con las credenciales proporcionadas

### 4.2 Verificar funcionalidades
- ✅ Dashboard con estadísticas en tiempo real
- ✅ Gestión de productos
- ✅ Gestión de categorías y proveedores
- ✅ Movimientos de inventario
- ✅ Sistema de bobinas
- ✅ Cotizaciones
- ✅ Reportes
- ✅ Gestión de usuarios y permisos

## 🔧 Características del Sistema

### 📊 Dashboard
- Estadísticas en tiempo real
- Gráficas de stock y movimientos
- Alertas de stock bajo
- Información de productos más movidos

### 📦 Gestión de Productos
- CRUD completo de productos
- Soporte para imágenes
- Códigos de barras
- Gestión de categorías y proveedores
- Sistema de bobinas para cables

### 🔄 Movimientos de Inventario
- Entradas y salidas
- Diferentes tipos de movimiento
- Historial completo
- Trazabilidad

### 📋 Cotizaciones
- Crear cotizaciones profesionales
- Convertir a ventas
- Historial de clientes
- Impresión de cotizaciones

### 👥 Gestión de Usuarios
- Sistema de roles y permisos
- Permisos personalizados por usuario
- Control de acceso granular
- Logs de actividad

### 📈 Reportes
- Reportes semanales, mensuales y personalizados
- Análisis de movimientos
- Estadísticas de ventas
- Exportación de datos

## 🛠️ Estructura de Archivos

```
inventory-management-system-main/
├── auth/                    # Autenticación y middleware
├── assets/                  # CSS, JS e imágenes
├── bobinas/                 # Gestión de bobinas
├── clients/                 # Gestión de clientes
├── config/                  # Configuración del sistema
├── cotizaciones/            # Sistema de cotizaciones
├── dashboard/               # Panel principal
├── equipos/                 # Gestión de equipos
├── includes/                # Componentes reutilizables
├── insumos/                 # Gestión de insumos
├── inventory/               # Vista de inventario
├── movements/               # Movimientos de inventario
├── products/                # Gestión de productos
├── proveedores/             # Gestión de proveedores
├── reports/                 # Reportes del sistema
├── system_config/           # Configuración del sistema
├── usuarios/                # Gestión de usuarios
├── uploads/                 # Archivos subidos
├── connection.php           # Conexión a base de datos
├── inventory_management_system_complete.sql  # Estructura de BD
├── setup_initial_data.php   # Datos iniciales
└── README_CONFIGURACION.md  # Este archivo
```

## 🔒 Sistema de Permisos

### Roles Disponibles
1. **Admin**: Acceso completo al sistema
2. **User**: Acceso estándar (sin eliminar, sin configuración)
3. **Viewer**: Solo visualización

### Módulos con Permisos
- **products**: Gestión de productos
- **movements**: Movimientos de inventario
- **cotizaciones**: Sistema de cotizaciones
- **insumos**: Gestión de insumos
- **equipos**: Gestión de equipos
- **usuarios**: Gestión de usuarios
- **reportes**: Reportes del sistema
- **configuracion**: Configuración del sistema

### Acciones Disponibles
- **read**: Leer/ver
- **write**: Crear/editar
- **delete**: Eliminar

## 🚨 Solución de Problemas

### Error de conexión a base de datos
1. Verifica que XAMPP esté ejecutándose
2. Confirma que MySQL esté activo
3. Revisa las credenciales en `connection.php`

### Error de permisos
1. Verifica que el usuario tenga los permisos correctos
2. Revisa la configuración de roles en la base de datos

### Imágenes no se cargan
1. Verifica que la carpeta `uploads/` tenga permisos de escritura
2. Confirma que las rutas en el código sean correctas

### Errores de JavaScript
1. Verifica que la consola del navegador no muestre errores
2. Confirma que Chart.js esté cargado correctamente

## 📞 Soporte

Si tienes problemas con la configuración:

1. **Verifica los logs**: Revisa los archivos de log en la carpeta `logs/`
2. **Consulta la documentación**: Revisa los comentarios en el código
3. **Prueba paso a paso**: Sigue las instrucciones en orden

## 🎉 ¡Listo!

Una vez completados todos los pasos, tu sistema de gestión de inventarios estará completamente funcional con:

- ✅ Base de datos normalizada y completa
- ✅ Sistema de autenticación seguro
- ✅ Gestión de permisos granular
- ✅ Dashboard en tiempo real
- ✅ Todas las funcionalidades operativas

¡Disfruta usando tu nuevo sistema de gestión de inventarios! 🚀 