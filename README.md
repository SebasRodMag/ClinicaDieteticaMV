<image src="/front_laravel_12.png" alt="Imagen de Laravel 12">

# Clínica Dietética – API REST Laravel 12

Bienvenido a la API REST para la gestión de una clínica dietética, desarrollada con **Laravel 12**. Este proyecto forma parte de un trabajo académico y está diseñado para manejar de forma eficiente la administración de usuarios, citas médicas, historiales clínicos y documentos compartidos, todo a través de una arquitectura limpia y segura.

## Características principales

- Autenticación con **Laravel Sanctum**
- Gestión de usuarios con distintos roles:
  - **Administrador**
  - **Especialista**
  - **Paciente**
  - **Usuario** (rol provisional)
- Sistema de citas unificado (consulta + seguimiento)
- Subida y gestión de documentos por rol
- Historial médico con trazabilidad de entradas
- Registro de acciones (logs) con fines de auditoría
- API RESTful clara y estructurada
- Uso de **SoftDeletes** en entidades sensibles
- Validación robusta de datos y manejo de errores

## Tecnologías utilizadas

- **Laravel 12**
- **PHP 8.2+**
- **SQLite / MySQL**
- **Spatie Laravel-Permission** (gestión de roles y permisos)
- **Laravel Sanctum** (autenticación vía token)
- **Eloquent** como ORM
- **Seeders y Factories** para datos de prueba

## ⚙️ Instalación

```bash
git clone https://github.com/tu-usuario/clinica-dietetica-api.git
cd clinica-dietetica-api
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve