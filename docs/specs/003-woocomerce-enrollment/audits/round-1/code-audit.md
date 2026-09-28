# Auditoría de Código y Cumplimiento

SPEC: `SPEC-003 — WooCommerce Enrollment`
Ronda: `1`
Auditor: Claude Code
Commit auditado: **ninguno válido** — working tree sin commitear sobre `001496c` (branch `feature/spec-002-courses-and-curriculum`). El commit declarado `876b391` no existe en el repositorio.
Fecha: `2026-09-28`

Verdict: `FAIL`

Audit completeness: `Partial` — revisión estática completa del working tree; integración WooCommerce no reproducida por el auditor (ver §8).

## 1. Resumen

La implementación de SPEC-003 está, en sustancia, bien encaminada: separa `Enrollment` de `Enrollment Sources`, protege la idempotencia en esquema (`UNIQUE(user_id, course_id)`, `UNIQUE(product_id, course_id)`, `UNIQUE(enrollment_id, source_type, order_item_id, product_id)`) y en aplicación (`INSERT IGNORE` / `ON DUPLICATE KEY`), usa los cuatro hooks aprobados en `plan.md §11`, revoca por fuentes antes de decidir sobre la matrícula, reactiva matrículas revocadas, ignora pedidos de invitado y no registra nada cuando WooCommerce está ausente. No se encontró scope creep académico.

La ronda termina en `FAIL` por dos motivos, ninguno de ellos un defecto de lógica de producción observado:

1. **Precondición de auditoría no cumplida** (`CODE-001`): no existe un candidato estable. `implementation-status.md` declara `Ready for audit: Yes` apuntando a un commit inexistente, con `TASK-035` en progreso y `TASK-037` pendiente.
2. **Cobertura declarada que no existe** (`CODE-002`): `TASK-026` está `Done` y `implementation-status.md` marca AC-004, AC-008 y AC-009 como `INTEGRATION-TESTED`, pero el fixture de integración no contiene esos casos (bundle, productos solapados en el mismo pedido, producto sin cursos).

Tasks revisadas: 37
Acceptance Criteria revisados: 21
Escenarios Gherkin revisados: 21

Blocking findings: 2
Non-blocking findings: 6

---

## 2. Alcance Auditado

Documentos revisados:

- `docs/specs/003-woocomerce-enrollment/spec.md`
- `docs/specs/003-woocomerce-enrollment/plan.md`
- `docs/specs/003-woocomerce-enrollment/feature.feature`
- `docs/specs/003-woocomerce-enrollment/tasks.md`
- `docs/specs/003-woocomerce-enrollment/implementation-status.md`
- `docs/audit-standard.md`, `docs/agents.md`, `docs/data-model.md` (§24–28)

Código revisado (íntegro, working tree):

- `kaanbal.php`
- `src/Bootstrap/Plugin.php`, `src/Bootstrap/Activator.php`, `src/Bootstrap/Version.php` (modificados)
- `src/Shared/Database/SchemaManager.php` (modificado)
- `src/Shared/Database/SchemaMigration.php`, `src/Shared/Database/EnrollmentSchemaMigration.php` (nuevos)
- `src/Enrollment/Application/EnrollmentService.php`
- `src/Enrollment/Application/GrantCoursesFromOrder.php`
- `src/Enrollment/Infrastructure/EnrollmentRepository.php`
- `src/Enrollment/Infrastructure/EnrollmentSourceRepository.php`
- `src/Enrollment/Infrastructure/ProductCourseRepository.php`
- `src/WooCommerce/WooCommerceModule.php`
- `src/WooCommerce/Infrastructure/WooCommerceOrderAdapter.php`
- `src/WooCommerce/Presentation/Admin/ProductCourseMetaBox.php`
- `wp-content/plugins/woocommerce/includes/class-wc-order.php` (v11.1.1, solo para confirmar la firma de `woocommerce_order_status_{to}` y el `try/catch` de `status_transition()`)

Pruebas revisadas:

- `tests/Unit/WooCommerceModuleTest.php`
- `tests/Unit/LifecycleTest.php` (modificado)
- `tests/bootstrap.php` (modificado: stub de `add_action`)
- `tests/Integration/woocommerce-enrollment.php`

---

## 3. Trazabilidad

