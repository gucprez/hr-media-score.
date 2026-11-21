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

## 2026-11-21 — Fase 2: Competiciones ✅

### Implementado
- **Calendario**: generador "todos contra todos" (método del círculo) que respeta las vueltas del reglamento, invierte la localía en la 2.ª vuelta y **no duplica partidos ya programados**: completa solo los que faltan, sin que un equipo juegue dos veces en la misma jornada. Programación y edición manual (fecha, hora, sede, jornada, anotador).
- **Resultados**: por períodos (con prórrogas) o solo marcador final; forfeit (20–0 FIBA); pospuesto/cancelado. No se permiten empates en un resultado final.
- **Posiciones**: recálculo automático al guardar cada resultado; puntos por victoria/derrota/forfeit del reglamento; desempates configurables en orden (enfrentamiento directo en puntos y en diferencia, diferencia general, puntos anotados, menos recibidos) y orden alfabético como último criterio.
- **Playoffs** (tabla `playoff_series`): 2 clasificados (final), 3 con pase directo (semifinal 2.º–3.º y final contra el 1.º) o 4 (semifinales 1–4 y 2–3). Series al mejor de N; los juegos se crean a medida que hacen falta (formato 2-2-1 en series de 5/7), los sobrantes se eliminan al decidirse la serie, el ganador avanza solo y el torneo pasa a "Finalizado" al decidirse la final. Un resultado no puede cambiarse si la serie siguiente ya empezó.
- **Anotador**: registra resultados solo de los partidos que tiene asignados; no puede reprogramar ni eliminar.
- **Diseño estilo afiche oficial**: enfrentamientos local (rojo) vs visitante (azul), VS cromado, hora en cinta roja, acentos dorados, tipografía condensada en cursiva. Aplicado a portada ("Hoy se juega / Próxima jornada"), calendario por jornadas, ficha de partido, cuadro de playoffs y panel.
- **Cartelera descargable**: `/torneos/{id}/cartelera/{fecha}` genera el afiche de la jornada (club, edición, lema, copa, fecha, "Doble cartelera" o "Resultados", partidos, sede) y lo descarga como PNG en 1080×1350 (publicación) o 1080×1920 (historia).
- Campos nuevos: copa/patrocinador del torneo (texto y logo) y año de fundación del club.
- **Datos oficiales del afiche**: Jornada 1, sábado 21/11/2026 en el Techado: Ninjas GB vs Cacuses (6:00 PM) y Calle Duarte vs Balkanes (7:00 PM); "Copa La Tendencia Podcast"; club fundado en 1920.

### Verificación
- `php artisan test`: **118 pruebas, 431 aserciones, todas pasan** (24 nuevas de competición: calendario con 4/5/6 equipos, doble vuelta, jornada manual, empates, prórrogas, forfeit, desempate por enfrentamiento directo, flujo completo de playoffs, permisos del anotador, cartelera).
- Flujo real en navegador: carga de resultados por períodos con prórroga y por marcador final → tabla recalculada → cartelera de resultados descargada como PNG de 1080×1920.

### Defectos encontrados y corregidos
- Forfeit: el perdedor no quedaba registrado (operador `+=` de PHP no sobrescribe claves) y contaba como derrota normal.
- Panel de inicio: error al mostrar partidos (relación de sede sin precargar).
- Nombres de equipos cortados en el calendario del panel y en móvil.

### Producción (VPS)
- Reportado: error 500 al editar el último equipo en `score.54.39.98.84.sslip.io`. **No reproducible aquí** (ni en modo producción con cachés). Se reforzó la subida de imágenes para que nunca produzca un 500 y se elevaron los límites de PHP en `install-vps.sh`. Falta el registro de errores del servidor para confirmar la causa.

### Pendiente de confirmar con el club
- Los afiches llaman a los equipos "Ninjas GB, Cacuses GB, Duarte GB, Balkanes GB". En el sistema siguen como se indicó al inicio (Cacuses, Calle Duarte, Balkanes); se cambian en Equipos → Editar.
- Subir los logos oficiales de los 4 equipos, del club y de la copa: la cartelera los usa automáticamente.
- Resto del calendario (jornadas 2 y 3): se generan con "Generar serie regular" indicando la fecha.

## Próximo: Fase 3 — Estadísticas en vivo (mesa de anotación)

## (Histórico) Plan original de la Fase 2
- Generador de calendario todos contra todos (respetando `round_robin_legs`) y programación manual.
- Registro de resultados por período (asignación de anotador).
- Recalculo de `standings` con desempates configurables (`tiebreakers`).
- Series de playoffs (mejor de N, pase directo a la final) y sanciones.
