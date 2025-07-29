# 📦 Sistema de Gestión de Inventarios

Un sistema completo y funcional de gestión de inventarios desarrollado desde cero usando **PHP (MySQLi - estilo OOP)**, **MySQL**, **HTML/CSS** y **JavaScript vanilla** — sin frameworks ni librerías pesadas.

Diseñado para demostrar prácticas de codificación limpias y estructuradas para la gestión de stock de productos y movimientos de inventario.

---

## 🚀 Características Principales

### ✅ **Funcionalidades Básicas**
* ✅ Autenticación de usuarios (Login/Logout)
* ✅ Dashboard con estadísticas en tiempo real
* ✅ CRUD completo de productos (Crear, Leer, Actualizar, Eliminar)
* ✅ Movimientos de inventario (Entradas/Salidas de stock)
* ✅ Gestión de categorías
* ✅ Sistema de proveedores
* ✅ Códigos de barras y SKU automático

### ✅ **Funcionalidades Avanzadas**
* ✅ Subida y gestión de imágenes de productos
* ✅ Filtros y búsqueda avanzada
* ✅ Gráficas interactivas con Chart.js
* ✅ Interfaz responsive y moderna
* ✅ Validación de datos en tiempo real

### ✅ **Seguridad y Rendimiento**
* ✅ Prepared statements para prevenir SQL injection
* ✅ Validación de archivos de imagen
* ✅ Middleware de autenticación
* ✅ Índices de base de datos optimizados
* ✅ Código limpio y bien estructurado

---

## 📁 Estructura del Proyecto

```
inventory-management-system/
├── assets/
│   ├── css/style.css          # Estilos principales
│   └── js/script.js           # JavaScript del frontend
├── auth/
│   ├── login.php              # Página de login
│   ├── authenticate.php       # Autenticación
│   ├── logout.php             # Cerrar sesión
│   └── middleware.php         # Middleware de seguridad
├── config/
│   └── config.php             # Configuración centralizada
├── dashboard/
│   └── index.php              # Panel principal
├── includes/
│   └── sidebar.php            # Barra lateral
├── movements/
│   ├── index.php              # Lista de movimientos
│   └── new.php                # Nuevo movimiento
├── products/
│   ├── list.php               # Lista de productos
│   ├── add.php                # Agregar producto
│   ├── edit.php               # Editar producto
│   ├── delete.php             # Eliminar producto
│   └── categories.php         # Gestión de categorías
├── uploads/
│   └── products/              # Imágenes de productos
├── logs/                      # Archivos de log
├── connection.php             # Conexión a base de datos
├── inventory_management_system.sql  # Estructura de BD
├── update_database.sql        # Script de actualización
└── README.md                  # Este archivo
```

---

## 🛠️ Instalación y Configuración

### **Requisitos Previos**
- PHP 7.4 o superior
- MySQL 5.7 o superior
- Servidor web (Apache/Nginx)

### **Pasos de Instalación**

1. **Clonar el repositorio**
   ```bash
   git clone [url-del-repositorio]
   cd inventory-management-system
   ```

2. **Configurar la base de datos**
   ```bash
   # Importar la estructura inicial
   mysql -u root -p < inventory_management_system.sql
   
   # Si ya tienes la BD, ejecutar las actualizaciones
   mysql -u root -p < update_database.sql
   ```

3. **Configurar permisos**
   ```bash
   chmod 755 uploads/products/
   chmod 755 logs/
   ```

4. **Configurar la aplicación**
   - Editar `config/config.php` con tus configuraciones
   - Ajustar `connection.php` con tus credenciales de BD

5. **Acceder al sistema**
   - URL: `http://localhost/inventory-management-system`
   - Usuario: `admin`
   - Contraseña: `admin123`

---

## 📊 Características Técnicas

### **Base de Datos**
- **Tablas principales**: products, categories, movements, admins
- **Tablas adicionales**: suppliers, users, precios_proveedores
- **Índices optimizados** para búsquedas rápidas
- **Relaciones** con claves foráneas apropiadas

### **Frontend**
- **Bootstrap 5** para componentes UI
- **Bootstrap Icons** para iconografía
- **Chart.js** para gráficas interactivas
- **CSS personalizado** con diseño responsive

### **Backend**
- **PHP 7.4+** con MySQLi orientado a objetos
- **Prepared statements** para seguridad
- **Validación de datos** en servidor y cliente
- **Manejo de archivos** con validación de tipos

---

## 🔒 Seguridad

- ✅ **Autenticación** con sesiones seguras
- ✅ **Validación** de entrada de usuario
- ✅ **Prepared statements** contra SQL injection
- ✅ **Validación de archivos** para subidas
- ✅ **Protección de directorios** con .htaccess
- ✅ **Sanitización** de datos de salida

---

## 🚀 Funcionalidades Destacadas

### **Dashboard Inteligente**
- Estadísticas en tiempo real
- Gráficas de stock por producto
- Movimientos de los últimos 7 días
- Alertas de stock bajo

### **Gestión de Productos**
- SKU automático o manual
- Códigos de barras
- Imágenes de productos
- Categorización flexible
- Proveedores dinámicos

### **Movimientos de Inventario**
- Entradas y salidas de stock
- Validación de stock disponible
- Historial completo de movimientos
- Escáner de códigos de barras

### **Búsqueda y Filtros**
- Búsqueda por nombre, SKU, categoría, proveedor
- Filtros dinámicos
- Interfaz intuitiva y rápida

---

## 📝 Notas de Desarrollo

### **Mejoras Recientes**
- ✅ Agregados campos `barcode` e `image` a la BD
- ✅ Configuración centralizada en `config/config.php`
- ✅ Directorio de uploads protegido
- ✅ Script de actualización de BD
- ✅ Funciones de utilidad para validación
- ✅ Eliminadas funcionalidades de comparador de precios (no funcionales)

### **Próximas Mejoras Sugeridas**
- [ ] Sistema de roles de usuario
- [ ] Reportes en PDF
- [ ] Notificaciones por email
- [ ] API REST para integraciones
- [ ] Backup automático de BD
- [ ] Auditoría de cambios

---

## 🤝 Contribución

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

---

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

---

## 👨‍💻 Autor

**Alarmas y Cámaras de seguridad del sureste**

- **Email**: [tu-email@ejemplo.com]
- **Sitio web**: [tu-sitio-web.com]

---

## 🙏 Agradecimientos

- Bootstrap para el framework CSS
- Chart.js para las gráficas