| AC | Scenario | Task | Implemented | Verified | Evidence |
|---|---|---|---|---|---|
| AC-001 | SC-001 | TASK-001, 004, 023 | Yes | Partial | Tabla N:N + `findCoursesByProduct/findProductsByCourse`. El fixture asocia 1 curso a 2 productos (N productos → 1 curso). **1 producto → N cursos no se prueba** (ver CODE-002). |
| AC-002 | SC-001 | TASK-001, 004 | Yes | Yes (código) | `UNIQUE KEY product_course` + `INSERT IGNORE` en `attachCourse()`; `syncCourses()` usa `array_unique`. |
| AC-003 | SC-002 | TASK-005, 023 | Yes | Yes | `ProductCourseMetaBox::save()`; fixture guarda con admin + nonce válido y relee la asociación. Ver CODE-003 (pérdida de asociaciones no publicadas). |
| AC-004 | SC-004 | TASK-021 | Yes | **No** | Código: `$courses_by_product[...] ?? array()` → no-op. **Ningún pedido del fixture contiene un producto sin cursos**, pese a `INTEGRATION-TESTED`. |
| AC-005 | SC-005 | TASK-002, 006, 008 | Yes | Yes | `findOrCreate()` inserta `status = 'active'`; fixture verifica `active`. |
| AC-006 | SC-005 | TASK-002, 006, 033 | Yes | Yes | `UNIQUE KEY user_course` + `INSERT IGNORE`; fixture: 4 pedidos del mismo curso → 1 matrícula (la consulta de estado usa `get_row`, suficiente junto con el constraint). |
| AC-007 | SC-006 | TASK-003, 007, 025 | Yes | Partial | `addWooCommerceSource()` persiste `order_id`, `product_id`, `order_item_id`, `enrollment_id`. El fixture solo cuenta filas; no verifica columnas. |
| AC-008 | SC-007 | TASK-010, 013, 026 | Yes | **No** | Bucle `foreach ($courses_by_product[...] as $course_id)` en `GrantCoursesFromOrder::grant()`. **Sin test** (el fixture crea un único curso). |
| AC-009 | SC-008 | TASK-010, 014, 026 | Yes | **No** | Mismo bucle + unique constraints. El fixture usa productos A y B en **pedidos distintos** (fuente alternativa), no en el mismo pedido. |
| AC-010 | SC-009 | TASK-015, 026 | Yes | Yes | processing → completed mantiene 1 fuente; `ON DUPLICATE KEY` + unique de fuente. |
| AC-011 | SC-010 | TASK-011, 026 | Yes | Yes | Hook `woocommerce_order_status_processing`; fixture. |
| AC-012 | SC-011 | TASK-012, 027 | Yes | Yes | Hook `..._completed`; fixture mantiene 1 fuente / `active`. |
| AC-013 | SC-012 | TASK-016, 028 | Yes | Yes | Hook `..._cancelled` → `revokeWooCommerceOrder()`; fixture `reactivation_order` cancelado → `revoked`. |
| AC-014 | SC-013 | TASK-017, 029 | Yes | Yes | Hook `..._refunded`; fixture. Refund parcial (EC-010) no revoca: verificado en fixture. |
| AC-015 | SC-014 | TASK-018, 028, 029 | Yes | Yes | `hasValidSources()`; fixture: refund del pedido 1 con pedido alternativo vigente → `active`. |
| AC-016 | SC-015 | TASK-018, 028, 029 | Yes | Yes | fixture: refund del pedido alternativo → `revoked`. |
| AC-017 | SC-016 | TASK-019, 030 | Yes | Yes | `activate()` pone `revoked_at = NULL`; fixture: nueva compra tras revocación → `active` con la misma matrícula. |
| AC-018 | SC-017 | TASK-022 | Yes | Yes | `class_exists('WooCommerce')` en `registerWhenWooCommerceIsAvailable()`; `WooCommerceModuleTest`. |
| AC-019 | SC-003 | TASK-005, 032 | Yes | Partial | `canSave()`: nonce + `edit_post`. El test combina nonce inválido **y** usuario 0; la rama de capability no se prueba aislada (CODE-006). |
| AC-020 | SC-018, SC-019 | TASK-004, 023 | Yes | Yes | Curso inexistente (`999999999`) rechazado; guardar sobre un post que no es `product` rechazado. La validación vive en la capa de presentación, no en el repositorio (aceptable para el alcance). |
| AC-021 | SC-021 | TASK-034 | Yes | Yes (código) | Ningún archivo toca progreso, quizzes ni certificados. |

Edge cases:

