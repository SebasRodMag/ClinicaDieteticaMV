<p align="center">
  <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="380" alt="Laravel Logo">
</p>

<h1 align="center">Clínica Dietética MV – Backend (Laravel)</h1>

<p align="center">
  <a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-12.x-FF2D20?style=flat-square&logo=laravel&logoColor=white" alt="Laravel"></a>
  <a href="https://www.php.net/"><img src="https://img.shields.io/badge/PHP-8.3-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP"></a>
  <a href="https://github.com/SebasRodMag/ClinicaDieteticaMV"><img src="https://img.shields.io/badge/Repo-GitHub-181717?style=flat-square&logo=github" alt="GitHub Repo"></a>
  <a href="https://opensource.org/licenses/MIT"><img src="https://img.shields.io/badge/License-MIT-blue?style=flat-square" alt="MIT License"></a>
</p>

---

## Descripción general

**Clínica MV – Backend** es una **API RESTful** desarrollada con **Laravel 12** y **PHP 8.3**, para la gestión una clínica, controlando pacientes, especialistas, citas, historiales clínicos y documentos compartidos.  
Forma parte del ecosistema **Clínica MV**, compuesto por:
- Frontend Angular SPA → [clinica-frontend](../clinica-frontend)
- Backend API REST (este proyecto)
- Infraestructura Docker + Traefik (reverse proxy)

---

## Características principales

- **Laravel Sanctum** → Autenticación mediante tokens seguros (SPA / API).  
- **Spatie Laravel Permission** → Gestión avanzada de roles y permisos (Administrador, Especialista, Paciente, Usuario).  
- **Controladores RESTful** → Estructurados con buenas prácticas (un único return, validación, logs).  
- **Sistema de logs de auditoría** → Registro automático de acciones de usuario.  
- **Gestión de documentos** → Subida, descarga y control de acceso por rol.  
- **SoftDeletes** → Eliminaciones lógicas en la mayoría de entidades.  
- **Colas de trabajo (Queue)** → Notificaciones y correos electrónicos asíncronos (Brevo/Sendinblue).  
- **Integración con Jitsi Meet** → Videoconsultas entre pacientes y especialistas.  
- **Internacionalización y configuración dinámica** → Idioma “es” por defecto y variables de entorno centralizadas.

---

## Estructura del proyecto
````bash
clinica-backend/
│
├── app/
│ ├── Http/Controllers/ # Controladores REST
│ ├── Models/ # Modelos Eloquent
│ ├── Traits/Loggable.php # Trait personalizado de logs
│ └── ...
├── database/
│ ├── migrations/ # Migraciones de tablas
│ ├── seeders/ # Datos iniciales (roles, usuarios, etc.)
│ └── factories/ # Generación de datos de prueba
├── routes/
│ ├── api.php # Rutas principales de la API
│ └── web.php
├── storage/
│ └── documentos/ # Archivos subidos por usuarios
└── docker/ # Configuración Docker (php, nginx, etc.)
````

---

## Arranque rápido
Podemos arrancar el servicio en local de dos formas, desde contenedores con Docker o sin contenedores.

### Con Docker (recomendado)

- **Clonar el repositorio:**
```bash
git clone https://github.com/tu-usuario/clinica-dietetica-api.git
cd clinica-dietetica-api
```
- **Crear entorno**
```bash
cp .env.example .env
```
Ajustar variables
```bash
APP_NAME="Clinica Dietética MV"
APP_ENV=local
APP_KEY=
APP_URL=https://api.clinicamv.lol

DB_HOST=db
DB_DATABASE=clinica
DB_USERNAME=clinica_user
DB_PASSWORD=clinica_pass

```
- **Levantar el stack**
```bash
docker compose up -d --build
```
- **Inicializar la app:**
```bash
docker compose exec php php artisan key:generate
docker compose exec php php artisan migrate --seed
docker compose exec php php artisan storage:link
```

- **Limpiar cache:**
```bash
docker compose exec php php artisan config:clear
docker compose exec php php artisan route:clear
docker compose exec php php artisan view:clear
```
> Traefik enruta automáticamente ``https://api.clinicamv.lol`` hacia este backend.

