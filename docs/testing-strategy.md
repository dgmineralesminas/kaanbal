# Estrategia de Pruebas

## 1. Propósito

Este documento define la estrategia global de pruebas de Kaanbal.

Su objetivo es establecer:

- qué tipos de pruebas deben existir
- qué debe ejecutar Codex antes de auditoría
- cómo se relacionan las pruebas con los Acceptance Criteria
- qué evidencia debe existir para considerar una SPEC verificable
- cómo distinguir una prueba útil de una prueba superficial
- cómo manejar limitaciones ambientales

Este documento complementa:

- `docs/agents.md`
- `docs/development-workflow.md`
- `docs/definition-of-done.md`
- `docs/audit-standard.md`
- `docs/architecture.md`

---

## 2. Principio General

Las pruebas deben demostrar comportamiento real.

El objetivo no es maximizar el número de tests.

El objetivo es obtener evidencia suficiente de que:

```text
el requisito existe
→ fue implementado
→ se comporta correctamente
→ no rompe comportamiento previo relevante
```

---

## 3. Pirámide de Pruebas

Kaanbal utilizará diferentes niveles de validación.

Conceptualmente:

```text
                    Manual / E2E
                       /\
                      /  \
             Integration Tests
                  /        \
                 /          \
              Unit Tests
```

La mayor parte de la lógica aislable debe poder validarse con pruebas unitarias.

La integración con WordPress y WooCommerce requiere pruebas de integración.

Las pruebas manuales deben reservarse para comportamiento visual o escenarios que no aporten suficiente valor automatizándolos inicialmente.

---

## 4. Tipos de Pruebas

Tipos principales:

```text
Unit
Integration
Behavior / Acceptance
Regression
Manual verification
Static analysis
Coding standards
Build / syntax validation
```

No todas las SPEC requieren todos los tipos.

La estrategia debe ser proporcional al riesgo.

---

# 5. Unit Tests

Las pruebas unitarias validan lógica aislada.

Deben preferirse cuando la lógica no necesita WordPress completamente cargado.

Ejemplos futuros:

```text
calcular score de quiz
determinar si un curso puede completarse
resolver estado académico
validar límites de intentos
generar un folio
normalizar una fuente de video
```

Ejemplo conceptual:

```php
public function test_course_is_not_complete_when_quiz_is_pending(): void
{
    // ...
}
```

---

## 6. Qué Debe Evitar un Unit Test

Una prueba unitaria no debe:

- cargar WordPress completo sin necesidad
- acceder a base de datos real si la lógica puede probarse aislada
- depender de WooCommerce cuando solo se prueba lógica interna
- verificar detalles privados sin valor funcional
- existir únicamente para aumentar coverage

---

# 7. Integration Tests

Las pruebas de integración validan la interacción entre componentes reales.

Ejemplos:

```text
activar plugin
crear tablas
registrar CPTs
persistir matrícula
consultar progreso
procesar hooks WordPress
procesar eventos WooCommerce
```

Estas pruebas pueden requerir:

- WordPress test suite
- base de datos de pruebas
- WooCommerce instalado
- fixtures

---

## 8. WordPress Integration Tests

Deben utilizarse cuando se necesite comprobar:

- hooks
- filters
- CPT registration
- capabilities
- metadata
- REST endpoints
- AJAX
- lifecycle
- activación
- schema
- permisos

Ejemplo:

```text
Given Kaanbal is active
When WordPress initializes
Then the course post type is registered
```

---

## 9. WooCommerce Integration Tests

Deben utilizarse para comportamiento que dependa realmente de WooCommerce.

Ejemplos:

```text
pedido completado
→ resolver productos
→ resolver cursos
→ generar matrícula
```

También:

```text
mismo pedido procesado dos veces
→ no duplicar matrícula
```

No debe mockearse WooCommerce de forma tan agresiva que la prueba deje de verificar la integración real.

---

# 10. Behavior / Acceptance Tests

Los criterios de aceptación definidos en Gherkin deben tener evidencia.

No es obligatorio utilizar un runner Gherkin específico desde la primera versión.

`feature.feature` puede funcionar inicialmente como especificación ejecutable conceptualmente.

Cada escenario debe mapearse a:

- test automatizado
- test de integración
- validación reproducible
- evidencia manual justificada

---

## 11. Relación Gherkin → Tests

Ejemplo:

```gherkin
@AC-004
Scenario: Evitar matrícula duplicada
  Given un alumno ya tiene matrícula en el curso
  When el mismo pedido es procesado nuevamente
  Then no debe crearse una segunda matrícula
```

Puede mapearse a:

```text
EnrollmentWooCommerceTest::test_reprocessing_order_is_idempotent
```

---

# 12. Matriz de Trazabilidad

Cada SPEC debe permitir reconstruir:

```text
AC
→ Scenario
→ Task
→ Test
→ Código
```

Ejemplo:

| AC | Scenario | Task | Test |
|---|---|---|---|
| AC-001 | SC-001 | TASK-001 | PluginActivationTest |
| AC-002 | SC-002 | TASK-004 | SchemaIdempotencyTest |

No es obligatorio mantener una tabla separada si `tasks.md` y la auditoría ya proporcionan suficiente trazabilidad.

---

# 13. Regression Tests

Todo bug bloqueante corregido debería recibir una prueba de regresión cuando sea razonable.

Ejemplo:

```text
Round 1:
SEC-001
Alumno puede modificar progreso de otro usuario
```

Después de corregir:

```text
test_student_cannot_complete_another_users_lesson()
```

La prueba debe impedir que el mismo defecto reaparezca.

---

# 14. Regla para Findings Corregidos

Si un finding demuestra comportamiento incorrecto reproducible, Codex debe considerar añadir una prueba que:

```text
falla antes del fix
pasa después del fix
```

No siempre será posible, pero debe ser la preferencia.

---

# 15. Manual Verification

La verificación manual es válida cuando automatizar aporta poco valor inicial.

Ejemplos posibles:

- apariencia del admin
- orden visual
- experiencia responsive
- comportamiento de drag & drop
- reproducción de video embebido

Debe documentarse:

```text
qué se verificó
cómo
resultado
```

---

## 16. Manual no Sustituye Seguridad

La verificación manual no debe sustituir pruebas de autorización cuando éstas pueden automatizarse.

Ejemplo insuficiente:

> Probé que el botón no aparece para usuarios sin acceso.

Eso no demuestra seguridad.

Debe verificarse también backend.

---

# 17. Syntax Validation

Antes de auditoría debe validarse al menos que no existan errores sintácticos en PHP modificado.

Ejemplo:

```text
php -l
```

o equivalente automatizado sobre archivos relevantes.

---

# 18. Composer Validation

Si el proyecto utiliza Composer:

```text
composer validate
```

debe formar parte del quality gate cuando sea aplicable.

También debe verificarse:

```text
composer dump-autoload
```

cuando se modifiquen namespaces o autoloading.

---

# 19. PHPUnit

Framework de pruebas preferido:

```text
PHPUnit
```

La versión exacta dependerá de:

- PHP
- WordPress
- tooling seleccionado

SPEC-001 deberá documentar las versiones compatibles.

---

# 20. PHPCS

Se recomienda utilizar PHPCS con WordPress Coding Standards cuando el entorno lo permita.

Objetivos:

- errores claros
- escaping
- naming
- estándares básicos
- problemas comunes WordPress

No debe convertirse en un blocker por preferencias cosméticas que no estén configuradas como reglas obligatorias.

---

# 21. PHPStan / Static Analysis

Se recomienda evaluar PHPStan.

Puede utilizarse para:

- tipos
- nullability
- métodos inexistentes
- errores estructurales
- paths imposibles

La versión y nivel se definirán cuando se configure el proyecto.

No se debe fijar un nivel excesivamente alto que obligue a complejidad innecesaria para código WordPress.

---

# 22. JavaScript

Cuando exista JavaScript significativo podrán incorporarse pruebas específicas.

Ejemplos:

```text
quiz frontend
course builder
drag & drop
REST interactions
```

No se requiere configurar un framework JS de testing antes de que exista código que lo justifique.

---

# 23. Test Database

Las pruebas que modifiquen persistencia deben utilizar una base de datos de pruebas.

Nunca deben ejecutarse tests destructivos contra producción.