| EC | Implemented | Verified | Evidence |
|---|---|---|---|
| EC-001 / SC-020 guest | Yes | Yes | `0 === customerId()` → return; fixture. Sin logging (CODE-005). |
| EC-003 curso eliminado | Yes | **No** | `isValidCourse()` descarta posts inexistentes o en papelera; sin test (plan §23 lo exige). |
| EC-004 producto eliminado | Yes | No | Asociaciones huérfanas no se consultan si el producto no está en el pedido; sin test. |
| EC-005 / EC-006 | Yes | Yes | Constraints + fixture processing→completed. |
| EC-007 / EC-008 | Yes | Yes | Fixture. |
| EC-009 reactivación | Yes | Yes | Fixture. |
| EC-010 refund parcial | Yes | Yes | Fixture con `wc_create_refund` de 10. |
| EC-010 (bis) dos items del mismo producto | Yes | No | Dos `order_item_id` distintos → dos fuentes; trazabilidad razonable. Sin test. |

---

## 4. Verificación de Tasks

| Task | Status documentado | Resultado | Observaciones |
|---|---|---|---|
| TASK-001–003 | Done | PASS | `EnrollmentSchemaMigration` vía `SchemaManager`, `DATABASE_SCHEMA = 2`. |
| TASK-004 | Done | PASS | `ProductCourseRepository` (+ `syncCourses`, `findCoursesByProducts`). |
| TASK-005 | Done | PASS | Metabox con nonce/capability/sanitización. Ver CODE-003. |
| TASK-006–008 | Done | PASS | `findOrCreate`, `activate`, `revoke`; `EnrollmentService` transaccional. |
| TASK-009 | Done | PASS | `WooCommerceOrderAdapter` (id, customer, items, productIds). No expone `status`, innecesario porque el hook ya lo implica. |
| TASK-010 | Done | PASS | `findCoursesByProducts()` con `IN (...)`, evita N+1. |
| TASK-011–022 | Done | PASS | Implementados según §3. |
| TASK-023 | Done | PASS | Casos positivos y negativos de asociación presentes. |
| TASK-024, 025 | Done | PASS (parcial) | Cubiertos por el fixture único, no por tests de repositorio separados. Aceptable. |
| **TASK-026** | Done | **FAIL** | Lista explícitamente "1 producto / varios cursos" y "varios productos / curso solapado"; ninguno existe en el fixture. → CODE-002. |
| TASK-027–032 | Done | PASS | TASK-032 con la observación de CODE-006. |
| TASK-033 | Done | PASS | Constraints + `INSERT IGNORE`/`ON DUPLICATE KEY` + transacción. |
| TASK-034 | Done | PASS | Sin scope creep. |
| **TASK-035** | In Progress | **FAIL** | Quality gate no cerrado, pero `implementation-status.md` declara `Ready for audit: Yes`. → CODE-001. |
| TASK-036 | Done | FAIL | El estado contiene afirmaciones falsas (commit inexistente, ACs "INTEGRATION-TESTED" sin test, conteo de tests desactualizado). → CODE-001, CODE-002. |
| **TASK-037** | Pending | **FAIL** | Candidato de auditoría no preparado. → CODE-001. |

---

## 5. Findings

### CODE-001 — No existe candidato de auditoría estable; el estado declara lo contrario

Severity: Medium
Blocking: Yes
Classification: Documentation Mismatch
Status: NEW

Affected requirement:
Definition of Done; `audit-standard.md §3` (mismo commit para los tres auditores)

Affected task:
TASK-035, TASK-036, TASK-037

Affected files:
- `docs/specs/003-woocomerce-enrollment/implementation-status.md`

Description:

`implementation-status.md` declara `Status: Ready for audit`, `Ready for audit: Yes` y `Current commit: 876b391`, pero:

- `git cat-file -t 876b391` → `fatal: Not a valid object name 876b391`.
- `HEAD` es `001496c` (branch `feature/spec-002-courses-and-curriculum`); todo SPEC-003 está sin commitear (`??` en `src/Enrollment/`, `src/WooCommerce/`, `EnrollmentSchemaMigration.php`, `SchemaMigration.php`, tests y la carpeta de la SPEC; `M` en `Activator`, `Plugin`, `Version`, `SchemaManager`, `composer.json`, tests).
- `Branch: Not created`.
- `TASK-035` está `In Progress` y `TASK-037` `Pending`; `TASK-037` exige explícitamente que solo se declare `Ready for audit: Yes` cuando todo pase.
- `Current audit round: 0`.
- El working tree también contiene una modificación no commiteada a `docs/specs/002-courses-and-curriculum/audits/round-1/code-audit.md`, mezclando artefactos de dos SPECs.

