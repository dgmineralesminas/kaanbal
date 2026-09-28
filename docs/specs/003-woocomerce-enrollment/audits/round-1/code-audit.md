# Auditoría de Código y Cumplimiento

SPEC: `SPEC-003 — WooCommerce Enrollment`
Ronda: `1` (reabierta contra un commit estable, tras el `FAIL` de la primera pasada)
Auditor: Claude Code
Commit auditado: `481c4574f37abde80fc75d58f63d7ab4e3a84ff5` (branch `feature/spec-003-woocommerce-enrollment`)
Fecha: `2026-09-28`

Verdict: `PASS WITH RECOMMENDATIONS`

Audit completeness: `Complete`

## 0. Nota sobre la reapertura de esta ronda

La primera pasada de esta ronda 1 (commit `091fa41`, contra el working tree sin commitear sobre `001496c`) terminó en `FAIL` por dos findings bloqueantes: `CODE-001` (sin candidato estable) y `CODE-002` (casos obligatorios sin test pese a `TASK-026: Done`). También registró seis findings no bloqueantes (`CODE-003` a `CODE-008`).

Por instrucción explícita del humano, la remediación de esos findings **la realizó el mismo agente que audita** (Claude Code), en los commits `3adde8c`, `d2e4a77`, `8e3b9a3` y `481c457`. Esto se aparta del reparto habitual (Codex remedia, Claude Code audita), así que esta reapertura no es independiente respecto de su propia remediación. Para compensarlo:

- cada corrección se verificó con pruebas de mutación: se rompió deliberadamente la regla corregida y se comprobó que la prueba falla en la aserción esperada;
- el contenido de `481c457` se comparó por hash (`git hash-object`) con la copia probada: 42/42 archivos idénticos;
- se recomienda que Qwen y Mimo auditen `481c457` de forma independiente, como exige `audit-standard.md §3`.

Este archivo se actualiza in situ (misma ronda 1) siguiendo el precedente de SPEC-002.

## 1. Resumen

Precondición cumplida: `implementation-status.md` declara `Status: Ready for audit`, `Ready for audit: Yes`, `Current audit round: 1` y `Current implementation commit: 481c457…`. El commit existe en `feature/spec-003-woocommerce-enrollment`, con `41ec818` encima (solo `implementation-status.md`), y el working tree está limpio.

La implementación satisface los 21 Acceptance Criteria y los 21 escenarios Gherkin, ahora con evidencia ejecutada: las tres pruebas de integración se corrieron contra una copia desechable del sitio local (WordPress 7.1.2, WooCommerce 11.1.1, MariaDB). Los dos findings bloqueantes están resueltos. De los no bloqueantes, cinco están resueltos y `CODE-008` queda parcialmente resuelto, porque su parte restante vive en `spec.md` (documento protegido) y requiere decisión humana.

Durante la remediación se detectaron y corrigieron dos defectos adicionales de las pruebas: `CODE-009` (regresión en la prueba de integración de SPEC-001) y `CODE-010` (contaminación del fixture vía `$_POST`).

Tasks revisadas: 37
Acceptance Criteria revisados: 21
Escenarios Gherkin revisados: 21

Blocking findings: 0 (2 de la pasada anterior: RESOLVED)
Non-blocking findings: 1 abierto (`CODE-008`, PARTIALLY RESOLVED)

---

## 2. Alcance Auditado

Documentos revisados:

- `spec.md`, `plan.md`, `feature.feature`, `tasks.md`, `implementation-status.md` de SPEC-003
- `docs/audit-standard.md`, `docs/agents.md`

Código revisado en `481c457` (íntegro): `src/Bootstrap/{Plugin,Activator,Version}.php`, `src/Shared/Database/{SchemaManager,SchemaMigration,EnrollmentSchemaMigration}.php`, `src/Enrollment/Application/{EnrollmentService,GrantCoursesFromOrder}.php`, `src/Enrollment/Infrastructure/{EnrollmentRepository,EnrollmentSourceRepository,ProductCourseRepository}.php`, `src/WooCommerce/WooCommerceModule.php`, `src/WooCommerce/Infrastructure/WooCommerceOrderAdapter.php`, `src/WooCommerce/Presentation/Admin/ProductCourseMetaBox.php`.

Pruebas revisadas y ejecutadas: `tests/Unit/*` (18 tests), `tests/Integration/wordpress-lifecycle.php`, `tests/Integration/courses-curriculum.php`, `tests/Integration/woocommerce-enrollment.php`.

