# 🎰 Casino Online - TFG Marc y Javier

> **Proyecto Final de Grado**: Desarrollo de una plataforma de casino online con juegos interactivos, gestión de saldos y sistema de pagos integrado con Stripe.

---

## 📋 Descripción del Proyecto

**Casino Online** es una aplicación web moderna desarrollada en **PHP 7.4+** que permite a los usuarios:

- 🎮 Jugar a 4 juegos diferentes (Blackjack, Ruleta, Minas, Tragaperras)
- 💰 Gestionar su saldo de dinero virtual
- 💳 Realizar depósitos seguros con Stripe
- 📊 Ver historial completo de partidas
- 👤 Administrar su perfil de usuario
- 🏆 Competir en rankings

---

## 🚀 Características Principales

### 🎮 Juegos Disponibles
- **Blackjack** - Juego de cartas clásico contra el crupier
- **Ruleta** - Apuestas a números, colores y combinaciones
- **Minas** - Juego de descubrimiento con multiplicadores progresivos
- **Tragaperras** - Máquina tragamonedas con líneas de pago

### 💻 Características Técnicas
- ✅ Autenticación y gestión de sesiones segura
- ✅ Base de datos relacional (MySQL)
- ✅ API REST para operaciones asincrónicas (AJAX)
- ✅ Integración con Stripe para pagos seguros
- ✅ Webhooks para confirmar transacciones
- ✅ Panel de administración
- ✅ Historial completo de transacciones
- ✅ Sistema de niveles VIP

### 📱 Diseño Responsivo
- Desktop (1920px+)
- Tablet (768px - 1024px)
- Mobile (320px - 767px)

---

## 🛠️ Tecnologías Utilizadas

### Backend
| Tecnología | Versión | Descripción |
|-----------|---------|------------|
| PHP | 7.4+ | Lenguaje de programación servidor |
| MySQL | 5.7+ | Base de datos relacional |
| Stripe API | 2024 | Procesamiento de pagos |
| Composer | 2.0+ | Gestor de paquetes PHP |

### Frontend
| Tecnología | Uso |
|-----------|-----|
| HTML5 | Estructura semántica |
| CSS3 | Estilos y diseño responsivo |
| JavaScript | Interactividad y AJAX |
| jQuery | (Opcional) Manipulación DOM |

### Herramientas Adicionales
- **Apache/Nginx** - Servidor web
- **Git** - Control de versiones
- **Visual Studio Code** - Editor recomendado
- **Postman** - Testing de APIs
- **phpMyAdmin** - Administración de BD

---

## 📁 Estructura del Proyecto

```
CasinoTFG-Marc-Javi/
│
├── 📄 README.md                    # Este archivo
├── 📄 TODO.md                      # Tareas pendientes
├── 📄 WIREFRAMES.md               # Diseños de pantallas
│
└── CASINOPRUEBAS/PRINCIPAL/
    │
    ├── 🔐 AUTENTICACIÓN
    │   ├── index.php               # Login / Registro
    │   ├── logout.php              # Cierre de sesión
    │   ├── config.php              # Configuración
    │   └── session_helpers.php     # Funciones de sesión
    │
    ├── 👤 ÁREA DE USUARIO
    │   ├── principal.php           # Dashboard
    │   ├── perfil.php              # Perfil de usuario
    │   ├── saldo.php               # API: saldo (AJAX)
    │   └── historial.php           # Historial de partidas
    │
    ├── 🎮 JUEGOS
    │   ├── blackjack.php           # Juego Blackjack
    │   ├── ruleta.php              # Juego Ruleta
    │   ├── minas.php               # Juego Minas
    │   └── tragaperras.php         # Juego Tragaperras
    │
    ├── 💳 PAGOS
    │   ├── crear_sesion_pago.php   # Crear sesión Stripe
    │   ├── exito.php               # Confirmación de pago
    │   ├── webhook.php             # Webhook de Stripe
    │   └── guardar_entrada.php     # API: guardar juego
    │
    ├── 🎨 ASSETS
    │   ├── assets/app.css          # Estilos globales
    │   ├── assets/app.js           # JavaScript global
    │   └── img/cards/              # Imágenes de cartas
    │
    ├── 🔐 ADMIN
    │   └── admin/index.php         # Panel de administrador
    │
    ├── 🗄️ DATABASE
    │   └── casinobd.sql            # Esquema SQL
    │
    ├── 📦 DEPENDENCIAS
    │   ├── composer.json           # configuración Composer
    │   └── vendor/                 # Paquetes PHP
    │
    └── 🔧 UTILIDADES
        ├── db.php                  # Conexión a BD
        ├── conexion.php            # Conexión legacy
        └── config_stripe.php       # Configuración Stripe
```

---

## 🎮 Flujo de Uso

### 1. Registro e Inicio de Sesión
```
Usuario accede a index.php
    ↓
Se registra o inicia sesión
    ↓
Credenciales validadas
    ↓
Sesión iniciada → Acceso a principal.php
```

### 2. Jugar
```
Dashboard (principal.php)
    ↓
Selecciona juego
    ↓
Indica apuesta
    ↓
Juega (lógica del juego)
    ↓
Resultado (ganancia/pérdida)
    ↓
Saldo actualizado
    ↓
Guardado en historial
```

### 3. Depositar Saldo
```
Botón "Añadir saldo"
    ↓
Selecciona cantidad (crear_sesion_pago.php)
    ↓
Redirige a Stripe Checkout
    ↓
Usuario ingresa tarjeta
    ↓
Stripe procesa pago
    ↓
Webhook actualiza saldo (webhook.php)
    ↓
Confirmación (exito.php)
```

---

