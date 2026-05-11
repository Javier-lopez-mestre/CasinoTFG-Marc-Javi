# TODO - Refactor CasinoTFG (CASINOPRUEBAS/PRINCIPAL)

## Paso 1: Planificación y scaffolding
- [x] Analizar archivos principales
- [x] Definir lista de archivos a crear/editar

## Paso 2: Crear archivos base
- [x] Crear `CASINOPRUEBAS/PRINCIPAL/config.php`
- [x] Crear `CASINOPRUEBAS/PRINCIPAL/db.php`
- [x] Crear `CASINOPRUEBAS/PRINCIPAL/session_helpers.php`

## Paso 3: Cambios de backend
- [x] Editar `conexion.php` para delegar en `db.php` (o reemplazarlo)
- [x] Editar `principal.php` y `perfil.php` para usar `session_helpers.php`
- [x] Editar `webhook.php` para eliminar secrets hardcodeados y usar config
- [x] Ajustar `saldo.php` para respuesta consistente (y opcional: cache-control)


## Paso 4: Rendimiento front
- [x] Crear `assets/app.js` y mover funciones JS comunes
- [x] Crear `assets/app.css` si aplica
- [x] Reducir polling (o hacerlo menos agresivo) en `principal.php` y `perfil.php`


## Paso 5: QA básico
- [ ] Probar login/registro
- [ ] Probar principal/perfil (polling saldo)
- [ ] Revisar que navegación y rutas no rompan
- [ ] Revisar que webhook responda OK con secretos correctos