Git: `git status` limpio; `git log` confirma la cadena `6e6b9fb → 3adde8c → 091fa41 → ee41002 → d2e4a77 → 60e07d1 → 8e3b9a3 → be46186 → 481c457 → 41ec818`.

---

## 3. Resolución de Findings de la Pasada Anterior

| Finding | Tipo | Estado | Commit |
|---|---|---|---|
| CODE-001 | Blocking | **RESOLVED** | `3adde8c`, `ee41002` |
| CODE-002 | Blocking | **RESOLVED** | `d2e4a77` |
| CODE-003 | Non-blocking | **RESOLVED** | `8e3b9a3` |
| CODE-004 | Non-blocking | **RESOLVED** | `481c457` |
| CODE-005 | Non-blocking | **RESOLVED** | `481c457` |
| CODE-006 | Non-blocking | **RESOLVED** | `481c457` |
| CODE-007 | Non-blocking | **RESOLVED** | `481c457` |
| CODE-008 | Non-blocking | **PARTIALLY RESOLVED** | `ee41002`, `60e07d1`, `41ec818` |

### CODE-001 — RESOLVED

Evidence: la implementación está en `feature/spec-003-woocommerce-enrollment`, rama propia creada desde SPEC-002. El cambio pendiente del audit de SPEC-002 se commiteó aparte (`6e6b9fb`), sobre la rama de SPEC-002. `implementation-status.md` referencia un hash que existe; `TASK-035` y `TASK-037` están en `Done`; working tree limpio.

### CODE-002 — RESOLVED

Evidence: `tests/Integration/woocommerce-enrollment.php` cubre ahora:

- bundle: 1 producto → 3 cursos → 3 matrículas (AC-008);
- dos productos del mismo pedido que conceden el mismo curso → 1 matrícula, 2 fuentes, estable tras `completed` (AC-009);
- producto sin cursos → sin matrícula y el pedido llega a `completed` (AC-004);
- curso en papelera y curso borrado → sin matrícula (EC-003);
- columnas `order_id`, `product_id` y `order_item_id` de cada fuente (AC-007).

Mutaciones detectadas: conceder solo el primer curso de cada producto, omitir `isValidCourse()`, procesar solo el primer item y guardar `order_item_id = 0`.

### CODE-003 — RESOLVED

Evidence: `ProductCourseMetaBox::render()` lista los estados `publish`, `future`, `draft`, `pending` y `private`, más cualquier curso ya asociado (incluso en papelera), con etiqueta de estado. El formulario envía `kaanbal_listed_course_ids[]`; `ProductCourseRepository::syncCourses()` solo desasocia cursos incluidos en esa lista. Un curso en papelera puede seguir asociado, pero no asociarse de nuevo. Las pruebas renderizan el metabox, extraen los inputs del HTML y los envían como un navegador. Mutaciones detectadas: listar solo `publish`, ignorar la lista gestionada, permitir asociar un curso en papelera y ocultar asociaciones en papelera. Con el código anterior la prueba falla en `Saving the product form dropped associations to unpublished courses.`

### CODE-004 — RESOLVED

Evidence: `ON DUPLICATE KEY UPDATE granted_at = IF(revoked_at IS NULL, granted_at, %s), revoked_at = NULL`. Probado en ambos sentidos: re-conceder una fuente revocada actualiza `granted_at`; reprocesar una fuente vigente lo conserva. La revocación intermedia queda visible en el historial de estados del pedido WooCommerce.

### CODE-005 — RESOLVED

Evidence: `GrantCoursesFromOrder::grant()` devuelve `GRANTED | NOTHING_TO_GRANT | GUEST_ORDER_WITH_COURSES`. Para un pedido de invitado con cursos, `WooCommerceModule` añade una nota privada una sola vez, controlada por la meta `_kaanbal_guest_order_notice`. La prueba verifica que hay exactamente una nota tras `processing` + `completed`, y que seguir sus instrucciones (asignar el cliente y volver a `processing`) concede el acceso. La nota se añade desde la capa WooCommerce, no desde el servicio de aplicación.

### CODE-006 — RESOLVED

Evidence: nuevo caso con un `subscriber` autenticado y un nonce válido generado para él; la asociación no cambia. Si se elimina `current_user_can('edit_post', …)` de `canSave()`, la prueba falla.

### CODE-007 — RESOLVED

