# Auditoría de Seguridad

SPEC: `003-woocomerce-enrollment`
Ronda: `1`
Auditor: Mimo
Commit auditado: `481c457`
Fecha: `2026-09-28`

Verdict: `PASS WITH RECOMMENDATIONS`

Audit completeness: `Complete`

Nota sobre el commit: el commit de implementación registrado es `481c457`. Verificado que el árbol de trabajo es idéntico a `481c457` para `src/`, `tests/` y `kaanbal.php` (`git diff 481c457 -- src tests kaanbal.php` sin cambios); las diferencias del árbol son solo documentación (`implementation-status.md`, SPEC-004 sin seguimiento).

---

## 1. Resumen

Evaluación de seguridad de la implementación dentro del alcance aprobado.

La superficie de ataque de SPEC-003 es pequeña y está bien contenida:

- una única entrada de formulario administrativo (`ProductCourseMetaBox` sobre el editor de producto WooCommerce)
- escrituras transaccionales disparadas solo por hooks de estado de pedido de WooCommerce (server-side, sin endpoint público)
- ausencia total de REST/AJAX/shortcodes/endpoints propios en el alcance

Los controles de autorización, CSRF, sanitización, escaping y SQL preparado están implementados y fueron verificados por inspección estática. No se encontró vulnerabilidad explotable dentro del alcance.

Blocking findings: `0`
Non-blocking findings: `1`

---

## 2. Superficies Revisadas

- autenticación: N/A (ningún endpoint público; el estado de sesión solo aplica al editor de producto)
- autorización: revisada — capabilities de WordPress, guardas server-side
- ownership: revisada — `edit_post` sobre el producto concreto
- capabilities: revisada
- nonces / CSRF: revisadas
- XSS / escaping: revisados
- sanitización de entradas: revisada
- SQL injection / prepared statements: revisados
- IDOR: N/A (sin endpoints por objeto accesibles al usuario)
- REST / AJAX: N/A (no existen en el alcance)
- uploads / acceso a archivos / secretos: N/A (no existen en el alcance)
- exposición de datos: revisada (notas de pedido, notices admin, tablas propias)
- manipulación de reglas de negocio: revisada (concesión/revocación de matrículas)
- integración WooCommerce: revisada (hooks, adapter, pedidos de invitado)

---

## 3. Threat Review

| Superficie | Estado | Evidence |
|---|---|---|
| Authorization | `PASS` | `ProductCourseMetaBox::canSave()` exige `current_user_can('edit_post', $product_id)` y nonce (`src/WooCommerce/Presentation/Admin/ProductCourseMetaBox.php:186`); concesión/revocación de matrículas solo ocurre desde `woocommerce_order_status_*` hooks (`src/WooCommerce/WooCommerceModule.php:36-39`), no desde input del usuario. Verificado estáticamente y por tests (`tests/Integration/woocommerce-enrollment.php:87-108`, `:132`). |
| CSRF | `PASS` | `wp_nonce_field('kaanbal_save_product_courses', ...)` (`ProductCourseMetaBox.php:38`) y `wp_verify_nonce($nonce, 'kaanbal_save_product_courses')` (`:186`) con acción consistente; la misma garra exige capability, por lo que un nonce válido de un usuario sin `edit_post` no basta (AC-019). Verificado estáticamente y parcialmente por tests (ver SEC-001). |
| XSS | `PASS` | Toda la salida del metabox usa `esc_html` / `esc_attr` / `checked()` (`ProductCourseMetaBox.php:44-68`, incluido `get_the_title()` y el label de estado). La nota de pedido de invitado es un string i18n estático (`src/WooCommerce/WooCommerceModule.php:64`) y `add_order_note()` la registra como nota privada. Verificado estáticamente. |
| SQL Injection | `PASS` | Todas las consultas usan `$wpdb->prepare` con `%d`/`%s` (`ProductCourseRepository.php`, `EnrollmentRepository.php`, `EnrollmentSourceRepository.php`); los IDs de entrada pasan por `absint` (`ProductCourseMetaBox.php:80-83`); los nombres de tabla derivan de `$wpdb->prefix` + constantes; `SHOW TABLES LIKE` usa `esc_like` + `prepare` (`EnrollmentSchemaMigration.php`). Sin concatenación de input en SQL. Verificado estáticamente. |
| IDOR | `N/A` | No hay endpoints por objeto; los writes transaccionales se limitan a fuentes del pedido que dispara el hook (`revokeWooCommerceSourcesByOrder` filtra por `order_id` server-side). |
| Business rule manipulation | `PASS` | Imposible crear matrícula sin un evento de pedido de WooCommerce: `customer_id = 0` falla en cerrado (`GrantCoursesFromOrder.php:43-45`), cursos inexistentes/eliminados/en papelera no conceden (`:33`, `:60-65`), y los estados `pending`/`on-hold`/`failed` no están enganchados. Idempotencia reforzada por constraints (`UNIQUE(user_id, course_id)`, `UNIQUE(product_id, course_id)`, `UNIQUE(enrollment_id, source_type, order_item_id, product_id)`) e `INSERT IGNORE` / `ON DUPLICATE KEY UPDATE`. |
| Data exposure | `PASS` | Sin superficie pública de lectura de matrículas; la nota de invitado no contiene PII ni secretos; el notice de fallo de esquema exige `current_user_can('activate_plugins')` (`src/Bootstrap/Plugin.php:55-57`) y escapa su salida. |