Evidence:

`git rev-parse HEAD` → `001496cebe14575820caba535477194aa1209163`; `git status --short` (arriba); `git cat-file -t 876b391` falla.

Impact:

Qwen y Mimo no pueden auditar el mismo candidato; cualquier resultado de esta ronda no es reproducible sobre un hash. Es el mismo defecto de proceso que `CODE-003` en SPEC-002 ronda 1.

Required correction:

Crear una branch `feature/spec-003-...` (o la que defina el flujo), commitear la implementación, cerrar TASK-035/TASK-037, y registrar en `implementation-status.md` el hash real, `Current audit round: 1` y un working tree limpio. La modificación del audit de SPEC-002 debe ir en su propio commit o descartarse.

---

### CODE-002 — Casos obligatorios de test ausentes pese a `TASK-026: Done` y ACs marcados `INTEGRATION-TESTED`

Severity: Medium
Blocking: Yes
Classification: Test Gap
Status: NEW

Affected requirement:
AC-004, AC-008, AC-009 (y EC-003 de `plan.md §23`)

Affected scenario:
SC-004, SC-007, SC-008

Affected task:
TASK-013, TASK-014, TASK-021, TASK-026

Affected files:
- `tests/Integration/woocommerce-enrollment.php`
- `docs/specs/003-woocomerce-enrollment/implementation-status.md`

Description:

`plan.md §23` define como **obligatorios** `1 product → N courses`, `N products → same course`, `product without courses` y `deleted course`. `TASK-026` enumera "1 producto / varios cursos" y "varios productos / curso solapado" y está `Done`. `implementation-status.md` marca AC-004, AC-008 y AC-009 como `IMPLEMENTED AND INTEGRATION-TESTED`.

El único fixture de integración:

- crea **un solo curso** (`$course_id`), por lo que el bundle (AC-008) nunca se ejercita;
- añade el producto A y el producto B en **pedidos separados** (`$order`, `$alternative_order`), nunca en el mismo pedido (AC-009 / SC-008 exige "ambos forman parte del mismo pedido");
- todos los pedidos contienen el producto A, asociado a un curso; ningún pedido incluye un producto sin cursos (AC-004);
- no hay caso de curso eliminado/en papelera (EC-003).

Evidence:

`tests/Integration/woocommerce-enrollment.php` líneas 58 (único curso), 130–149 (A y B en pedidos distintos), 171–196 (siempre `$product_a`).

Expected:

Evidencia reproducible para los ACs centrales de la SPEC, conforme a lo que declara el estado.

Actual:

La lógica de producción parece correcta por inspección (`GrantCoursesFromOrder::grant()` itera todos los cursos de cada item; las constraints absorben el solapamiento), pero no hay prueba, y la documentación afirma que sí la hay.

Impact:

AC-008 y AC-009 son el núcleo de "múltiples cursos por producto / múltiples productos por curso" de la SPEC. `audit-standard.md §24` lista "task marcada Done pero no implementada" y "documentación de estado falsa" como blockers válidos; §34: "Un AC central sin prueba ni evidencia reproducible puede ser blocker".

Required correction:

Extender el fixture (o añadir uno) con: (a) un producto asociado a ≥2 cursos → N matrículas y N fuentes; (b) un pedido con productos A y B que conceden el mismo curso → 1 matrícula y 2 fuentes; (c) un pedido con un producto sin cursos → sin matrícula para ese producto y pedido completado con normalidad; (d) asociación a un curso enviado a la papelera → sin matrícula. Actualizar `implementation-status.md` para reflejar la cobertura real.

---

### CODE-003 — Guardar un producto borra en silencio asociaciones a cursos no publicados

Severity: Medium
Blocking: No
Classification: Data Integrity Risk
Status: NEW

Affected requirement:
AC-003, RF-003

Affected task:
TASK-005

Affected files:
- `src/WooCommerce/Presentation/Admin/ProductCourseMetaBox.php`

Description:

`render()` solo lista cursos con `post_status = 'publish'` (y si no hay ninguno, retorna sin checkboxes). `save()` hace `syncCourses($product_id, $valid_course_ids)` con **solo** lo recibido en `$_POST`, lo que ejecuta `detachCourse()` para todo lo que no venga marcado.