Evidence: `SchemaManager` ya no tiene el `2 <=` fijo y guarda la versión solo después de `install()`. `EnrollmentSchemaMigration` comprueba con `SHOW TABLES LIKE` que existen las tres tablas y lanza una excepción si falta alguna. `Plugin::boot()` convierte esa excepción en un aviso de administración en vez de un error fatal. Dos tests unitarios nuevos (18 tests / 26 assertions) y una verificación real: sin privilegio `CREATE`, el sitio carga, muestra el aviso y `kaanbal_db_version` sigue en `1` en requests sucesivos; al restaurar el privilegio, el siguiente request crea las tablas y guarda `2`.

### CODE-008 — PARTIALLY RESOLVED

Evidence: cifras del quality gate actualizadas (18/26) y eliminada la contradicción sobre la prueba de integración en `Dependencies`.

Pendiente: `spec.md` contiene dos casos con el ID `EC-010` (refund parcial; dos items del mismo producto). `spec.md` es un documento protegido y aprobado, así que el renumerado corresponde al humano. No afecta la implementación: ambos casos están implementados y el de refund parcial está probado.

---

## 4. Findings Nuevos (detectados y resueltos durante la remediación)

### CODE-009 — La prueba de integración de SPEC-001 fallaba tras el cambio de esquema

Severity: Medium
Blocking: No (resuelto antes de esta reapertura)
Classification: Regression
Status: RESOLVED (`d2e4a77`)

Affected files:
- `tests/Integration/wordpress-lifecycle.php`

Description: SPEC-003 subió `Version::DATABASE_SCHEMA` de 1 a 2 y actualizó `tests/Unit/LifecycleTest.php`, pero no la prueba de integración de SPEC-001, que seguía esperando `1` y fallaba con `Kaanbal activation did not persist the expected schema version.` El estado anterior no lo detectó porque solo reportaba la prueba de WooCommerce.

Evidence: ahora compara contra `Version::DATABASE_SCHEMA` y pasa con WooCommerce desactivado, lo que además aporta evidencia de integración para AC-018.

### CODE-010 — El fixture asociaba cursos a productos sin intención

Severity: Low
Blocking: No (resuelto antes de esta reapertura)
Classification: Test Gap
Status: RESOLVED (`8e3b9a3`)

Affected files:
- `tests/Integration/woocommerce-enrollment.php`

Description: tras las pruebas del metabox, `$_POST` quedaba con un nonce válido y un curso. Cada `wp_insert_post` posterior de un producto disparaba `save_post_product` y el hook real del plugin asociaba ese curso a cada producto nuevo. Los datos del fixture no eran los que aparentaban. La lógica de producción se comportaba correctamente con esa entrada.

Evidence: `$_POST = array()` tras las pruebas de administración; el fixture deja 0 filas en las tres tablas y 0 posts de prueba tras cada ejecución.

---

## 5. Trazabilidad

| AC | Scenario | Implemented | Verified | Evidence |
|---|---|---|---|---|
| AC-001 | SC-001 | Yes | Yes | N productos → 1 curso y 1 producto → 3 cursos (fixture). |
| AC-002 | SC-001 | Yes | Yes | `UNIQUE product_course` + `INSERT IGNORE`; `syncCourses()` deduplica. |
| AC-003 | SC-002 | Yes | Yes | Envío real del formulario del metabox; verificación manual humana sobre `8e3b9a3`. |
| AC-004 | SC-004 | Yes | Yes | Pedido con producto sin cursos: 0 matrículas nuevas, pedido `completed`. |
| AC-005 | SC-005 | Yes | Yes | Matrícula `active` creada. |
| AC-006 | SC-005 | Yes | Yes | `UNIQUE user_course`; varios pedidos → 1 matrícula. |
| AC-007 | SC-006 | Yes | Yes | `assertSourceTraceability()` sobre order/product/order item. |
| AC-008 | SC-007 | Yes | Yes | Bundle de 3 cursos. |
| AC-009 | SC-008 | Yes | Yes | 2 productos, mismo pedido, mismo curso → 1 matrícula, 2 fuentes. |
| AC-010 | SC-009 | Yes | Yes | Reprocesamiento y processing→completed sin duplicados. |
| AC-011 | SC-010 | Yes | Yes | Hook `processing`. |
| AC-012 | SC-011 | Yes | Yes | Hook `completed`, sin duplicados. |
| AC-013 | SC-012 | Yes | Yes | `cancelled` revoca fuentes. |
| AC-014 | SC-013 | Yes | Yes | `refunded` total revoca; refund parcial no. |
| AC-015 | SC-014 | Yes | Yes | Fuente alternativa mantiene `active`. |
| AC-016 | SC-015 | Yes | Yes | Sin fuentes válidas → `revoked`. |
| AC-017 | SC-016 | Yes | Yes | Reactivación sin segunda matrícula. |
| AC-018 | SC-017 | Yes | Yes | `WooCommerceModuleTest` + `wordpress-lifecycle` con WooCommerce inactivo. |
| AC-019 | SC-003 | Yes | Yes | Nonce inválido rechazado; nonce válido sin `edit_post` rechazado. |
| AC-020 | SC-018, SC-019 | Yes | Yes | Curso inexistente y post no-producto rechazados. |
| AC-021 | SC-021 | Yes | Yes (código) | Sin progreso, quizzes ni certificados. |

