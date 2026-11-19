# HR Media Score

Plataforma de gestión de torneos de baloncesto desarrollada por **HR Media**.
Primera organización: **Club Gustavo Behall** (Puerto Plata, República Dominicana).

Portal público (torneos, equipos, jugadores, partidos, posiciones) + panel administrativo
multi-organización con roles, auditoría y reglamentos configurables por torneo.

## Tecnología

| Capa | Versión verificada |
|---|---|
| PHP | 8.3 |
| Laravel | 12.x |
| MySQL | 8.0 |
| Livewire (incluye Alpine.js) | 3.x |
| Tailwind CSS (vía Vite) | 4.x |
| Spatie Laravel Permission | 6.x |
| Pest | 3.x |

> Alpine.js se usa a través de Livewire 3, que ya lo incluye. Importarlo por separado crea dos instancias en conflicto.

## Instalación local

Requisitos: PHP 8.3 con `pdo_mysql`, `gd` (con WebP), `intl`, `mbstring`; Composer 2; Node 20+; MySQL 8.

```bash
git clone <repositorio> hr-media-score && cd hr-media-score
composer install
npm install
cp .env.example .env
php artisan key:generate

# Crear base de datos y usuario (ajuste la contraseña)
mysql -uroot -e "CREATE DATABASE hr_media_score CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  CREATE DATABASE hr_media_score_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  CREATE USER 'hrms'@'localhost' IDENTIFIED BY 'cambie-esto';
  GRANT ALL ON hr_media_score.* TO 'hrms'@'localhost';
  GRANT ALL ON hr_media_score_test.* TO 'hrms'@'localhost';"
# Edite DB_PASSWORD en .env

php artisan migrate --seed      # roles, superadmin, Club Gustavo Behall y XIV torneo
php artisan storage:link
npm run build                   # o `npm run dev` durante el desarrollo
php artisan serve               # http://localhost:8000
```

El seeder crea el superadministrador con `ADMIN_EMAIL` / `ADMIN_PASSWORD` del `.env`.
Si `ADMIN_PASSWORD` está vacío, genera una contraseña aleatoria y **la muestra una sola vez** en la consola.

Acceso al panel: `/acceso`. Recuperación de contraseña: `/recuperar-contrasena` (requiere configurar `MAIL_*`).

## Pruebas

```bash
php artisan test          # usa la base MySQL hr_media_score_test (ver phpunit.xml)
vendor/bin/pint           # estilo de código
```

Las pruebas corren contra MySQL (no SQLite) para validar las restricciones reales de integridad
(claves únicas, foráneas y `CHECK`).

## Arquitectura

Monolito modular Laravel. Ver [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md).

```
app/
├── Actions/            Casos de uso transaccionales (inscribir equipo, asignar jugador…)
├── Enums/              Estados, posiciones, roles, tipos de evento (con etiquetas en español)
├── Http/Controllers/   Admin/ (panel), Public/ (portal), Auth/
├── Http/Requests/      Validación de servidor (Form Requests)
├── Http/Middleware/    Usuario activo, cabeceras de seguridad
├── Livewire/Admin/     Plantillas, inscripciones, búsqueda de jugadores, auditoría
├── Models/Concerns/    BelongsToClub (aislamiento), Auditable, HasSlug
├── Policies/           Autorización por permiso + organización
├── Services/           AuditLogger, ImageService
└── Support/            Catálogo de permisos por rol
```

## Identidad gráfica

`public/assets/branding/`: originales sin modificar (`original/`), versiones recortadas en PNG y WebP
(`hr-logo-principal*`, `hr-logo-horizontal*`, `hr-isotipo*`), favicon, iconos PWA (incluidos *maskable*)
e imagen para redes (`og-image.jpg`). La transparencia de los tres PNG originales fue verificada
(canal alfa real, compuesto sobre fondo de contraste).

## Despliegue

Ver [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md) (Nginx, PHP-FPM, MySQL, HTTPS, copias de seguridad, recuperación).