Escenario: el producto X concede el curso C. El curso C pasa temporalmente a `draft`, `pending`, `private` o `future` (lanzamiento programado). Un administrador edita X por cualquier motivo (precio, descripción). C no aparece en el metabox → no se envía → la asociación X↔C se elimina. Cuando C vuelve a publicarse, las compras de X cobran pero no conceden el curso, sin ningún aviso.

Además, `render()` y `save()`/`GrantCoursesFromOrder::isValidCourse()` usan criterios distintos (publish vs. no-trash), así que un curso programado nunca puede asociarse desde la UI aunque el backend lo aceptaría.

Evidence:

`ProductCourseMetaBox.php` líneas 35–49 (filtro `publish` y early return) y 84 (`syncCourses`); `ProductCourseRepository::syncCourses()` líneas 114–116.

Impact:

Pérdida silenciosa de configuración comercial → compras sin acceso. No bloqueante porque requiere que el curso esté despublicado en el momento del guardado y ningún AC fija ese comportamiento, pero el efecto es el de la categoría "pérdida de matrículas".

Required correction (recomendada):

Listar en el metabox también los cursos ya asociados (y los estados que el backend acepta), o limitar `syncCourses()` a los IDs que el formulario realmente mostró (p. ej. enviar la lista de IDs renderizados en un campo oculto y solo desasociar entre ellos).

---

### CODE-004 — Re-conceder una fuente revocada sobrescribe su `revoked_at`

Severity: Low
Blocking: No
Classification: Recommendation
Status: NEW

Affected requirement:
RF-017, RB-008

Affected files:
- `src/Enrollment/Infrastructure/EnrollmentSourceRepository.php` (línea 27)

Description:

`ON DUPLICATE KEY UPDATE revoked_at = NULL`: si un pedido cancelado vuelve a `processing` (reintento de pago, corrección manual), la misma fila de fuente se reutiliza y el registro de la revocación desaparece; `granted_at` conserva la fecha original. El comportamiento de acceso es correcto, pero se pierde el rastro de que hubo una revocación intermedia.

Impact:

Menor pérdida de historial comercial; no afecta ningún AC.

Required correction (recomendada):

Actualizar también `granted_at` al reactivar, o registrar la transición (nota de pedido o columna/evento). Decisión humana si interesa ese nivel de historial.

---

### CODE-005 — Pedidos de invitado sin registro controlado

Severity: Low
Blocking: No
Classification: Recommendation
Status: NEW

Affected requirement:
EC-001

Affected files:
- `src/Enrollment/Application/GrantCoursesFromOrder.php` (líneas 21–23)

Description:

EC-001 pide registrar el resultado "cuando exista logging disponible". Con WooCommerce activo existe `wc_get_logger()` (o una nota de pedido), pero el guest order simplemente retorna. Para el administrador, un cliente invitado que compró un curso no tiene ninguna pista de por qué no recibió acceso.

Required correction (recomendada):

Registrar vía `wc_get_logger()` o `$order->add_order_note()` desde la capa WooCommerce (no desde el servicio de aplicación, para no acoplarlo).

---

### CODE-006 — La rama de capability de AC-019 no se prueba aislada

Severity: Low
Blocking: No
Classification: Test Gap
Status: NEW

Affected requirement:
AC-019

Affected task:
TASK-032

Affected files:
- `tests/Integration/woocommerce-enrollment.php` (líneas 84–93)

Description:

El caso negativo usa a la vez `wp_set_current_user(0)` **y** `nonce = 'invalid'`; cualquiera de las dos condiciones basta para rechazar. Si mañana se eliminara `current_user_can('edit_post', ...)` de `canSave()`, el test seguiría pasando. La implementación actual es correcta.

Required correction (recomendada):

Añadir un caso con nonce válido generado para un usuario `subscriber` (o `customer`) autenticado.

---

### CODE-007 — `SchemaManager` marca la versión aunque la migración falle

Severity: Low
Blocking: No
Classification: Recommendation
Status: NEW

Affected files:
- `src/Shared/Database/SchemaManager.php` (líneas 33–37)

Description:

`dbDelta()` no lanza excepciones; si la creación de tablas falla (permisos, motor), `kaanbal_db_version` se actualiza igualmente a `2` y la migración no se reintenta en requests posteriores, dejando los repositorios contra tablas inexistentes. Además la condición `2 <= $this->targetVersion` está hard-codeada. (Solapa con el ámbito de arquitectura; se deja a Qwen la valoración de diseño.)

Required correction (recomendada):

Verificar la existencia de las tres tablas tras `install()` antes de actualizar la opción.