### En local (sin Docker)

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```
>Servirá la API en ``http://127.0.0.1:8000``.

## Roles y seguridad
<table>
    <tr>
        <th>Rol</th>
        <th>Accesos principales</th>
    </tr>
    <tr>
        <td>Administrador</td>
        <td>CRUD de usuarios, configuración general, estadísticas</td>
    </tr>
    <tr>
        <td>Especialista</td>
        <td>Gestión de pacientes, citas, historiales y documentos</td>
    </tr>
    <tr>
        <td>Paciente</td>
        <td>Área personal, descarga/subida de documentos, citas</td>
    </tr>
    <tr>
        <td>Usuario (pendiente de consulta)</td>
        <td>Registro básico, sin acceso a historial ni citas</td>
    </tr>
</table>

Autenticación: **Laravel Sanctum**
Permisos: **Spatie Laravel Permissions**

## Migraciones y seeders
Migraciones automáticas para las principales tablas
- ``users``, ``roles``, ``permissions``
- ``pacientes``, ``especialistas``
- ``citas``, ``historiales``, ``documentos``, ``logs``
Ejecutar:
```bash
php artisan migrate --seed
```
Seeder por defecto:

- Crea roles base (``admin``, ``especialista``, ``paciente``, `usuario`)
- Usuario administrador inicial
- Datos de configuración básica

## Endpoints destacados
<table>
    <tr>
        <th>Método</th>
        <th>Ruta</th>
        <th>Descripción</th>
    </tr>
    <tr>
        <td>POST</td>
        <td>/api/auth/login</td>
        <td>Autenticación con Sanctum</td>
    </tr>
    <tr>
        <td>GET</td>
        <td>/api/users</td>
        <td>Listado de usuarios (según rol)</td>
    </tr>
    <tr>
        <td>POST</td>
        <td>/api/citas</td>
        <td>Crear nueva cita</td>
    </tr>
    <tr>
        <td>PUT</td>
        <td>/api/citas/{id}</td>
        <td>Actualizar estado de cita</td>
    </tr>
    <tr>
        <td>GET</td>
        <td>/api/documentos/{id}/descargar</td>
        <td>Descargar documento si autorizado</td>
    </tr>
    <tr>
        <td>POST</td>
        <td>/api/documentos/subir</td>
        <td>Subida de documentos (PDF, imágenes)</td>
    </tr>
</table>

>Todas las rutas protegidas requieren token de Sanctum y rol autorizado.

## Buenas prácticas aplicadas

- Código documentado con **PHPDoc**.
- Un único ``return`` por método.
- Validaciones con ``$request->validate()`` o FormRequest.
- **Trait** ``Loggable`` para registrar acciones.
- **SoftDeletes** en todas las tablas excepto ``documentos``.
- Auditoría en tabla ``logs``.

---

## Notificaciones y colas

Sistema de colas (Laravel Queue) para tareas asíncronas:

- Envío de correos con **Brevo (Sendinblue)**.
- Procesamiento de notificaciones internas.

Servicio “queue” ejecuta:
```bash
php artisan queue:work --queue=mail,default
```
---

## Infraestructura y dependencias
<table>
    <tr>
        <th>Componente</th>
        <th>Versión</th>
        <th>Descripción</th>
    </tr>
    <tr>
        <td>Laravel</td>
        <td>12.0</td>
        <td>Framework backend</td>
    </tr>
    <tr>
        <td>PHP</td>
        <td>8.2</td>
        <td>Lenguaje base</td>
    </tr>
    <tr>
        <td>MySQL</td>
        <td>8.0</td>
        <td>Base de datos relacional</td>
    </tr>
    <tr>
        <td>Composer</td>
        <td>2.x</td>
        <td>Gestor de dependencias</td>
    </tr>
    <tr>
        <td>Nginx</td>
        <td>1.25</td>
        <td>Servidor web/proxy PHP-FPM</td>
    </tr>
    <tr>
        <td>Traefik</td>
        <td>3.1</td>
        <td>Proxy inverso y TLS</td>
    </tr>
    <tr>
        <td>Brevo</td>
        <td>API</td>
        <td>Notificaciones por correo</td>
    </tr>
    <tr>
        <td>Jitsi Meet</td>
        <td>SDK</td>
        <td>Videollamadas integradas</td>
    </tr>
</table>

---

## Licencia

Este proyecto se distribuye bajo la licencia **MIT** (Heredad de Laravel)

---

<p align="center"><b>© 2025 Clínica MV | Desarrollado por Sebastián Rodríguez</b></p>
