# Arquitectura — HR Media Score

## Principios

- **Monolito modular**: un solo despliegue Laravel; módulos separados por responsabilidad (Actions, Policies, Livewire, Public/Admin). Sin microservicios.
- **Multi-organización desde el inicio**: todo recurso del dominio pertenece a un `club` (organización). El aislamiento se aplica en tres capas:
  1. **Consultas**: `scopeVisibleTo($user)` (trait `BelongsToClub`).
  2. **Autorización**: Policies verifican permiso **y** pertenencia (`$user->belongsToClub($model->club_id)`).
  3. **Escritura**: `ResolvesClub::validatedForClub()` fuerza el `club_id` del usuario; un `club_id` inyectado en el formulario se ignora (solo el superadministrador elige organización). Las referencias (temporada, sede, equipo, jugador) se validan con `exists` restringido al club.
- **Reglas de negocio en Actions transaccionales** con bloqueo de fila (`lockForUpdate`) para evitar condiciones de carrera (p. ej. dos altas simultáneas que superen el máximo de plantilla).
- **Doble barrera de integridad**: validación en la aplicación (mensajes claros) + restricciones en MySQL (únicas, foráneas, `CHECK`).
- **Reglamento configurable por torneo** (`tournament_rules`): nada específico del Club Gustavo Behall está programado como constante.

## Modelo de datos

| Tabla | Función |
|---|---|
| `clubs` | Organizaciones (clubes, ligas). Base del aislamiento. |
| `users` | + `club_id` (nulo para superadmin), `is_active`, `last_login_at`. |
| `roles`, `permissions`, `model_has_*`, `role_has_permissions` | Spatie Permission. |
| `venues` | Sedes (único por club + nombre). |
| `seasons` | Temporada de una organización; agrupa torneos y estadísticas por temporada. |
| `tournaments` | Torneo (edición, lema, categoría, rama, estado, visibilidad, destacado). Borrado lógico. |
| `tournament_rules` | **Adicional**: reglamento 1:1 por torneo (edades, plantillas, refuerzos, formato, playoffs, puntuación, disciplina). |
| `teams` | Equipo permanente de la organización (colores, logo, cuerpo técnico). Sobrevive a torneos. |
| `players` | Persona-jugador independiente de equipos → conserva historial. Datos sensibles ocultos. |
| `tournament_teams` | Inscripción de un equipo en un torneo (único torneo+equipo). |
| `team_rosters` | Plantilla. `tournament_id` desnormalizado para garantizar en BD: un jugador, un equipo por torneo; dorsal único por equipo. |
| `games` | Partido (fase, jornada, serie, estado, marcador, anotador asignado). `CHECK home <> away`. |
| `game_periods` | Marcador por período. |
| `game_events` | Bitácora inmutable de la mesa de anotación (Fase 3); correcciones = anular + referenciar. |
| `player_game_stats` | Estadística individual por partido (único partido+jugador). |
| `standings` | Tabla de posiciones materializada (Fase 2 la recalcula). |
| `sanctions` | Técnicas, expulsiones, agresiones, impuntualidad, con partidos de suspensión. |
| `audit_logs` | Bitácora de cambios (valores antes/después, sin contraseñas) y accesos. |

## Roles y permisos

| Permiso | Superadmin | Admin. club | Organizador | Anotador |
|---|:-:|:-:|:-:|:-:|
| Dashboard | ✔ | ✔ | ✔ | ✔ |
| Crear organizaciones | ✔ | | | |
| Editar su organización | ✔ | ✔ | | |
| Usuarios, auditoría | ✔ | ✔ (su club) | | |
| Torneos, sedes, temporadas, equipos, jugadores, plantillas | ✔ | ✔ | ✔ | |
| Ver torneos | ✔ | ✔ | ✔ | ✔ |
| Anotar partidos asignados (Fase 3) | ✔ | ✔ | ✔ | ✔ |

El visitante (sin cuenta) solo accede al portal público. El catálogo vive en `app/Support/Permissions.php`.

## Seguridad

Contraseñas con bcrypt (cast `hashed`); CSRF en todos los formularios; limitación de intentos de acceso
(5/min por correo+IP) y de recuperación; respuesta idéntica exista o no la cuenta; usuarios u organizaciones
desactivados son expulsados aunque tengan sesión; imágenes validadas (tipo, tamaño, dimensiones) y
**re-codificadas con GD** a WebP (elimina metadatos y contenido no-imagen); cabeceras `X-Frame-Options`,
`nosniff`, `Referrer-Policy`, `Permissions-Policy`; el portal nunca expone fecha de nacimiento, documento
ni datos del tutor de jugadores (menores de edad). Cada acción Livewire re-autoriza en el servidor.

## Preparación para fases siguientes

- **Fase 2** (calendario, resultados, posiciones, desempates, playoffs): tablas `games`, `game_periods`, `standings` y reglas (`tiebreakers`, `semifinal_best_of`, `final_best_of`, `direct_final_seeds`, `series_key`) ya existen.
- **Fase 3** (mesa de anotación): `game_events` con secuencia única, anulación y corrección; `GameEventType` con valor en puntos; `scorekeeper_id` por partido; permiso `games.score`. Tiempo real: Laravel Reverb + Redis.
- **Fase 4**: manifiesto web e iconos PWA ya publicados; API móvil se añadirá con Sanctum sobre las mismas Actions y Policies.