La configuración de testing debe distinguir claramente:

```text
development
test
production
```

---

# 24. Fixtures

Los tests pueden crear fixtures como:

- users
- courses
- lessons
- products
- orders
- enrollments
- quiz attempts

Los fixtures deben ser:

- pequeños
- explícitos
- reproducibles
- independientes cuando sea posible

---

# 25. Test Isolation

Una prueba no debe depender innecesariamente del orden de ejecución.

Ejemplo inválido:

```text
test_2 solo pasa si test_1 corrió antes
```

Cada prueba debe preparar su propio estado relevante.

---

# 26. Cleanup

Las pruebas de integración deben limpiar o resetear estado cuando corresponda.

El resultado de una ejecución previa no debe contaminar la siguiente.

---

# 27. Tests de Idempotencia

La idempotencia es un requisito importante en Kaanbal.

Debe probarse explícitamente cuando aplique.

Casos clave:

```text
activar plugin dos veces
procesar mismo pedido dos veces
matricular mismo curso dos veces
marcar lección completa dos veces
emitir certificado dos veces
```

---

# 28. Tests de Autorización

Para operaciones protegidas deben existir pruebas negativas.

No basta con comprobar:

```text
authorized user → success
```

También:

```text
unauthenticated user → denied
wrong user → denied
user without capability → denied
```

cuando corresponda.

---

# 29. Tests de Ownership

Especialmente importantes para:

- progreso
- quiz attempts
- certificados
- datos personales de alumno

Ejemplo:

```text
Usuario A intenta modificar progreso de Usuario B
→ operación rechazada
```

---

# 30. Tests de Input Validation

Deben cubrir inputs no válidos cuando tengan impacto real.

Ejemplos:

```text
course_id inexistente
lesson_id fuera del curso
quiz inexistente
score manipulado
product_id inválido
```

---

# 31. Tests de Persistencia

Las tablas propias deben validar:

- inserts
- updates
- uniqueness
- queries
- estados
- idempotencia

Especialmente las invariantes importantes.

---

# 32. Tests de Migraciones

Cuando exista versionado de schema, deben probarse:

```text
fresh install
upgrade path
re-run migration
```

cuando sea viable.

Una migración debe ser segura al ejecutarse según el flujo previsto.

---

# 33. Tests de Activación

SPEC-001 debe cubrir al menos:

```text
plugin activates
required bootstrap loads
schema installs
activation is idempotent
```

---

# 34. Tests de Desactivación

Debe verificarse que desactivar:

- no elimine datos académicos
- no provoque fatal errors

No es necesario automatizar cada detalle si el comportamiento es trivial, pero debe existir evidencia cuando la SPEC lo requiera.

---

# 35. Tests de WooCommerce

La futura SPEC de enrollment debe cubrir, al menos:

```text
single product → one course
single product → multiple courses
multiple products → overlapping courses
same order processed twice
user already enrolled
```

También deben definirse posteriormente tests para:

```text
refund
cancel
processing
completed
```

según la política aprobada.

---

# 36. Tests de Progreso

La futura SPEC debe cubrir:

```text
mark lesson complete
duplicate completion
progress percentage
course with zero lessons
access denied without enrollment
```

---

# 37. Tests de Quiz

La futura SPEC debe cubrir:

```text
correct score calculation
passing score
failing score
attempt limits
unlimited attempts
tampered client score ignored
answers evaluated server-side
```

---

# 38. Tests de Certificados

La futura SPEC debe cubrir:

```text
cannot issue before completion
issue after valid completion
unique certificate number
idempotent issuance
certificate snapshot remains stable
```

---

# 39. Happy Path y Negative Path

Cada funcionalidad importante debe considerar ambos.

Ejemplo:

```text
Happy:
Alumno matriculado completa lección.

Negative:
Alumno no matriculado intenta completar la misma lección.
```

---

# 40. Edge Cases

No todos los edge cases deben convertirse en requisito.

Deben priorizarse los que:

- pueden romper datos
- afectan seguridad
- son plausibles
- tienen alto impacto
- están cerca del flujo principal

No se debe inflar una SPEC con escenarios extremadamente improbables sin justificación.

---

# 41. Test Naming

Los nombres deben describir comportamiento.