---

### CODE-008 — Métricas del quality gate desactualizadas en `implementation-status.md`

Severity: Info
Blocking: No
Classification: Documentation Mismatch
Status: NEW

Description:

El estado reporta `Unit Tests: PASS — 15 tests, 19 assertions`; la ejecución del auditor sobre el working tree da `16 tests, 21 assertions`. También afirma a la vez "active integration test pending explicit fixture authorization" (sección Dependencies) e "Integration Tests: PASS". Adicionalmente, `spec.md` contiene dos casos con el ID `EC-010` (refund parcial y dos items del mismo producto); no afecta la implementación, pero rompe la trazabilidad por ID. Se corrige al actualizar la documentación por CODE-001/CODE-002.

---

## 6. Findings de Rondas Anteriores

No aplica (primera ronda de SPEC-003).

---

## 7. Observaciones Positivas (sin acción)

- Hooks verificados contra WooCommerce 11.1.1: `woocommerce_order_status_{to}` recibe `($order_id, $order, $status_transition)`, por lo que `grantCourses(int, ?object)` recibe el pedido. Las excepciones del listener quedan contenidas por el `try/catch` de `WC_Order::status_transition()` (se registran como nota "Error during status transition"), así que un fallo de Kaanbal no rompe el cambio de estado del pedido (AC-004 / RF-004).
- Cada curso se concede en su propia transacción (`matrícula + fuente`), evitando el estado "fuente sin matrícula" descrito en `plan.md §19`.
- `kaanbal_student_enrolled` solo se dispara para matrículas nuevas o reactivadas; reprocesar no lo duplica (`plan.md §18`).
- Variaciones: `get_product_id()` devuelve el producto padre, conforme a la decisión de `plan.md §9`.
- Todos los queries usan `$wpdb->prepare()`; los IDs de `$_POST` pasan por `absint` y validación de tipo de post.

---

## 8. Validación Ejecutada

El shell del equipo del usuario no tiene PHP; se copió el plugin (sin `.git`) al entorno del auditor y se ejecutó ahí con PHP 8.4.21:

```text
find src tests -name '*.php' | xargs -n1 php -l
vendor/bin/phpunit --configuration phpunit.xml.dist
vendor/bin/phpcs --warning-severity=0 --standard=phpcs.xml.dist
vendor/bin/phpstan analyse --configuration=phpstan.neon --memory-limit=512M
composer validate --strict
```

Resultados:

```text
lint:              sin errores de sintaxis
phpunit:           OK (16 tests, 21 assertions)
phpcs:             sin errores
phpstan:           [OK] No errors
composer validate: ./composer.json is valid
```

Limitaciones ambientales:

`Verification status: BLOCKED BY ENVIRONMENT` para `tests/Integration/woocommerce-enrollment.php`: requiere la base de datos MySQL local (`DB_HOST=localhost`, BD `tutor`) con WordPress + WooCommerce activos, inaccesible desde el entorno del auditor. El resultado de integración se evaluó por lectura estática del fixture y del código; no se reprodujo. Tampoco se verificó manualmente el metabox en wp-admin (pendiente también según `implementation-status.md`).

---

## 9. Conclusión

Verdict:

`FAIL`

Blocking findings:

- `CODE-001` — No existe candidato de auditoría estable; el estado declara `Ready for audit: Yes` con commit inexistente.
- `CODE-002` — Casos obligatorios (bundle, productos solapados en un pedido, producto sin cursos, curso eliminado) sin test, pese a `TASK-026: Done` e `INTEGRATION-TESTED`.

Non-blocking findings:

- `CODE-003` — Metabox borra asociaciones a cursos no publicados (Medium, se recomienda corregir junto con los blockers).
- `CODE-004` — `revoked_at` sobrescrito al re-conceder.
- `CODE-005` — Guest order sin log/nota.
- `CODE-006` — Capability de AC-019 no probada aislada.
- `CODE-007` — Versión de esquema marcada aunque falle la migración.
- `CODE-008` — Métricas y referencias documentales desactualizadas.

Comentario final:

No se encontraron defectos de lógica que contradigan la SPEC: la implementación parece cumplir los 21 ACs por inspección. El `FAIL` es de evidencia y de proceso. Tras commitear un candidato estable y completar los cuatro casos de test faltantes, se espera que la siguiente pasada pueda cerrar en `PASS WITH RECOMMENDATIONS`. Se sugiere a Codex atender también `CODE-003` en la misma remediación por su impacto comercial.
