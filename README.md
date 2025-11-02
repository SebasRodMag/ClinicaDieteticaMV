<p align="center">
  <img src="/assets/favicon.png" alt="Imagen de Laravel 12" width="300">
</p>

# Clínica MV

**Clínica MV** es una plataforma web integral diseñada para la **gestión de consultas y seguimiento** entre especialistas y pacientes. Nace con el objetivo de **optimizar la atención clinica**, centralizar la información y ofrecer un entorno digital seguro y eficiente tanto para profesionales de la salud como para sus pacientes.

---

## Propósito y necesidades que cubre

El sistema responde a una necesidad cada vez más evidente en el ámbito sanitario: **digitalizar la gestión de citas, historiales médicos y documentación clínica**, facilitando además la **comunicación telemática** mediante videoconferencias seguras.

Entre sus objetivos principales se encuentran:

- Simplificar la **gestión administrativa** de la clínica.
- Ofrecer una **atención personalizada** y continua a cada paciente.
- Centralizar en un único espacio la **información médica, documentos y citas.**
- Reducir el uso de papel y mejorar la **trazabilidad y seguridad** de los datos clínicos.

---

## A quién va dirigido

La aplicación está orientada principalmente a:

- **Especialistas en de la salud**, que requieren una herramienta eficiente para gestionar sus pacientes, citas, historiales y documentos.
- **Administradores**, encargados de la administración general de clínicas, la gestión de usuarios y personal especialista.

---

## Funcionamiento general

El sistema se estructura en torno a tres áreas principales:

### 1. Gestión de usuarios y roles

Los usuarios se registran por una de las dos vias posibles, de forma personal por uno de los especialistas donde se le asigna el rol de usuario o paciente según el criterio del especialista, o de forma telemática desde la parte publica del sistema, donde se le asigna el rol de paciente. Cada rol dispone de un área privada con funcionalidades específicas.

### 2. Gestión de citas

Permite programar, modificar o cancelar citas tanto presenciales como telemáticas. Las videollamadas se realizan mediante **integración con Jitsi Meet**, garantizando comunicación segura y sin necesidad de instalar software adicional.

### 3. Historiales y documentos clínicos

Los especialistas pueden registrar evoluciones médicas, compartir documentos en PDF o imagen con sus pacientes, y mantener un historial completo de cada caso.  
Los pacientes, por su parte, pueden subir archivos personales (como analíticas o informes) y acceder a los documentos compartidos por su especialista.

---

## Arquitectura y tecnologías utilizadas

<image src="/assets/front_laravel_12.png" alt="Imagen de Laravel 12">

### Backend (API REST con Laravel)

El backend está desarrollado en **PHP utilizando el framework Laravel 12**, bajo una arquitectura **API RESTful**.  
Entre sus principales características técnicas destacan:

- **Autenticación segura** mediante Laravel Sanctum.  
- **Gestión de roles y permisos** con Spatie Laravel Permissions.  
- **Eloquent ORM** para el manejo de la base de datos MySQL.  
- **Migraciones y Seeders** para la creación automatizada de estructuras y datos base.  
- **Controladores REST** con buenas prácticas: validaciones, logs de auditoría y respuestas normalizadas en formato JSON.  
- **SoftDeletes y trazabilidad de acciones** para garantizar integridad y reversibilidad de datos.  
- **Notificaciones por correo electrónico** integradas con **Brevo (Sendinblue)**.
- Sistema de citas unificado (consulta + seguimiento)
- Subida y gestión de documentos por rol
- Historial médico con trazabilidad de entradas
- Registro de acciones (logs) con fines de auditoría

El backend actúa como el **núcleo lógico del sistema**, procesando peticiones, validando datos y ofreciendo servicios a través de endpoints accesibles solo mediante tokens autenticados.


#### Tecnologías utilizadas

- **Laravel 12**
- **PHP 8.2+**
- **SQLite / MySQL**
- **Spatie Laravel-Permission** (gestión de roles y permisos)
- **Laravel Sanctum** (autenticación vía token)
- **Eloquent** como ORM
- **Seeders y Factories** para datos de prueba
---

### Frontend (Interfaz web con Angular)

<image src="/assets/front_angular_19.png" alt="Imagen de Angular 19">

El frontend está desarrollado en **Angular 18 (standalone components)** y ofrece una experiencia fluida, moderna y adaptable a cualquier dispositivo.  
Entre sus aspectos técnicos más relevantes:

- **Interfaz modular y reutilizable**, con componentes como tablas dinámicas, modales, formularios y calendario interactivo.  
- **Consumo de API REST** para comunicación directa con el backend.  
- **Protección de rutas** mediante guards y roles definidos.  
- **Diseño responsivo** con **Bootstrap 5** y personalización de colores a partir del valor configurado en la base de datos (`color_tema`).  
- **Integración de FullCalendar** para la visualización de citas.  
- **Exportación de datos** (PDF y CSV) desde componentes genéricos.  
- **Animaciones suaves** mediante GSAP para una presentación visual atractiva.

El frontend funciona como una **SPA (Single Page Application)**, donde cada usuario accede a un entorno personalizado según su rol, manteniendo siempre la seguridad y fluidez en la navegación.

