# PROGRESS — HR Media Score

Registro de avance del desarrollo. Fechas en zona America/Santo_Domingo.

## 2026-11-19 — Fase 1: Base funcional ✅

### Entorno inspeccionado
- PHP 8.3.6 (con gd, intl, pdo_mysql, redis), Composer 2.8, Node 22, npm 10.
- MySQL no estaba instalado → se instaló MySQL 8.0.46.
- No existía un proyecto previo; los repositorios presentes en el entorno (`claude-code`, `jarvis-consenso`) no se tocaron.
- El proxy bloquea las descargas zip de GitHub: Composer instala clonando por git (más lento, funcional).

### Implementado
- Laravel 12.73 + Livewire 3 (con Alpine) + Tailwind 4 + Vite + Spatie Permission 6 + Pest 3.
- **Identidad**: logos oficiales en `public/assets/branding/` (originales intactos + versiones recortadas PNG/WebP, favicon, iconos PWA y maskable, imagen OG). Transparencia verificada en los tres archivos. Sistema de diseño oscuro con la paleta indicada; tipografías autoalojadas (Barlow Condensed + Inter).
- **Base de datos**: 18 entidades solicitadas + `tournament_rules` (reglamento por torneo), con claves foráneas, índices, únicas, `CHECK`, borrado lógico y auditoría. Migraciones, seeders y factories.
- **Autenticación**: acceso, cierre, recuperación y restablecimiento de contraseña, límite de intentos, expulsión de cuentas/organizaciones desactivadas, cambio de contraseña.
- **Roles y permisos**: Superadministrador, Administrador del club, Organizador, Anotador (+ visitante público). Policies con aislamiento por organización.
- **Panel**: dashboard, organizaciones, sedes, temporadas, torneos (con reglamento editable), inscripción de equipos (Livewire), plantillas (Livewire, validación del reglamento en vivo), equipos (colores, logo oficial), jugadores (búsqueda en vivo, foto, historial), usuarios y permisos, auditoría (con detalle antes/después), configuración de cuenta.
- **Portal público**: inicio con torneo destacado, torneos, ficha de torneo (equipos/plantillas, calendario, posiciones, reglamento), equipos, jugadores (sin datos personales), partidos. Todo consulta MySQL; los estados vacíos lo dicen honestamente (no hay resultados ficticios).
- **Datos oficiales**: Club Gustavo Behall, Techado Club Gustavo Behall, Temporada 2026, *XIV Torneo Juvenil Infantil con Refuerzos* («Volviendo a la Tradición») con su reglamento, y los equipos Balkanes, Calle Duarte, Cacuses y Ninjas GB con sus colores.
- **Despliegue**: `docs/DEPLOYMENT.md`, `deploy/nginx.conf`, `deploy/deploy.sh`, `deploy/backup.sh`, servicio de colas, `.env.production.example`.

### Verificación
- `php artisan test`: **92 pruebas, 304 aserciones, todas pasan** (MySQL 8, base `hr_media_score_test`).
- Recorrido automático con Chromium de 37 páginas del panel y del portal: 0 respuestas de error, 0 errores de JavaScript, 0 errores en el log.
- Flujo real en navegador: login → crear jugador → duplicado rechazado → inscribir en plantilla → jugador fuera de edad y dorsal repetido rechazados con mensaje.

### Defectos encontrados y corregidos durante la verificación
- Directiva `@can` que envolvía un `<x-slot>` rompía la ficha de torneo (500).
- Los errores del reglamento no se mostraban en el gestor de plantillas (claves de error no coincidían con las propiedades Livewire).
- El superadministrador podía eliminar su propia cuenta (`Gate::before` saltaba la regla de la Policy).
- Iconos sobredimensionados en accesos rápidos; tabla de posiciones con desplazamiento horizontal en móvil.

### Supuestos a confirmar con el club
- **Refuerzos exentos del rango de edad** (15–18): configurado así porque es el sentido habitual de "refuerzo"; se cambia con un interruptor en el reglamento. También existe "edad máxima de refuerzos" (vacía = sin límite).
- **Puntos en tabla**: victoria 2, derrota 1, forfeit 0 (estándar FIBA); editables.
- **Sanciones y puntualidad**: se registró un texto general; los detalles exactos (partidos de suspensión, minutos de tolerancia) deben copiarse del reglamento oficial.
- **Colores hexadecimales** de los equipos: aproximaciones de los nombres de color; ajustables en el panel.
- Fechas del torneo: no proporcionadas, quedan vacías.
- Logos de equipos: no se generaron sustitutos; sin logo se muestran las iniciales sobre los colores del equipo hasta que se suba el oficial.

## Próximo: Fase 2 — Competiciones
- Generador de calendario todos contra todos (respetando `round_robin_legs`) y programación manual.
- Registro de resultados por período (asignación de anotador).
- Recalculo de `standings` con desempates configurables (`tiebreakers`).
- Series de playoffs (mejor de N, pase directo a la final) y sanciones.