---

## 4. Findings

### SEC-001 — La protección CSRF (nonce) no está aislada por pruebas automatizadas

Severity: `Low`
Blocking: `No`
Classification: `Test Gap`
Status: `NEW`

Affected requirement:

`AC-019` (TASK-032), `plan.md` §16 Seguridad Administrativa (nonce), TASK-005 (Seguridad: nonce)

Affected files:

- `tests/Integration/woocommerce-enrollment.php`
- `src/WooCommerce/Presentation/Admin/ProductCourseMetaBox.php`

Attack surface:

`save_post_product` → `ProductCourseMetaBox::save()` (formulario del editor de producto, acción `kaanbal_save_product_courses`)

Description:

El control CSRF está implementado y es correcto, pero ninguna prueba automatizada fallaría si se elimina la verificación del nonce. La cobertura actual de rechazo no separa el control de nonce del control de capability.

Evidence:

`tests/Integration/woocommerce-enrollment.php` contiene exactamente tres ramas de rechazo/aceptación sobre `$meta_box->save()`:

- Líneas 87-95: `wp_set_current_user(0)` con nonce `'invalid'` → rechazado. En esta rama `current_user_can('edit_post', ...)` también es falso, por lo que el rechazo no demuestra el nonce.
- Líneas 99-108: subscriber con nonce válido → rechazado (AC-019, capability). Aísla la capability, no el nonce.
- Líneas 111-126: administrator con nonce válido → aceptado.

No existe ningún caso con un usuario que sí tenga `edit_post` sobre el producto y con nonce ausente o inválido que espere rechazo. Verificado por inspección: si se elimina `wp_verify_nonce($nonce, 'kaanbal_save_product_courses')` de `ProductCourseMetaBox::canSave()` (`ProductCourseMetaBox.php:186`) dejando el resto intacto, ninguna aserción de la suite unitaria o de integración registrada quedaría en fallo (análisis estático; la mutación no fue ejecutada).

Attack scenario:

Una regresión futura que retire o relaje la verificación del nonce pasaría desapercibida en el quality gate. Un atacante podría entonces explotar CSRF contra un usuario autenticado con `edit_post` sobre un producto (por ejemplo un shop manager) para asociar o desasociar cursos de productos mediante una petición forjada, alterando qué cursos concede cada compra.

Expected protection:

El nonce requerido por `plan.md` §16 debe estar protegido por una prueba de regresión que demuestre que un usuario con capability y un nonce inválido/ausente no modifica asociaciones.

Actual behavior:

Control implementado y funcional (verificado estáticamente); sin cobertura de regresión que lo asegure.

Impact:

Sin impacto sobre la implementación actual. Riesgo de regresión silenciosa de un control CSRF en una superficie que determina la concesión futura de acceso académico.

Required correction:

Añadir un caso en `tests/Integration/woocommerce-enrollment.php` donde un usuario con `edit_post` sobre el producto envíe el formulario con el nonce ausente o inválido y se verifique que `kaanbal_product_courses` no cambia. No requiere modificar código de producción.

---

## 5. Findings de Rondas Anteriores

Ninguno. Primera ronda de auditoría de seguridad de SPEC-003; los identificadores SEC inician en `SEC-001`.

---

## 6. Recomendaciones de Hardening

### SEC-REC-001 — Recalcular server-side el conjunto de cursos desasociables en `syncCourses`

