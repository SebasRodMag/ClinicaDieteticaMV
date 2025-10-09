<p align="center">
  <img src="/workflow.png" alt="Imagen de github workflow" width="600">
</p>

# CI/CD – Workflows de GitHub Actions

Este documento describe la configuración de **CI/CD** para el proyecto **Clínica Dietética MV**, incluyendo la integración continua, construcción de imágenes Docker y despliegue automatizado en el servidor de producción.

---

## Mapa de workflows

| Workflow | Archivo | Propósito | Disparadores |
|-----------|----------|------------|---------------|
| **Backend CI** | `.github/workflows/backend-ci.yml` | Ejecuta tests y validaciones del backend (Laravel). | `push`, `pull_request` |
| **Frontend CI** | `.github/workflows/frontend-ci.yml` | Linter + build de Angular (sin publicar). | `push`, `pull_request` |
| **Build & Push Images** | `.github/workflows/build-images.yml` | Construye y publica imágenes Docker en GHCR (backend API, PHP y frontend). | `push` a `main`, `workflow_dispatch` |
| **Deploy Production** | `.github/workflows/deploy.yml` | Despliega la versión más reciente al servidor en Hetzner Cloud. | `workflow_dispatch`, `release` |

---

## Repositorios e imágenes

Las imágenes Docker se publican en **GitHub Container Registry (GHCR)**:

| Servicio | Imagen publicada | Propósito |
|-----------|------------------|------------|
| `clinicamv-api` | `ghcr.io/SebasRodMag/clinicamv-backend:api-main` | Nginx que sirve la API y proxy a PHP-FPM. |
| `clinicamv-php` | `ghcr.io/SebasRodMag/clinicamv-backend:php-main` | PHP-FPM con Laravel (migraciones, Artisan, colas). |
| `clinicamv-queue` | `ghcr.io/SebasRodMag/clinicamv-backend:php-main` | Worker de colas (Laravel queue:work). |
| `clinicamv-frontend` | `ghcr.io/SebasRodMag/clinicamv-frontend:main` | Nginx sirviendo la SPA Angular. |

---

## Flujos principales

### 1. Backend CI
Verifica el backend antes de hacer merge o publicar imágenes.

**Archivo:** `.github/workflows/backend-ci.yml`

```yaml
name: Backend CI
on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: mbstring, pdo, mysql, bcmath
      - uses: actions/cache@v4
        with:
          path: ~/.composer/cache
          key: composer-${{ hashFiles('**/composer.lock') }}
      - run: composer install --no-interaction --prefer-dist
      - run: php artisan test --env=testing
```
---

### 2. Frontend CI
Verifica que el frontend Angular compile correctamente y cumpla las reglas de lint.
**Archivo**: ``.github/workflows/frontend-ci.yml``
```yaml
name: Frontend CI
on: [push, pull_request]

jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with:
          node-version: '20'
          cache: 'npm'
      - run: npm ci
      - run: npm run lint --if-present
      - run: npm run build
```
----

### 3. Build & Push de Imágenes

Construye las imágenes del stack y las publica en **GHCR.io** bajo tu espacio.

**Archivo**: ``.github/workflows/build-images.yml``

```yaml
name: Build & Push Images
on:
  push:
    branches: [main]
  workflow_dispatch:

jobs:
  build:
    permissions:
      contents: read
      packages: write
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Login en GHCR
        run: echo "${{ secrets.GHCR_TOKEN }}" | docker login ghcr.io -u ${{ github.actor }} --password-stdin

      - name: Build API
        run: docker build -t ghcr.io/SebasRodMag/clinicamv-backend:api-main -f docker/api/Dockerfile .

      - name: Build PHP-FPM
        run: docker build -t ghcr.io/SebasRodMag/clinicamv-backend:php-main -f docker/php/Dockerfile .

      - name: Build Frontend
        run: docker build -t ghcr.io/SebasRodMag/clinicamv-frontend:main -f docker/frontend/Dockerfile .

      - name: Push imágenes
        run: |
          docker push ghcr.io/SebasRodMag/clinicamv-backend:api-main
          docker push ghcr.io/SebasRodMag/clinicamv-backend:php-main
          docker push ghcr.io/SebasRodMag/clinicamv-frontend:main
```
---

### 4. Despliegue en Producción

Despliega la última versión en el servidor remoto (Hetzner Cloud) mediante SSH, actualizando los contenedores y limpiando caché.
**Archivo**: ``.github/workflows/deploy.yml``
```yaml
name: Deploy Production
on:
  workflow_dispatch:
  release:
    types: [published]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - name: Desplegar en servidor remoto
        run: |
          ssh -i ${{ secrets.SERVER_SSH_KEY }} ${{ secrets.SERVER_USER }}@${{ secrets.SERVER_HOST }} '
            cd /opt/ClinicaDieteticaMV/server/clinicamv && \
            docker compose pull && \
            docker compose up -d && \
            docker system prune -f
          '

```
---

## Secrets y variables de entorno
Configura en **Settings** → **Secrets and variables** → **Actions**:

<table>
    <tr>
        <th>Variable</th>
        <th>Uso</th>
    </tr>
    <tr>
        <td>GHCR_TOKEN</td>
        <td>Token con permisos para packages:write (publicar imágenes).</td>
    </tr>
    <tr>
        <td>SERVER_SSH_KEY</td>
        <td>Clave privada SSH para conexión al servidor.</td>
    </tr>
    <tr>
        <td>SERVER_USER</td>
        <td>Usuario remoto (por ejemplo, root).</td>
    </tr>
    <tr>
        <td>SERVER_HOST</td>
        <td>IP o dominio del servidor (ej. 37.27.80.228).</td>
    </tr>
    <tr>
        <td>BREVO_API_KEY</td>
        <td>API Key para el envío de correos desde Laravel.</td>
    </tr>
    <tr>
        <td>ENV_PRODUCTION</td>
        <td>Variables de entorno de producción.</td>
    </tr>
</table>

---

## Buenas prácticas aplicadas

- Versionar Actions → ``actions/checkout@v4``, ``setup-php@v2``, etc.

- Permisos mínimos → ``permissions``, ``contents``, ``read``, ``packages``: ``write`` solo donde sea necesario.

- Caché de dependencias Composer/npm para acelerar builds.

- Uso de ``workflow_dispatch`` para lanzamientos manuales controlados.

- ``concurrency`` en deploys para evitar ejecuciones solapadas:
```yaml
concurrency:
  group: deploy-production
  cancel-in-progress: true
```
---
<p align="center"> <b>© 2025 Clínica MV | Desarrollado por Sebastián Rodríguez</b> </p>