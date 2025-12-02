# Clinica MV - Stack Docker con Traefik, API, Frontend, Queue y MySQL

Encontramos el directorio con el entorno de despliegue en Docker Compose para la aplicación Clinica MV.
Incluye backend *Laravel* frontend **Angular**, **Traefik** como proxy inverso, **My SQL** y workers de cola.

---

## Arquitectura general

### Servicios principales

|Servicio|Descripción|
|--------|------------|
|db|MySQL 8 — Base de datos principal|
|php|Servidor PHP-FPM para Laravel (app y API)|
|api|Nginx de Laravel para servir ``/api`` y documentación Swagger|
|frontend|Angular build estático servido en Nginx|
|clinicamv-queue| Worker de colas Laravel para mails, notificaciones, etc.|
|traefik|stack global|

---

### Redes

```yaml
networks:
    red-clinicamv:
        external: true
```
**red-clinicamv** es una red externa compartida con **Traefik**, permitiendo enrutamientos HTTPS y certificados Let´s Encrypt.

---

### Volúmenes

|Volumen|Uso|
|---|---|
|db_data|Datos persistentes de MySQL|
|laravel_storage|Archivos de Laravel (``storage/app``, logs, carga de archivos).

---

### Despliegue

1. **Primer deploy con migraciones y seeders opcionales**

```bash
docker compose up -d
```

Para ejecutar migraciones y seeders en el arranque
- Editar en ``php``:

```yaml
MIGRATE_ON_START: "true"
SEED_ON_START: "true"
```
Después, se vuelve a poner en ``false`` para no repetirlo en cada reinicio.

2. ***Actualizar imágenes desde GHCR***

```bash
docker compose pull
docker compose up -d
```
3. **Apagar y levantar**

```bash
docker compose down
docker compose up -d
```
---
### Dominios usados

|Dominio|Servicio|
|---|---|
|app.clinicamv.lol|Frontend Angular|
|api.clinicamv.lol|API REST Laravel|
|docs.clinicamv.lol|Swagger UI (L5-Swagger)|
|clinicamv.lol – www.clinicamv.lol|Redirección automática a app.clinicamv.lol|

---
### Etiquetas Traefik
***API***
- ``traefik.http.routers.clinicamv-api.rule=Host(\api.clinicamv.lol)``
- Certificados automáticos: ``tls.certresolver=lets``
- Middleware: ``secure-headers@docker``

***Docs Swagger***
- ``traefik.http.routers.clinicamv-docs.rule=Host(\docs.clinicamv.lol)``
- Usa el **mismo servicio** ``clinicamv-api-svc``

***Frontend***
- ``traefik.http.routers.front.rule=Host(\app.clinicamv.lol)``
- Redirección automática desde ``clinicamv.lol`` -> ``app.clinicamv.lol``
---

### Healtchecks
Cada servicio tiene su watchdog:

```yaml
healthcheck:
  test: ["CMD-SHELL", "wget -qO- http://127.0.0.1/_health || exit 1"]
```
Si algo falla respondera **ERROR** en ``docker ps``.

---

### Queue
El servicio c``linicamv-queue`` ejecuta continuamente:
```bash
php artisan queue:work --queue=default,mail,notifications
```

**Se usa para:**
- Emails
- Notificaciones
- Tareas asíncronas

***Importante***: comparte el volumen ``laravel_storage`` con ``php`` para logs y archivos pendientes.

---

### Comandos útiles

#### Ver estado
```bash
docker compose ps
docker compose logs -f
```

#### Ver solo los logs de la API

```bash
docker compose logs -f api
```

#### Acceder al contenedor PHP

```bash
docker exec -it clinicamv-php-1 sh
```

#### Ejecutar migraciones manuales

```bash
php artisan migrate --force
php artisan db:seed --force
```

---

## Backup de base de datos

```bash
docker exec -it clinicamv-db-1 mysqldump -u root -proot clinica > backup.sql
```
Restaurar:
```bash
docker exec -i clinicamv-db-1 mysql -u root -proot clinica < backup.sql
```
---

### Variables sensibles
En `` server/clinicamv/secrets/clinica.en``
Claves críticas:
- APP_KEY
- DB_PASSWORD
- MAIL_*
- BREVO_API_KEY *Servicio de notificación por mail de terceros*
- SANCTUM_STATEFUL_DOMAINS
> Nunca subir *.env a Git

---

### Mantenimiento
#### Eliminar contenedores y liberar espacio

```bash
docker system prune -a
docker volume prune
```
#### Eliminar imágenes huérfanas
```bash
docker image ls
docker image rm <id>
```