## 📋 Requisitos de Sistema

### Servidor
- **Apache 2.4+** o **Nginx 1.14+**
- **PHP 7.4+**
- **MySQL 5.7+** o **MariaDB 10.3+**
- **Composer 2.0+** (para dependencias)

### Cliente
- Navegador moderno (Chrome, Firefox, Safari, Edge)
- Conexión a internet
- JavaScript habilitado

### Claves de API
- **Stripe Public Key** (pk_live_...)
- **Stripe Secret Key** (sk_live_...)
- **Stripe Webhook Secret** (whsec_...)

---

## 🚀 Instalación y Configuración

### Paso 1: Clonar o Descargar
```bash
git clone <repositorio>
cd CasinoTFG-Marc-Javi
```

### Paso 2: Instalar Dependencias
```bash
cd CASINOPRUEBAS/PRINCIPAL
composer install
```

### Paso 3: Configurar BD
1. Crear base de datos `casino_db`
2. Importar esquema: `mysql -u root -p casino_db < casinobd.sql`

### Paso 4: Configurar variables de entorno
Crear archivo `.env`:
```env
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=casino_db

STRIPE_PUBLIC_KEY=pk_live_...
STRIPE_SECRET_KEY=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...

ADMIN_EMAIL=admin@casino.local
```

### Paso 5: Servir la aplicación
```bash
# Con PHP built-in (desarrollo)
php -S localhost:8000 -t CASINOPRUEBAS/PRINCIPAL

# O configurar en Apache/Nginx (producción)
```

### Paso 6: Acceder
Abre en navegador: `http://localhost:8000`

---

## 🔐 Seguridad

### ✅ Implementado
- Hash de contraseñas con `bcrypt` / `password_hash()`
- Validación servidor-lado
- Prepared statements (PDO)
- CSRF tokens en formularios
- Validación de webhooks Stripe

### ⚠️ Importante
- Usar HTTPS en producción (obligatorio con Stripe)
- No exponer secrets en código fuente
- Usar environment variables (.env)
- Validar todas las entradas de usuario
- Implementar rate-limiting en login

---

## 🗄️ Base de Datos

### Tablas Principales

**usuarios**
- id, email, password, nombre, saldo, nivel_vip, fecha_registro

**historial_juegos**
- id, usuario_id, juego, apuesta, ganancia, resultado, fecha

**transacciones**
- id, usuario_id, cantidad, tipo, estado, referencia_stripe, fecha

**sesiones_pago**
- id, usuario_id, session_id, estado, cantidad, fechas

Esquema completo en: [`casinobd.sql`](./CASINOPRUEBAS/PRINCIPAL/casinobd.sql)

---

## 📊 API Endpoints

### Autenticación
```
POST /index.php           → Login/Registro
POST /logout.php          → Cierre de sesión
```

### Datos de Usuario
```
GET  /saldo.php           → Obtener saldo actual (AJAX)
POST /guardar_entrada.php → Guardar resultado de juego
GET  /historial.php       → Ver historial de partidas
```

### Pagos
```
POST /crear_sesion_pago.php → Crear sesión Stripe
GET  /exito.php             → Confirmación de pago
POST /webhook.php           → Webhook de Stripe
```

### Administración
```
GET  /admin/index.php     → Panel de admin
```

---

## 🧪 Testing

### Testing Manual
1. Crear cuenta de prueba
2. Realizar login/logout
3. Jugar cada juego
4. Depositar saldo (usar tarjeta de prueba Stripe)
5. Ver historial y perfil

### Tarjetas de Prueba Stripe
- Éxito: `4242 4242 4242 4242`
- Rechazada: `4000 0000 0000 0002`
- Cualquier fecha futura + CVC: 123

---

## 📖 Documentación Adicional

- 📋 [Wireframes](./WIREFRAMES.md) - Diseño de pantallas
- 🎯 [Resumen Ejecutivo](./RESUMEN_EJECUTIVO.md) - Overview del proyecto
- 🏗️ [Arquitectura](./ARQUITECTURA.md) - Detalles técnicos
- 📋 [TODO List](./TODO.md) - Tareas pendientes

---

## 👥 Autores

- **Marc** - Desarrollador
- **Javier** - Desarrollador

Proyecto Final de Grado (TFG)

---

## 📝 Licencia

Este proyecto es parte del TFG y está sujeto a los términos de la institución educativa.

---

## 🤝 Contribución

Las contribuciones son bienvenidas. Para cambios mayores:

1. Fork el repositorio
2. Crea una rama (`git checkout -b feature/Mejora`)
3. Commit cambios (`git commit -am 'Añade mejora'`)
4. Push a la rama (`git push origin feature/Mejora`)
5. Abre un Pull Request

---

## ⚠️ Avisos Importantes

### Desarrollo
- Esta es una aplicación de demostración educativa
- No usar en producción sin revisión de seguridad
- Las claves de Stripe de prueba están limitadas

### Datos de Usuario
- Los datos personales se recopilan solo para la sesión
- No se comparten datos con terceros (excepto Stripe)
- Ver política de privacidad para más detalles

---

## 🆘 Soporte y Contacto

Para reportar bugs o hacer sugerencias:
- Crear un issue en el repositorio
- Contactar a los desarrolladores directamente
- Consultar la documentación en línea

---

## 📅 Changelog

### v1.0 (Versión Inicial)
- ✅ Implementación de login/registro
- ✅ 4 juegos básicos
- ✅ Sistema de saldos
- ✅ Integración Stripe
- ✅ Panel de administración
- ✅ Historial de partidas

---

**Última actualización:** 14 de mayo de 2024  
**Estado:** ✅ Funcional - Prototipo Educativo