Severity: `Info`
Blocking: No
Classification: Recommendation

Description:

`ProductCourseMetaBox::save()` acepta `kaanbal_listed_course_ids[]` del POST (`ProductCourseMetaBox.php:82-84`) y lo utiliza como conjunto autorizado para desasociar (`ProductCourseRepository::syncCourses`, `:117-137`). El alcance de borrado depende por tanto de un campo oculto controlado por el cliente.

Evidence:

Verificado por inspección que hoy no es explotable más allá de la autoridad ya concedida: `canSave()` exige `edit_post` sobre el producto (`:186`) y `listableCourses()` renderiza siempre todas las asociaciones existentes (las ya asociadas se fusionan vía `$missing`, `:128-152`), por lo que manipular el campo no permite desasociar nada que el formulario no muestre.

Benefit:

Elimina la dependencia de input del cliente en decisiones de alcance de escritura (defensa en profundidad y principio de mínimo privilegio para operaciones destructivas).

Scope note:

La recomendación no representa un requisito de la SPEC actual.

---

### SEC-REC-002 — Confinar las excepciones de transacción en el boundary de los hooks de WooCommerce

Severity: `Info`
Blocking: No
Classification: Recommendation

Description:

`EnrollmentService` lanza `RuntimeException` cuando `START TRANSACTION`/`COMMIT` fallan (`EnrollmentService.php:74-86`) y `WooCommerceModule::grantCourses`/`revokeCourses` (`WooCommerceModule.php:45-71`) no capturan, por lo que la excepción se propaga al ciclo de transición de estado del pedido (checkout o administración).

Evidence:

Verificado por inspección que el comportamiento de seguridad es correcto: ante fallo de base de datos el flujo falla en cerrado (`ROLLBACK`, sin concesión ni revocación parcial, sin matrícula huérfana). No hay defecto de seguridad.

Benefit:

Capturar y registrar en el boundary del hook evitaría que una falla de base de datos se convierta en un fallo visible del checkout, manteniendo la postura fail-closed para matrículas.

Scope note:

La recomendación no representa un requisito de la SPEC actual.

---

## 7. Limitaciones

- Ejecución dinámica del fixture de integración: `BLOCKED BY ENVIRONMENT`. `tests/Integration/woocommerce-enrollment.php` requiere `KAANBAL_WP_PATH` con un WordPress + WooCommerce + base de datos desechables; no se ejecutó durante esta auditoría para no alterar el entorno local. Los tests fueron revisados estáticamente y sus aserciones de seguridad consideradas significativas (verificado: los casos de rechazo lanzan excepción si el guard desaparece, no son placeholders).
- Suite unitaria ejecutada por el auditor: `composer test` → `OK (18 tests, 26 assertions)` (verificado, 2026-09-28).
- No se realizó explotación dinámica (pentest) contra un sitio en vivo; autorización, CSRF, sanitización, escaping y SQL preparado se verificaron por inspección estática del código y de las pruebas.
- Componentes heredados de SPEC-001/SPEC-002 (`CurriculumMetaBoxes`, `Requirements`, `SchemaManager`, `Plugin`) solo se revisaron donde SPEC-003 interactúa con ellos (registro de hooks, migración de esquema, notices). Auditorías previas de esas SPECs quedan fuera de este informe.

---

## 8. Conclusión

Verdict:

`PASS WITH RECOMMENDATIONS`

Blocking findings:

- Ninguno.

Findings no bloqueantes:

- `SEC-001` — La protección CSRF (nonce) no está aislada por pruebas automatizadas.

Recommendations:

- `SEC-REC-001` — Recalcular server-side el conjunto de cursos desasociables en `syncCourses`.
- `SEC-REC-002` — Confinar las excepciones de transacción en el boundary de los hooks de WooCommerce.

Comentario final:

La implementación de SPEC-003 no introduce vulnerabilidades explotables dentro del alcance aprobado: la única entrada de usuario (asociaciones producto-curso) está doblemente protegida con nonce y `edit_post`, valida y sanea todos los IDs, escapa toda su salida y usa SQL preparado; la concesión y revocación de matrículas ocurre solo desde hooks server-side de WooCommerce, con validación de curso, fail-closed ante pedidos de invitado y de base de datos, e idempotencia reforzada por constraints. El único finding es un vacío de pruebas de regresión sobre el control CSRF (no bloqueante); las dos recomendaciones son hardening opcional sin defecto de seguridad actual.