Edge cases: EC-001 (nota de pedido de invitado), EC-003 (curso en papelera o borrado), EC-005/006, EC-007/008, EC-009 y EC-010 (refund parcial) verificados por el fixture. EC-004 (producto eliminado) y EC-010 bis (dos items del mismo producto) verificados por inspección de código.

---

## 6. Verificación de Tasks

Las 37 tasks están en `Done` y el conteo de `implementation-status.md` (37/0/0/0) es consistente. `TASK-026` pasa a `PASS` (casos presentes), `TASK-035` a `PASS` (quality gate completo, ver §8), `TASK-036` a `PASS` (estado veraz salvo el punto de `spec.md` de CODE-008) y `TASK-037` a `PASS` (candidato estable). El resto sin cambios respecto a la primera pasada: `PASS`.

---

## 7. Recomendaciones

### CODE-008 (restante) — Renumerar el `EC-010` duplicado en `spec.md`

Severity: Info
Blocking: No
Classification: Documentation Mismatch

Renumerar "EC-010 — Dos items del mismo producto" (p. ej. a `EC-011`) mediante decisión humana. Es un cambio de identificador, sin cambio de requisito.

Fuera del ámbito de este auditor, para Qwen: `GrantCoursesFromOrder` (capa `Enrollment\Application`) sigue dependiendo del tipo concreto `WooCommerce\Infrastructure\WooCommerceOrderAdapter`. Queda anotado solo como contexto.

---

## 8. Validación Ejecutada

Entorno: copia desechable del sitio local (WordPress 7.1.2, WooCommerce 11.1.1, Kaanbal en `481c457`, verificado por hash) sobre MariaDB, PHP 8.4.21. No se tocó la base de datos del usuario.

```text
find src tests -name '*.php' | xargs -n1 php -l
vendor/bin/phpunit --configuration phpunit.xml.dist
KAANBAL_WP_PATH=… php tests/Integration/woocommerce-enrollment.php
KAANBAL_WP_PATH=… php tests/Integration/courses-curriculum.php
KAANBAL_WP_PATH=… php tests/Integration/wordpress-lifecycle.php     (WooCommerce desactivado)
vendor/bin/phpcs --warning-severity=0 --standard=phpcs.xml.dist
vendor/bin/phpstan analyse --configuration=phpstan.neon --memory-limit=512M
composer validate --strict
```

Resultados:

```text
lint:                     sin errores
phpunit:                  OK (18 tests, 26 assertions)
woocommerce-enrollment:   PASS (fixture deja 0 filas y 0 posts)
courses-curriculum:       PASS
wordpress-lifecycle:      PASS
phpcs:                    sin errores
phpstan:                  [OK] No errors
composer validate:        valid
```

Pruebas de mutación (cada una restaurada tras la ejecución): 14 mutaciones sobre las reglas corregidas en CODE-002 a CODE-007; las 14 hicieron fallar la prueba correspondiente con el mensaje esperado.

Limitaciones ambientales: ninguna material. La verificación manual del metabox la realizó el humano sobre `8e3b9a3`; la nota de pedido de invitado y el aviso de fallo de esquema (`481c457`) están cubiertos por pruebas automáticas y una simulación sin privilegio `CREATE`, pero aún no por revisión manual.

---

## 9. Conclusión

Verdict:

`PASS WITH RECOMMENDATIONS`

Blocking findings:

- None (`CODE-001` y `CODE-002`: RESOLVED)

Non-blocking findings:

- `CODE-008` — PARTIALLY RESOLVED: renumerar el `EC-010` duplicado en `spec.md` (decisión humana).

Comentario final:

SPEC-003 cumple lo aprobado con evidencia ejecutada y trazable. Como esta reapertura la realizó el mismo agente que remedió, el cierre de la ronda 1 debe apoyarse en las auditorías independientes de Qwen (arquitectura) y Mimo (seguridad) sobre `481c457`.
