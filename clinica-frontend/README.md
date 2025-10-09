
# Clínica MV – Frontend (Angular)
<image src="/front_angular_19.png" alt="Imagen de Angular 19">

**SPA** en **Angular** para la gestión una clínica, controlando pacientes, especialistas, citas e historiales.  
Consume la **API REST** de Laravel (Sanctum) y utiliza **Bootstrap 5**, **FullCalendar** y componentes reutilizables (tablas, modales, exportaciones).

## Contenido
- [Requisitos](#requisitos)
- [Arranque rápido](#arranque-rápido)
  - [Modo Docker](#modo-docker)
  - [Modo local (sin Docker)](#modo-local-sin-docker)
- [Configuración](#configuración)
  - [Variables de entorno (Angular)](#variables-de-entorno-angular)
  - [CORS y Sanctum (backend)](#cors-y-sanctum-backend)
  - [Tema de color](#tema-de-color)
- [Arquitectura del Frontend](#arquitectura-del-frontend)
  - [Rutas y guards](#rutas-y-guards)
  - [Servicios clave](#servicios-clave)
  - [Componentes reutilizables](#componentes-reutilizables)
---

## Requisitos
- **Node.js 20+** y **npm** (para modo local).
- Acceso a la **API** (`https://api.clinicamv.lol` en prod) con CORS configurado.
- En Docker, este frontend se sirve por **Nginx** y se enruta vía **Traefik** (`https://app.clinicamv.lol`).

## Arranque rápido
Podemos arrancar el servicio de forma local de dos formas, mediante Contenedores (Docker) o sin contenedores. Vamos a explicar las dos formas:

### Modo Docker
> Recomendado para reproducir el despliegue real.

Usa la misma arquitectura que en producción: el frontend se construye como imagen y se sirve con Nginx. Desde la raíz del proyecto (stack):

```bash
docker compose up -d --build
```
De esta forma se construye la imagen del frontend, arranca Nginx y lo expone internamente.

Con el traefik enrutará `https://app.clinicamv.lol` al contenedor `frontend`.

#### Para verificar que el contenedor esta OK:

```bash
docker compose ps
docker compose logs -f frontend
```
#### Prueba en el navegador:

En producción: ```https://app.clinicamv.lol```
En un **entorno local con Traefik** lo hacemos de la misma forma si el DNS/host apuntan al server.
**Si no se usa Traefik en local**, conectarnos al ````localhost```` por el puerto publicado del frontend. Podemos verlo en el ````docker-compose.yml````.

#### Para reconstruir tras un cambio:
```bash
docker compose build frontend
docker compose up -d
```
#### Para parar el servicio:
```bash
docker compose down
```

>**Importante**: el frontend llama a la API por ``environment.apiBase``. En producción debería ser ``https://api.clinicamv.lol``. Asegúrate de que `**CORS** en el backend permite el origen (ver sección de CORS en el README).

### Modo local (sin Docker)
Útil para desarrollo rápido con HMR.
HMR = recarga instantánea de los módulos modificados, sin reiniciar toda la app.
- **Instalar dependencias**
```bash
# 1) Instalar dependencias
npm install
```
- **Configurar el endpoint de API**
Abre ``src/environments/environment.ts`` y ajusta:
```bash
export const environment = {
  production: false,
  apiBase: 'http://localhost:8000', // o el que uses en backend local
};
```
- **Arrancar el servidor de desarrollo de Angular**
```bash
ng serve --open
```
La app se abre en `http://localhost:4200/`.
HMR recarga la página al guardar cambios.

- **Verificar CORS y sesión**
Si la API está en otra URL (p. ej. ``http://localhost:8000``), en el backend Laravel añade:

  - ``CORS_ALLOWED_ORIGINS=http://localhost:4200``
  - ``SANCTUM_STATEFUL_DOMAINS=localhost:4200``
  - ``SESSION_DOMAIN=localhost``

Reinicia backend si cambias el .env.

---

## Configuración

### Variables de entorno (Angular)
Define el endpoint de la API y ajustes de entorno:

`src/environments/environment.ts` (dev)
```ts
export const environment = {
  production: false,
  apiBase: 'http://localhost:8000', // o https://api.clinicamv.lol si usas túnel/cert
  colorTemaFallback: '#28a745'
};
```

`src/environments/environment.prod.ts`
```ts
export const environment = {
  production: true,
  apiBase: 'https://api.clinicamv.lol',
  colorTemaFallback: '#28a745'
};
```

Asegúrate de que los **servicios HTTP** consumen `environment.apiBase`.

### CORS y Sanctum (backend)
En el `.env` del backend (Laravel):
```
SANCTUM_STATEFUL_DOMAINS=app.clinicamv.lol
SESSION_DOMAIN=.clinicamv.lol
CORS_ALLOWED_ORIGINS=https://app.clinicamv.lol
```
En local, usa los orígenes de tu máquina (p. ej. `http://localhost:4200`).

### Tema de color
El color del sistema se inyecta como variable CSS:

```css
:root {
  --color-tema: #28a745; /* se sobreescribe desde ConfiguracionService */
}
```

Para adaptar Bootstrap “success” a tu tema en la landing:
```css
.bg-success { background-color: var(--color-tema) !important; }
.text-success { color: var(--color-tema) !important; }
.btn-success { background-color: var(--color-tema) !important; border-color: var(--color-tema) !important; }
```

---

## Arquitectura del Frontend

### Rutas y guards
- **AuthGuard** + **RoleGuard** protegen rutas según autenticación y rol (paciente, especialista, admin).
- SPA con módulos/standalone components.
- Redirecciones por defecto:
  - `/especialista` → calendario + tabla de citas del especialista.
  - `/paciente` → calendario + tabla de citas del paciente.

### Servicios clave
- **AuthService**: login/registro/logout, persistencia de sesión, headers con token (Sanctum).
- **UserService**: perfil, listados con paginación/búsqueda/ordenación.
- **ConfiguracionService**: color de tema, flags UI (ej. `Crear_cita_paciente`), etc.
- **ExportadorHistorialService**: exportación PDF/CSV (soporta 1 o N historiales).
- **Utilidades**: `unirse-conferencia.ts`, `mostrar-boton-videollamada.ts`, `utils.ts` (`validarEmail`, `formatearFecha`, `formatearHora`, `sanitizarTexto`, etc.).
- **Videollamadas:** integración con **Jitsi Meet** (función `unirseConferencia`). Se consume la APi de este servicio.

### Componentes reutilizables
- **TablaDatosComponent**: tabla genérica (ordenación, paginación, filtros, templates por columna).
- **CalendarioCitasComponent**: vista mensual (FullCalendar) para paciente y especialista.
- **ModalInfoCita**: agnóstico (`CitaGenerica`), con:
  - Paciente: botón “Cancelar” si `pendiente` y >24h.
  - Especialista: desplegable para cambiar estado (`pendiente` → `realizada/cancelada/ausente`).
- **Modales de CRUD** (usuarios, pacientes, especialistas, citas) con cabecera coloreada vía `ConfiguracionService`.
 ---

 <p align="center"><b>© 2025 Clínica MV | Desarrollado por Sebastián Rodríguez</b></p>