---


## Seguridad y buenas prácticas

El sistema ha sido diseñado con un enfoque prioritario en la **seguridad, mantenibilidad y usabilidad**:

- Tokens de autenticación y control de sesiones con **Sanctum**.  
- Validaciones en servidor y cliente para prevenir errores o accesos indebidos.  
- Logs de auditoría automáticos para registrar las acciones relevantes.  
- Control de acceso a documentos y citas según el rol autenticado.

---

## Arquitectura del servicio

El sistema **Clínica Dietética MV** se despliega mediante una **infraestructura basada en contenedores Docker**, organizada en múltiples servicios interconectados que garantizan seguridad, escalabilidad y mantenimiento sencillo.
La comunicación entre los contenedores se gestiona mediante una red interna y el enrutamiento externo está a cargo de **Traefik**, actuando como proxy inverso.

#### Traefik (Reverse Proxy)

**Traefik** es el punto de entrada principal a la infraestructura.
Gestiona el **enrutamiento del tráfico HTTP/HTTPS**, que escucha en los puertos 80 (HTTP) y 443 (HTTPS) y direcciona hacia los diferentes servicios según el dominio solicitado, y además **automatiza la emisión y renovación de certificados TLS** mediante **Let’s Encrypt**, garantizando conexiones seguras.

- Rol: Reverse proxy y terminador TLS

- Dominios gestionados:

    - ``https://app.clinicamv.lol`` : Frontend

    - ``https://api.clinicamv.lol`` : API REST

#### Frontend (Nginx + Angular)

El **frontend** está desarrollado en **Angular** y se distribuye como una **Single Page Application (SPA)** servida mediante **Nginx**.
Actúa como la interfaz de usuario, comunicándose exclusivamente con la API mediante peticiones HTTPS autenticadas con **Laravel Sanctum**.
El intercambio está protegido por **CORS** y los dominios permitidos se definen en el archivo de entorno (``.env`` del backend).

- Framework: Angular 18
- Servidor web: Nginx (contenedor ligero y eficiente)
- Dominio: `https://app.clinicamv.lol`
- Comunicación: API REST (token con Laravel Sanctum)

#### API (Nginx + Laravel)

La **API REST** se sirve también mediante **Nginx**, que actúa como proxy interno hacia un contenedor **PHP-FPM** encargado de ejecutar el framework **Laravel**.
Aquí reside toda la **lógica de negocio**, cuando recibe peticiones que requieren lógica de aplicación (por ejemplo, autenticación, gestión de citas o historiales), las reenvía al servicio **php** en el puerto interno **9000**, donde **PHP-FPM** ejecuta el framework Laravel.

- Framework: Laravel 12
- Servidor de aplicaciones: PHP-FPM
- Funciones principales:
    - Validación y autenticación de usuarios (Sanctum)
    - Gestión de roles y permisos (Spatie)
    - Manejo de datos clínicos y documentos
    - Comunicación con colas y base de datos
- Dominio: `https://api.clinicamv.lol`

#### PHP (PHP-FPM – Worker Principal)
Este servicio ejecuta los procesos PHP del backend, incluyendo **Artisan, migraciones, seeders, y tareas síncronas**.
Laravel accede directamente a la **base de datos MySQL** a través de la red interna Docker, utilizando credenciales definidas en variables de entorno.
Cuando se generan procesos en segundo plano (como el envío de correos o notificaciones), estos se publican en la cola de trabajos, que son procesados de manera asíncrona por el servicio **queue**.

#### Queue (Laravel Worker)

El servicio de **queue** ejecuta en segundo plano los procesos diferidos mediante **Laravel Queue.**
Esto permite gestionar tareas asincrónicas como:
- Envío de correos electrónicos (integración con Brevo).
- Procesamiento de notificaciones.
- Limpieza o generación periódica de datos.
Comando principal:
```bash
php artisan queue:work
```

#### Base de Datos (MySQL 8)
<image src="/assets/front-mysql.jpg" alt="Imagen de MySQL">

La persistencia de datos se gestiona mediante **MySQL 8**, almacenando información de usuarios, citas, historiales, logs y configuraciones del sistema.
El acceso está restringido a los contenedores de Laravel y phpMyAdmin dentro de la red interna.

- Motor: MySQL 8.0
- Persistencia: Volumen Docker dedicado (``/var/lib/mysql``)
- Seguridad: Usuario, contraseña y host configurados mediante variables de entorno (``.env``)

#### Esquema simplificado del flujo
```bash
[ Usuario ]
     │
     ▼
 [ Traefik ]
     ├──→ (app.clinicamv.lol) → [ Frontend (Angular/Nginx) ]
     │           │
     │           ▼
     │      [ API (Nginx → PHP-FPM/Laravel) ]
     │           │
     │           ├──→ [ Queue Worker (tareas asíncronas) ]
     │           └──→ [ MySQL (base de datos) ]
     ▼
 [ Certificados Let's Encrypt ]

```

#### Seguridad y comunicación
- Todo el tráfico se cifra mediante **TLS** con certificados emitidos automáticamente por **Let’s Encrypt (Traefik).**

