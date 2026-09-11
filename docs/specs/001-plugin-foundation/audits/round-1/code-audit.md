# Auditoría de Código y Cumplimiento

SPEC: `SPEC-001 — Plugin Foundation`
Ronda: `1`
Auditor: Claude Code
Commit auditado: `a9e91ec11981c1eaa6eeed8ad1b5fcc20c285dd0` (branch `feature/spec-001-plugin-foundation`)
Fecha: `2026-09-11`

Verdict: `PASS WITH RECOMMENDATIONS`

Audit completeness: `Complete` (con limitaciones ambientales documentadas en la sección 8)

## 1. Resumen

`implementation-status.md` declara `Ready for audit: Yes`, `Current audit round: 1` y `SPEC approved for implementation: Yes`. El working tree del branch `feature/spec-001-plugin-foundation` está limpio (`git status` sin cambios pendientes) y el commit candidato es `a9e91ec` (`feat: implement SPEC-001 plugin foundation`). Se audita exactamente esa revisión.

La implementación cubre correctamente el alcance aprobado de SPEC-001: bootstrap mínimo (`kaanbal.php`), autoload PSR-4 bajo `Kaanbal\`, lifecycle de activación/desactivación idempotente, verificación de requisitos, versionado de plugin y de schema separados, Schema Manager idempotente, Service Registry, e infraestructura de testing (PHPUnit + un script de integración real contra WordPress). No se detectó funcionalidad fuera de alcance (no hay cursos, matrícula, WooCommerce funcional, etc.), ni criterios de aceptación protegidos modificados para ajustarse al código.

No se encontró ningún finding bloqueante. Se registran 2 findings no bloqueantes (uno de documentación, uno de cobertura de pruebas) y 4 recomendaciones menores.

Tasks revisadas: 22
Acceptance Criteria revisados: 15
Escenarios Gherkin revisados: 13

Blocking findings: 0
Non-blocking findings: 2 (más 4 recomendaciones)

## 2. Alcance Auditado

Documentos revisados:

- `README.md`
- `docs/agents.md`
- `docs/development-workflow.md`
- `docs/definition-of-done.md`
- `docs/audit-standard.md`
- `docs/project-context.md`
- `docs/architecture.md`
- `docs/data-model.md`
- `docs/testing-strategy.md`
- `docs/templates/code-audit-template.md`
- `docs/specs/001-plugin-foundation/spec.md`
- `docs/specs/001-plugin-foundation/plan.md`
- `docs/specs/001-plugin-foundation/feature.feature`
- `docs/specs/001-plugin-foundation/tasks.md`
- `docs/specs/001-plugin-foundation/implementation-status.md`

Código relevante revisado:

- `kaanbal.php`
- `composer.json`, `composer.lock` (metadata), `phpunit.xml.dist`, `phpcs.xml.dist`, `phpstan.neon`
- `src/Bootstrap/Plugin.php`
- `src/Bootstrap/Activator.php`
- `src/Bootstrap/Deactivator.php`
- `src/Bootstrap/Requirements.php`
- `src/Bootstrap/RequirementResult.php`
- `src/Bootstrap/ServiceRegistry.php`
- `src/Bootstrap/BootableService.php`
- `src/Bootstrap/Version.php`
- `src/Shared/Database/SchemaManager.php`
- `src/Shared/Database/OptionStore.php`
- `src/Shared/Database/WordPressOptionStore.php`
- `.gitignore` (verificación de que `vendor/` no se versiona y `composer.lock` sí)
- `git log` / `git show a9e91ec` (diff completo del commit candidato, incluyendo el cambio de una línea en `spec.md`)

Pruebas revisadas:

- `tests/bootstrap.php`
- `tests/Unit/RequirementsTest.php`
- `tests/Unit/SchemaManagerTest.php`
- `tests/Unit/ServiceRegistryTest.php`
- `tests/Unit/LifecycleTest.php`
- `tests/Support/InMemoryOptionStore.php`
- `tests/Integration/wordpress-lifecycle.php`

No se ejecutaron pruebas en este entorno de auditoría (ver sección 8: `php` y `composer` no están disponibles en el sandbox del auditor). La verificación de comportamiento de pruebas se basó en lectura estática de assertions y en los resultados documentados por Codex en `implementation-status.md`.

## 3. Trazabilidad

| Acceptance Criterion | Scenario | Task | Implemented | Verified | Evidence |
|---|---|---|---|---|---|
| AC-001 | SC-001 | TASK-003 | Yes | Yes | `kaanbal.php` header (Plugin Name/Version/Text Domain); `tests/Integration/wordpress-lifecycle.php` comprueba `get_plugins()['kaanbal/kaanbal.php']` y su `Version`. |
| AC-002 | SC-001 | TASK-003, TASK-006 | Yes | Yes | `Activator::activate()`; `LifecycleTest::testActivationStoresSchemaVersionOnlyOnce`; integration test invoca `Activator::activate()` sobre WordPress real. |
| AC-003 | SC-002 | TASK-006, TASK-013 | Yes | Yes | `LifecycleTest::testActivationStoresSchemaVersionOnlyOnce` llama `activate()` dos veces y verifica una sola opción persistida; integration test repite la activación en WordPress real. |
| AC-004 | SC-003 | TASK-007, TASK-014 | Yes | Yes | `Deactivator::deactivate()` es intencionalmente no destructivo (comentario explícito); `LifecycleTest::testDeactivationPreservesTheInstalledSchemaVersion`; integration test confirma que `kaanbal_db_version` sobrevive a la desactivación. |
| AC-005 | SC-004 | TASK-004 | Yes | Yes | `Plugin::boot()` se invoca al final de `kaanbal.php`; el script de integración carga `kaanbal.php` contra WordPress real sin error fatal, lo que ejercita el boot completo. |
| AC-006 | SC-005 | TASK-002 | Yes | Yes | `composer.json` (`autoload.psr-4: "Kaanbal\\": "src/"`), `composer.lock` presente; todas las clases de prueba usan `Kaanbal\...` sin `require` manual. |
| AC-007 | SC-006 | TASK-003 | Yes | Partial | `defined('ABSPATH') || exit;` como primera línea ejecutable de `kaanbal.php` (revisión estática confirma que es correcto). Evidencia de ejecución es solo manual (ver CODE-REC-003), no automatizada. |
| AC-008 | SC-007 | TASK-005, TASK-015 | Yes | Yes | Integration test verifica explícitamente `class_exists('WooCommerce') === false` y que la carga/activación se completa sin error; `Requirements` no exige WooCommerce. |
| AC-009 | SC-008 | TASK-005 | Yes | Partial | `Requirements::evaluate()` cubierto por `RequirementsTest` (caso compatible e incompatible). El comportamiento integrado de `Plugin::boot()` ante un entorno incompatible (`registerRequirementsNotice()`, no arrancar `ServiceRegistry`) **no** está cubierto por ninguna prueba automatizada — ver CODE-002. |
| AC-010 | SC-009 | TASK-008 | Yes | Yes | `Version::PLUGIN = '0.1.0'` como fuente técnica; usado para definir `KAANBAL_VERSION` en `kaanbal.php`. Ver CODE-REC-004 sobre la duplicación inevitable con el docblock de cabecera de WordPress. |
| AC-011 | SC-010 | TASK-008, TASK-009 | Yes | Yes | `SchemaManager::installedVersion()` / `installOrUpgrade()`; `SchemaManagerTest::testItStoresTheSchemaVersionOnFirstInstall`. |
| AC-012 | SC-010 | TASK-006, TASK-009, TASK-013 | Yes | Yes | `SchemaManagerTest::testRepeatingInstallationDoesNotWriteAgain` (una sola escritura tras dos llamadas); `LifecycleTest` confirma lo mismo a nivel de `Activator`. |
| AC-013 | SC-011 | TASK-010 | Yes | Yes (aislado) | `ServiceRegistry::add()/registerAll()`; `ServiceRegistryTest::testItRegistersServicesInTheOrderAdded`. `Plugin::boot()` integra el registro, aunque todavía no añade ningún `BootableService` real (esperado: SPEC-001 no define módulos LMS) — ver CODE-REC-001. |
| AC-014 | SC-012 | TASK-011, TASK-012 | Yes | Yes | `phpunit.xml.dist` + 7 tests/11 assertions documentados; `tests/Integration/wordpress-lifecycle.php` ejecutable vía `composer test:integration`. |
| AC-015 | SC-013 | TASK-016 – TASK-020 | Yes | Yes (documentado) | `composer.json` scripts (`lint`, `test`, `cs`, `analyse`, `quality`); resultados registrados en `implementation-status.md` como PASS. No se re-ejecutaron en este sandbox (BLOCKED BY ENVIRONMENT, ver sección 8). |

## 4. Verificación de Tasks

Las 22 tasks de `tasks.md` están marcadas `Status: Done`. Se verificó, para cada una, que existe entregable real correspondiente (no hay tasks "Done" sin código/prueba asociada):

| Task | Status documentado | Resultado de auditoría | Observaciones |
|---|---|---|---|
| TASK-001 | Done | PASS | Entorno registrado en `implementation-status.md` (PHP 8.4.20, WP 7.1, Composer 2.9.5, Git 2.39.5, WooCommerce no instalado). |
| TASK-002 | Done | PASS | `composer.json` con PSR-4 `Kaanbal\\` → `src/`. |
| TASK-003 | Done | PASS | `kaanbal.php` mínimo, guard de acceso directo, metadata, hooks de activación/desactivación, arranque de `Plugin`. |
| TASK-004 | Done | PASS | `Plugin::boot()` centraliza inicialización; `kaanbal.php` no contiene lógica de negocio. |
| TASK-005 | Done | PASS (con recomendación) | `Requirements`/`RequirementResult` implementados y probados en aislamiento; falta prueba del comportamiento integrado (CODE-002). |
| TASK-006 | Done | PASS | `Activator` + `SchemaManager`, idempotencia probada. |
| TASK-007 | Done | PASS | `Deactivator` mínimo, no destructivo. |
| TASK-008 | Done | PASS | `Version::PLUGIN` / `Version::DATABASE_SCHEMA` como fuentes separadas. |
| TASK-009 | Done | PASS | `SchemaManager` idempotente. |
| TASK-010 | Done | PASS | `ServiceRegistry` + integración explícita en `Plugin::boot()`. |
| TASK-011 | Done | PASS | PHPUnit configurado, `tests/bootstrap.php`, 4 clases de test unitarias. |
| TASK-012 | Done | PASS | `tests/Integration/wordpress-lifecycle.php`, ejecutable contra WordPress real vía `KAANBAL_WP_PATH`. |
| TASK-013 | Done | PASS | Cubierta por `LifecycleTest` + integration test. |
| TASK-014 | Done | PASS | Cubierta por `LifecycleTest::testDeactivationPreservesTheInstalledSchemaVersion` + integration test. |
| TASK-015 | Done | PASS | Cubierta por integration test (`class_exists('WooCommerce')` false). |
| TASK-016 | Done | PASS | `composer lint` (`php -l` sobre `src`/`tests`). |
| TASK-017 | Done | PASS | `phpcs.xml.dist` (PSR12 + sniffs de seguridad WP); resultado documentado PASS. |
| TASK-018 | Done | PASS | `phpstan.neon` (nivel 5, stubs de WordPress); resultado documentado PASS, ejecutado fuera del sandbox por limitación de socket. |
| TASK-019 | Done | PASS | Scripts Composer (`lint`, `test`, `cs`, `analyse`, `quality`) documentados y consistentes. |
| TASK-020 | Done | PASS | `implementation-status.md` documenta ejecución de la suite completa. |
| TASK-021 | Done | PASS (con recomendación) | `implementation-status.md` actualizado, pero su conteo agregado de tasks no coincide con `tasks.md` (CODE-001). |
| TASK-022 | Done | PASS | `Status: Ready for audit`, `Ready for audit: Yes`, `Current audit round: 1` presentes y consistentes con el resto del documento salvo por CODE-001. |

## 5. Findings

### CODE-001 — El conteo agregado de tasks en `implementation-status.md` no coincide con `tasks.md`

Severity: Low
Blocking: No
Classification: Documentation Mismatch
Status: NEW

Affected requirement:
Definition of Done §6.1 / §6.6 (el documento de estado debe reflejar la realidad)

Affected task:
TASK-021

Affected files:
- docs/specs/001-plugin-foundation/implementation-status.md
- docs/specs/001-plugin-foundation/tasks.md

Description:

`implementation-status.md` reporta en la sección "Tasks": `Total: 22`, `Done: 21`, `In Progress: 1`, `Pending: 0`. Sin embargo, en `tasks.md` las 22 tasks están individualmente marcadas `Status: Done` (incluida TASK-022). No hay ninguna task identificada como "In Progress" en el documento fuente de tasks.

Evidence:

`implementation-status.md`, sección "Tasks": "Total: 22 / Done: 21 / In Progress: 1 / Pending: 0 / Blocked: 0 / Not Applicable: 0". `tasks.md`: cada una de las 22 tasks (TASK-001 a TASK-022) tiene la línea "Status: Done" inmediatamente después de su encabezado.

Expected:

El conteo agregado de `implementation-status.md` debe coincidir exactamente con los estados individuales de `tasks.md` (22 Done, 0 In Progress), o bien `tasks.md` debe reflejar cuál task sigue en progreso si eso es lo real.

Actual:

Discrepancia numérica entre ambos documentos.

Impact:

Bajo: la revisión task-por-task de esta auditoría (sección 4) no encontró evidencia de que exista trabajo real pendiente — cada task tiene código y/o prueba asociada. El riesgo es de confiabilidad documental, no de funcionalidad faltante.

Required correction:

Corregir el conteo agregado en `implementation-status.md` para que sea consistente con `tasks.md` antes de declarar el round como cerrado.

---

### CODE-002 — El comportamiento de `Plugin::boot()` ante un entorno incompatible (AC-009/SC-008) no tiene cobertura de pruebas automatizadas; la documentación de verificación es más amplia de lo realmente probado

Severity: Medium
Blocking: No
Classification: Test Gap

Affected requirement:
AC-009

Affected scenario:
SC-008 — "Detectar un entorno incompatible"

Affected task:
TASK-005

Affected files:
- src/Bootstrap/Plugin.php
- tests/Unit/RequirementsTest.php
- docs/specs/001-plugin-foundation/implementation-status.md

Description:

`Requirements::evaluate()` está correctamente probado en aislamiento para el caso compatible y el incompatible (`RequirementsTest`). Sin embargo, el comportamiento que describe realmente AC-009/SC-008 —que ante un entorno incompatible Kaanbal "no debe continuar silenciosamente"— vive en `Plugin::boot()` (registra un admin notice vía `registerRequirementsNotice()` y evita arrancar el `ServiceRegistry`). Ninguna prueba, unitaria ni de integración, ejercita esa rama de `Plugin::boot()`. El entorno real usado para la prueba de integración (`tests/Integration/wordpress-lifecycle.php`) cumple los requisitos mínimos, por lo que esa prueba solo cubre el camino compatible.

Evidence:

`grep -r registerRequirementsNotice tests/` no arroja resultados; `tests/Unit/RequirementsTest.php` solo invoca `Requirements::evaluate()` directamente, nunca `Plugin::boot()`. `implementation-status.md` declara "AC-009: IMPLEMENTED AND VERIFIED — requirements unit tests pass", lo cual es exacto únicamente para la lógica aislada de `Requirements`, no para el comportamiento integrado que el criterio de aceptación describe.

Expected:

Evidencia ejecutable (unit o integración) de que, ante un `RequirementResult` incompatible, `Plugin::boot()` registra el notice, no lanza un error fatal y no inicializa `ServiceRegistry`; o, alternativamente, documentación más precisa que distinga "lógica de Requirements verificada por test" de "comportamiento de boot ante incompatibilidad revisado estáticamente, no ejecutado".

Actual:

Solo la lógica pura de `Requirements::evaluate()` se ejecuta en pruebas. El comportamiento integrado fue revisado estáticamente por este auditor (el código es simple y, por lectura, correcto) pero no tiene evidencia de ejecución.

Impact:

Riesgo práctico bajo dado lo simple del código, pero el escenario Gherkin explícitamente listado (SC-008) para AC-009 carece de protección de regresión automatizada, y la declaración "VERIFIED" en `implementation-status.md` es más amplia de lo que realmente se ejecutó.

Required correction:

No bloqueante para este round. Se recomienda, antes de cerrar SPEC-001, añadir una prueba que ejercite `Plugin::boot()` bajo un entorno simulado incompatible (por ejemplo, haciendo inyectable el `Requirements` o el resultado de compatibilidad) y ajustar la redacción de `implementation-status.md` para reflejar exactamente qué se ejecutó.

## 6. Findings de Rondas Anteriores

No aplica — esta es la primera ronda de auditoría (`Current audit round: 1`); `implementation-status.md` confirma "No audit round has been executed" antes de este reporte.

## 7. Recomendaciones

### CODE-REC-001 — `ServiceRegistry` no tiene todavía ningún `BootableService` real registrado en producción

Severity: Info
Blocking: No
Classification: Recommendation

Description:

`Plugin::boot()` crea un `ServiceRegistry` y llama `registerAll()`, pero nunca llama `add()` con un servicio concreto — en producción, la lista de servicios está vacía. Esto es esperado y correcto para SPEC-001 (todavía no existe ningún módulo LMS que registrar), y evita crear infraestructura ficticia con contenido simulado solo para "llenar" la task.

Reason:

Vale la pena dejarlo anotado para que las SPEC futuras (002 en adelante) efectivamente conecten servicios reales a través de este mecanismo, en vez de introducir un patrón de inicialización distinto.

---

### CODE-REC-002 — `SchemaManagerTest` no cubre un camino de actualización real de versión (v1 → v2)

Severity: Low
Blocking: No
Classification: Recommendation

Description:

Las pruebas actuales cubren instalación inicial (`testItStoresTheSchemaVersionOnFirstInstall`) y reinstalación idéntica (`testRepeatingInstallationDoesNotWriteAgain`), pero no un caso donde la versión instalada sea menor que un nuevo `targetVersion` distinto (p. ej. instalada=1, target=2), que es el escenario que `SchemaManager` deberá manejar cuando existan migraciones futuras.

Reason:

Fortalecería la confianza en que la infraestructura de migraciones (mencionada como objetivo de TASK-009 y `docs/architecture.md` §24-25) realmente soporta upgrades, no solo instalación/no-op.

---

### CODE-REC-003 — La guarda de acceso directo (AC-007) solo tiene evidencia manual

Severity: Low
Blocking: No
Classification: Recommendation

Description:

`implementation-status.md` documenta la verificación de `defined('ABSPATH') || exit;` como manual ("exits without output or error"). Es una comprobación barata de automatizar (invocar `kaanbal.php` vía CLI/subproceso sin definir `ABSPATH` y afirmar que no hay salida ni error fatal).

Reason:

Sustituiría una nota manual de una línea por una prueba de regresión reproducible, alineado con la preferencia del proyecto por automatizar cuando el costo es bajo (`docs/testing-strategy.md` §16-17).

---

### CODE-REC-004 — Duplicación de la versión del plugin entre el docblock de cabecera y `Version::PLUGIN`

Severity: Info
Blocking: No
Classification: Recommendation

Description:

El valor `0.1.0` aparece tanto en el comentario de cabecera de `kaanbal.php` (`Version: 0.1.0`, requerido por WordPress porque su parser de metadata no ejecuta PHP) como en `Kaanbal\Bootstrap\Version::PLUGIN`. Es una duplicación técnicamente inevitable dado cómo WordPress lee headers de plugin, pero no hay ninguna prueba que detecte si ambos valores llegaran a divergir.

Reason:

Una prueba simple que parsee el header del archivo principal y lo compare contra `Version::PLUGIN` protegería AC-010 ("fuente técnica única") de una futura desincronización silenciosa.

## 8. Validación Ejecutada

Comandos intentados por este auditor en su propio entorno (sandbox del dispositivo conectado, no el entorno de desarrollo de Daniel):

```text
php -l kaanbal.php          → "php: command not found" (exit 127)
php kaanbal.php             → "php: command not found" (exit 127)
which php composer          → no encontrados
composer -V                 → "composer: command not found" (exit 127)
```

Resultados:

```text
No fue posible ejecutar PHP, Composer, PHPUnit, PHPCS ni PHPStan en el entorno
de este auditor: ninguno de los binarios está disponible en el sandbox del
dispositivo conectado usado para esta auditoría.
```

Por lo tanto, esta auditoría se basó en:

1. Revisión estática completa del código fuente (`src/`), del bootstrap (`kaanbal.php`) y de la configuración de tooling (`composer.json`, `phpunit.xml.dist`, `phpcs.xml.dist`, `phpstan.neon`).
2. Lectura íntegra de cada archivo de prueba (`tests/Unit/*.php`, `tests/Integration/wordpress-lifecycle.php`, `tests/Support/*.php`, `tests/bootstrap.php`) para confirmar que las assertions son significativas (ninguna prueba del tipo `assertTrue(true)` o equivalente) y que realmente corresponden al comportamiento que dicen validar.
3. Inspección de `git log` / `git show a9e91ec` para confirmar el contenido exacto del commit candidato, incluyendo el único cambio fuera de `src/`/`tests/`/config: la línea `Status: Draft` → `Status: Ready for implementation` en `spec.md` (ver nota abajo).
4. Los resultados numéricos de ejecución (7 tests/11 assertions, PHPCS PASS, PHPStan PASS, integration test PASS) provienen de lo documentado por Codex en `implementation-status.md`; no fueron re-ejecutados por este auditor.

Limitaciones ambientales:

`Verification status: BLOCKED BY ENVIRONMENT` para la re-ejecución directa de `composer quality` / `composer test` / `composer test:integration` por parte de este auditor. Comando intentado: `php -l`, `composer -V` (ambos "command not found"). Lo que pudo revisarse estáticamente: la totalidad del código de producción y de pruebas, la configuración de todas las herramientas, y la consistencia entre lo declarado y el código real. Lo que queda sin verificación directa por este auditor: la ejecución real de la suite (se confía en el reporte de Codex) y la verificación manual en el admin de WordPress (listado de plugins, activación/desactivación vía UI, navegador integrado) — también documentada solo por Codex.

Nota adicional sobre el commit candidato: el diff de `a9e91ec` incluye un cambio de una línea en `docs/specs/001-plugin-foundation/spec.md` (`Status: Draft` → `Status: Ready for implementation`). `spec.md` está listado como archivo protegido en `docs/agents.md` §6. No se trata de un cambio a un criterio de aceptación ni de una ampliación de alcance, y `implementation-status.md` registra explícitamente "SPEC approved for implementation: Yes", por lo que se interpreta como el registro de una aprobación humana ya otorgada y no como una modificación unilateral del alcance. Se deja documentado como observación, no como finding, dado que no hay evidencia de que altere el significado de la SPEC.

## 9. Conclusión

Verdict:

`PASS WITH RECOMMENDATIONS`

Blocking findings:

- Ninguno.

Non-blocking findings:

- CODE-001 — Conteo agregado de tasks inconsistente en `implementation-status.md`.
- CODE-002 — Falta cobertura de prueba integrada para el comportamiento de `Plugin::boot()` ante entorno incompatible (AC-009/SC-008); la documentación de verificación es más amplia que lo realmente ejecutado.

Recomendaciones:

- CODE-REC-001, CODE-REC-002, CODE-REC-003, CODE-REC-004 (ver sección 7).

Comentario final:

La implementación de SPEC-001 cumple, con evidencia razonable, los 15 Acceptance Criteria y los 13 escenarios Gherkin dentro del alcance aprobado, sin señales de scope creep ni de modificación indebida de criterios protegidos. Los dos findings no bloqueantes son de naturaleza documental/cobertura y no representan comportamiento incorrecto observado. Desde el punto de vista de esta auditoría de requisitos e implementación, SPEC-001 puede avanzar a las auditorías de arquitectura (Qwen) y seguridad (Mimo) sobre el mismo commit `a9e91ec`.