Preferir:

```text
test_reprocessing_completed_order_does_not_duplicate_enrollment
```

sobre:

```text
test_case_4
```

---

# 42. Assertions

Una prueba debe tener assertions significativas.

Evitar tests que solo validen:

```text
true === true
object exists
method returns something
```

si eso no representa el requisito.

---

# 43. Mocks

Los mocks son útiles para aislar dependencias.

Pero no deben ocultar la funcionalidad que realmente queremos verificar.

Ejemplo:

Para probar lógica de dominio:

```text
mock repository
```

puede ser correcto.

Para probar integración WooCommerce:

```text
mock absolutamente todo WooCommerce
```

puede volver la prueba irrelevante.

---

# 44. Coverage

No se establece inicialmente un porcentaje obligatorio de code coverage.

El coverage puede utilizarse como señal.

No debe convertirse en objetivo independiente.

Preferimos:

```text
80 tests relevantes
```

sobre:

```text
100% coverage superficial
```

---

# 45. Quality Gate Pre-Audit

Antes de:

```text
Ready for audit: Yes
```

Codex debe ejecutar los checks obligatorios configurados para el proyecto.

La lista inicial esperada será algo similar a:

```text
composer validate
composer test
PHP syntax validation
PHPCS
PHPStan
```

más tests de integración aplicables.

Los comandos definitivos se fijarán tras configurar SPEC-001.

---

# 46. Registro del Quality Gate

`implementation-status.md` debe registrar:

```text
Quality Gate

PHP syntax:
PASS

Unit tests:
PASS — 24 tests

Integration tests:
PASS — 8 tests

PHPCS:
PASS

PHPStan:
PASS

Manual verification:
PASS
```

No se deben inventar resultados.

---

# 47. Fallos del Quality Gate

Si falla un check obligatorio:

```text
Ready for audit: No
```

Codex debe corregir antes de entregar formalmente a auditoría.

---

# 48. Checks No Configurados

Un check todavía no configurado debe registrarse como:

```text
NOT CONFIGURED
```

No como:

```text
PASS
```

Ejemplo:

```text
PHPStan:
NOT CONFIGURED
```

Esto puede ser aceptable en SPEC tempranas si todavía no forma parte del Definition of Done obligatorio.

---

# 49. Checks No Aplicables

Cuando un check no aplique:

```text
N/A
```

Ejemplo:

```text
JavaScript tests:
N/A — SPEC contains no JavaScript
```

---

# 50. Problemas Ambientales

Si una prueba no puede ejecutarse por el entorno:

```text
BLOCKED BY ENVIRONMENT
```

Debe registrarse:

- comando
- error
- causa aparente
- impacto sobre la evidencia
- qué pudo verificarse

---

# 51. No Reducir Tests para Pasar

Codex no debe:

- borrar tests válidos
- eliminar assertions
- reducir cobertura requerida
- cambiar expected output para esconder bug
- marcar skipped sin justificación

para obtener `Ready for audit`.

Esto debe considerarse una violación del workflow.

---

# 52. Auditoría de Tests por Claude

Claude debe comprobar:

```text
¿Existe el test?
¿Se ejecutó?
¿Pasa?
¿Realmente prueba el requisito?
```

No basta con confiar en el nombre del test.

---

# 53. Auditoría Arquitectónica de Tests

Qwen puede evaluar:

- testabilidad
- acoplamiento que impide probar
- necesidad excesiva de WordPress global
- boundaries imposibles de aislar

Debe evitar imponer arquitectura únicamente porque facilite mocking.

---

# 54. Auditoría de Seguridad de Tests

Mimo debe buscar especialmente ausencia de casos negativos.

Ejemplos:

```text
authorization
ownership
input tampering
CSRF
privilege escalation
```

Una vulnerabilidad real puede existir aunque todos los happy-path tests pasen.

---

# 55. Gherkin

Los escenarios Gherkin deben:

- describir comportamiento
- ser comprensibles
- estar ligados a AC
- evitar detalles internos innecesarios

Ejemplo correcto:

```gherkin
@AC-003
Scenario: Evitar matrícula duplicada
  Given un alumno ya está matriculado en un curso
  When se procesa nuevamente la misma fuente de acceso
  Then no debe crearse otra matrícula para ese curso
```