- La comunicación entre contenedores se realiza dentro de una red **Docker interna**, inaccesible desde el exterior.

- Solo **Traefik** expone puertos públicos (80/443), garantizando el aislamiento de los servicios internos.

- El **CORS** y **Sanctum** controlan la autenticación y las solicitudes cruzadas entre el frontend y el backend.

---

#### phpMyAdmin
<image src="/assets/front_phpmyadmin.png" alt="Imagen de php myadmin">

Herramienta de administración visual de la base de datos, **accesible únicamente desde entorno local o desarrollo.**
Facilita la inspección y depuración de datos durante el proceso de desarrollo.

Acceso: ``http://localhost:8082``

Uso: Consultas SQL, revisión de migraciones y testing manual.

---

### Servicios del stack Docker
<image src="/assets/front_docker.png" alt="Imagen de docker">

La siguiente tabla describe cada contenedor que forma parte del despliegue de **Clínica Dietética MV**, incluyendo su imagen, propósito, puertos y notas relevantes.

<table>
  <thead>
    <tr>
      <th>Servicio (nombre)</th>
      <th>Imagen</th>
      <th>Propósito</th>
      <th>Puertos</th>
      <th>Notas</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><b>traefik (traefik-traefik-1)</b></td>
      <td><code>traefik:v3.1</code></td>
      <td>Reverse proxy y TLS (enruta dominios a los servicios internos).</td>
      <td><b>80, 443 (públicos)</b></td>
      <td>Lee labels de los servicios; enruta por ejemplo <code>https://api.clinicamv.lol</code> → api y <code>https://app.clinicamv.lol</code> → frontend.</td>
    </tr>
    <tr>
      <td><b>api (clinicamv-api-1)</b></td>
      <td><code>ghcr.io/sebasrodmag/clinicamv-backend:api-main</code></td>
      <td>Nginx que sirve la API y actúa como proxy a PHP-FPM (Laravel).</td>
      <td>Sin puertos públicos (solo vía Traefik)</td>
      <td>Healthcheck en <code>/_health</code>. Maneja CORS y cache estática; pasa PHP a <code>php:9000</code>.</td>
    </tr>
    <tr>
      <td><b>php (clinicamv-php-1)</b></td>
      <td><code>ghcr.io/sebasrodmag/clinicamv-backend:php-main</code></td>
      <td>PHP-FPM con Laravel (procesa app, artisan y composer).</td>
      <td>9000/tcp (interno)</td>
      <td>Ejecuta migraciones, seeders y comandos Artisan. Monta <code>storage/</code> y <code>vendor/</code> si aplica.</td>
    </tr>
    <tr>
      <td><b>queue (clinicamv-queue-1)</b></td>
      <td><code>ghcr.io/sebasrodmag/clinicamv-backend:php-main</code></td>
      <td>Worker de colas (<code>queue:work</code>) para notificaciones y correos.</td>
      <td>—</td>
      <td>Ejecuta <code>php artisan queue:work --queue=mail,default</code>.<br>Debe reiniciarse tras cambios en código o variables de entorno.</td>
    </tr>
    <tr>
      <td><b>frontend (clinicamv-frontend-1)</b></td>
      <td><code>ghcr.io/sebasrodmag/clinicamv-frontend:main</code></td>
      <td>Nginx sirviendo la SPA de Angular.</td>
      <td>Sin puertos públicos (solo vía Traefik)</td>
      <td>Build empaquetado y optimizado. Apunta a <code>apiBase</code> (por ejemplo, <code>https://api.clinicamv.lol</code>).</td>
    </tr>
    <tr>
      <td><b>db (clinicamv-db-1)</b></td>
      <td><code>mysql:8.0</code></td>
      <td>Base de datos MySQL para persistencia.</td>
      <td>3306 (interno)</td>
      <td>Persistencia mediante volumen Docker. No expuesto públicamente.</td>
    </tr>
    <tr>
      <td><b>phpmyadmin (clinicamv-phpmyadmin-1)</b></td>
      <td><code>phpmyadmin:5.2</code></td>
      <td>Interfaz web para administración de la base de datos.</td>
      <td><code>127.0.0.1:8082 → 80</code></td>
      <td>Solo accesible desde el host local en <code>http://localhost:8082</code>. Útil para depuración.</td>
    </tr>
  </tbody>
</table>

---

#### Instalación (local)
```bash
# 1) Clonar el repo
git clone https://github.com/tu-usuario/clinica-dietetica-api.git
cd clinica-dietetica-api

# 2) Dependencias PHP
composer install

# 3) Variables de entorno
cp .env.example .env

# 4) Generar APP_KEY
php artisan key:generate

# 5) Configurar DB en .env (DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD)

# 6) Migrar y seedear
php artisan migrate --seed

# 7) Enlazar storage
php artisan storage:link

# 8) (Opcional) limpiar/cachar config y rutas
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache

# 9) Levantar servidor local
php artisan serve

```
---

<p align="center"><b>© 2025 Clínica MV | Desarrollado por Sebastián Rodríguez</b></p>