---

# 56. Gherkin Incorrecto

Evitar:

```gherkin
When EnrollmentRepository::insert executes
Then SQL INSERT IGNORE must run
```

Eso describe implementación, no comportamiento.

---

# 57. IDs de Escenarios

Los escenarios pueden utilizar IDs cuando facilite auditoría.

Ejemplo:

```gherkin
@AC-003 @SC-005
Scenario: Evitar matrícula duplicada
```

Esto mejora trazabilidad.

---

# 58. Test Evidence

Codex debe poder proporcionar evidencia resumida.

Ejemplo:

```text
AC-003
Scenario: SC-005
Test:
EnrollmentTest::test_duplicate_enrollment_is_prevented

Result:
PASS
```

---

# 59. Qué Debe Auditarse Nuevamente

Después de una corrección:

```text
finding original
+
código afectado
+
tests relacionados
+
regresiones razonables
```

No es necesario ejecutar el universo completo si el proyecto crece mucho y existe una suite claramente segmentada, salvo que el cambio tenga alcance transversal.

---

# 60. Full Test Suite

Antes de merge, idealmente debe ejecutarse la suite completa aplicable cuando el tiempo y entorno lo permitan.

Esto es especialmente recomendable para:

- cambios de infraestructura
- persistencia
- autorización
- WordPress bootstrap
- WooCommerce integration

---

# 61. Performance Tests

No se requieren performance tests formales inicialmente.

Pueden añadirse si existe una SPEC con requisitos medibles de rendimiento.

Ejemplo futuro:

```text
dashboard must load under X ms
```

Sin requisito medible, performance es una consideración técnica, no un gate automático.

---

# 62. Security Tests

No se requiere inicialmente una suite DAST completa.

Sin embargo, sí deben existir tests específicos para controles de seguridad implementados.

Ejemplos:

```text
authorization
nonce validation
ownership
server-side scoring
```

---

# 63. E2E

No se requiere configurar una plataforma E2E compleja en SPEC-001.

Puede añadirse posteriormente para flujos como:

```text
login
→ purchase
→ enroll
→ complete course
→ quiz
→ certificate
```

cuando el producto tenga suficiente madurez.

---

# 64. Browser Testing

Cuando existan pantallas reales, podrán utilizarse pruebas de navegador para:

- administración de cursos
- flujo alumno
- quiz
- certificados

No es requisito base de infraestructura.

---

# 65. Pruebas por SPEC

Cada `plan.md` debe indicar:

```text
Testing Strategy
```

para esa SPEC concreta.

Debe señalar:

- tests unitarios
- tests de integración
- validaciones manuales
- checks aplicables

---

# 66. Tasks y Tests

`tasks.md` debe especificar validación cuando sea relevante.

Ejemplo:

```text
TASK-004 — Implement enrollment uniqueness

Validation:
- EnrollmentRepositoryTest
- EnrollmentIntegrationTest
```

---

# 67. Test First

No se obliga TDD estricto.

Codex puede:

```text
test → implementation
```

o:

```text
implementation → test
```

según la tarea.

Lo obligatorio es que al final exista evidencia adecuada.

---

# 68. Bugs Descubiertos Durante Implementación

Si Codex descubre un bug fuera de la SPEC:

- no debe expandir silenciosamente alcance
- debe evaluar si bloquea el requisito actual
- si no bloquea, documentarlo como deuda/recomendación
- si bloquea, debe reportarlo

---

# 69. Tests de Código Heredado

Kaanbal comienza como proyecto nuevo.

No existe obligación de crear tests para WordPress o WooCommerce internos.

Solo para:

```text
nuestro código
nuestra integración
nuestros requisitos
```

---

# 70. Regla Final

La estrategia de testing de Kaanbal sigue este principio:

> Una prueba existe para aportar evidencia sobre comportamiento o riesgo, no para aumentar una métrica.

Antes de una auditoría, Codex debe poder demostrar:

```text
qué requisito implementó
qué prueba lo valida
qué resultado obtuvo
qué permanece sin verificar
```

Solo entonces la implementación puede declararse:

```text
Ready for audit: Yes
```