# Estándar de Auditoría

## 1. Propósito

Este documento define el formato, criterios, severidades, reglas de clasificación y comportamiento esperado para todas las auditorías del proyecto.

Aplica a:

- Claude Code — auditoría de requisitos e implementación
- Qwen — auditoría de arquitectura
- Mimo — auditoría de seguridad

El objetivo es que todas las auditorías sean:

- consistentes
- reproducibles
- trazables
- comparables entre rondas
- basadas en evidencia
- resistentes al scope creep

Este documento complementa:

- `docs/agents.md`
- `docs/development-workflow.md`
- `docs/definition-of-done.md`

---

## 2. Principio General

Toda auditoría debe responder una pregunta concreta dentro de su ámbito.

Claude Code:

> ¿La implementación satisface correctamente los requisitos aprobados?

Qwen:

> ¿La implementación respeta la arquitectura aprobada y sigue siendo mantenible?

Mimo:

> ¿La implementación introduce vulnerabilidades explotables o viola requisitos de seguridad?

Los auditores no deben competir por encontrar más problemas.

La calidad de una auditoría se mide por:

- precisión
- evidencia
- relevancia
- trazabilidad
- correcta clasificación

No por la cantidad de findings.

---

## 3. Independencia

Cada auditor debe revisar el mismo candidato de implementación de forma independiente.

Debe identificarse, cuando Git esté disponible:

```text
Audit round: 1
Audit commit: abc1234
```

Los tres auditores deben revisar el mismo commit.

Un auditor no debe modificar su conclusión únicamente porque otro auditor haya obtenido un resultado distinto.

---

## 4. Artefactos de Auditoría

Cada ronda debe generar:

```text
audits/
└── round-N/
    ├── code-audit.md
    ├── architecture-audit.md
    ├── security-audit.md
    └── round-summary.md
```

Ownership:

```text
Claude Code
→ code-audit.md

Qwen
→ architecture-audit.md

Mimo
→ security-audit.md
```

Los auditores no deben modificar:

- código de producción
- pruebas
- `spec.md`
- `plan.md`
- `feature.feature`
- `tasks.md`
- `implementation-status.md`
- reportes de otros auditores

---

## 5. Veredictos Permitidos

Toda auditoría debe concluir con uno de estos tres resultados:

```text
PASS
PASS WITH RECOMMENDATIONS
FAIL
```

No deben inventarse estados alternativos.

---

## 6. PASS

Se utiliza cuando:

- no existen findings bloqueantes
- no existen defectos materiales dentro del ámbito del auditor
- existe evidencia suficiente para considerar la implementación aceptable

Puede haber observaciones informativas menores siempre que no requieran acción.

Ejemplo:

```text
Verdict: PASS
Blocking findings: 0
Recommendations: 0
```

---

## 7. PASS WITH RECOMMENDATIONS

Se utiliza cuando:

- no existen findings bloqueantes
- la implementación puede avanzar
- existen mejoras razonables pero opcionales

Ejemplo:

```text
Verdict: PASS WITH RECOMMENDATIONS
Blocking findings: 0
Recommendations: 2
```

Las recomendaciones no son obligatorias.

El humano decide si pasan a:

- backlog
- technical debt
- future SPEC
- hardening
- mejora de arquitectura

---

## 8. FAIL

Se utiliza únicamente cuando existe al menos un finding con:

```text
Blocking: Yes
```

Ejemplo:

```text
Verdict: FAIL
Blocking findings: 1
Recommendations: 2
```

No puede existir:

```text
Verdict: FAIL
Blocking findings: 0
```

Eso sería una auditoría inválida.

---

## 9. Tipos de Finding

Todo finding debe clasificarse.

Valores recomendados:

```text
Requirement Violation
Behavioral Defect
Implementation Defect
Architecture Violation
Security Vulnerability
Data Integrity Risk
Regression
Test Gap
Documentation Mismatch
Recommendation
Technical Debt
Future Consideration
```

Los auditores deben elegir la clasificación más específica aplicable.

---

## 10. Identificadores

Los findings deben usar identificadores permanentes.

Claude Code:

```text
CODE-001
CODE-002
CODE-003
```

Qwen:

```text
ARCH-001
ARCH-002
ARCH-003
```

Mimo:

```text
SEC-001
SEC-002
SEC-003
```

Los IDs:

- no se reutilizan
- no se renumeran
- no desaparecen entre rondas

Si un finding se corrige:

```text
CODE-002 → RESOLVED
```

Si aparece un problema nuevo:

```text
CODE-004 → NEW
```

---

## 11. Severidad

Valores permitidos:

```text
Critical
High
Medium
Low
Info
```

La severidad representa el impacto potencial.

No determina automáticamente si el finding es bloqueante.

---

## 12. Critical

Usar cuando existe riesgo excepcionalmente alto.

Ejemplos:

- ejecución remota de código
- escalación administrativa trivial
- exposición masiva de información sensible
- corrupción grave de datos
- pérdida irreversible de datos
- bypass completo de autorización
- implementación fundamentalmente incompatible con requisitos críticos

Normalmente será:

```text
Blocking: Yes
```

pero debe existir evidencia real.

---

## 13. High

Usar para defectos de impacto significativo.

Ejemplos:

- acceso no autorizado a contenido pagado
- modificación del progreso de otros usuarios
- incumplimiento de un criterio central
- arquitectura que viola un boundary aprobado y genera riesgo material
- creación incorrecta o pérdida de matrículas
- manipulación de resultados de quiz
- certificado emitido sin cumplir condiciones

Puede ser bloqueante.

---

## 14. Medium

Usar para problemas materiales pero acotados.

Ejemplos:

- manejo incompleto de un caso requerido
- error de validación con impacto limitado
- duplicación innecesaria que compromete mantenibilidad
- test insuficiente para comportamiento importante
- mismatch documental relevante

Puede ser:

```text
Blocking: Yes
```

o:

```text
Blocking: No
```

según impacto sobre la SPEC.

---

## 15. Low

Usar para problemas menores.

Ejemplos:

- nomenclatura inconsistente
- duplicación pequeña
- mensajes de error mejorables
- pequeñas mejoras de legibilidad
- cobertura adicional deseable

Normalmente:

```text
Blocking: No
```

---

## 16. Info

Usar para información, observaciones o contexto.

Nunca debe ser bloqueante.

Ejemplo:

```text
Severity: Info
Blocking: No
Classification: Future Consideration
```

---

## 17. Regla de Blocking

Un finding puede marcarse:

```text
Blocking: Yes
```

solo si impide aceptar correctamente la SPEC.

Debe demostrar al menos uno de estos casos:

- criterio de aceptación incumplido
- comportamiento obligatorio ausente
- comportamiento contrario a la SPEC
- vulnerabilidad material
- riesgo material de integridad
- regresión demostrable
- violación de una restricción arquitectónica aprobada
- implementación incapaz de cumplir de forma segura el alcance definido

---

## 18. Qué NO es un Blocker

No son blockers por sí solos:

- preferencia personal del auditor
- patrón alternativo que el auditor considera mejor
- abstracción adicional
- optimización futura
- feature no solicitada
- soporte para una integración futura
- escalabilidad para una magnitud no requerida
- cobertura de tests adicional sin riesgo real
- gusto de nomenclatura
- refactor opcional

Ejemplo inválido:

> FAIL porque sería mejor implementar GraphQL.

Si GraphQL no está requerido:

```text
Classification: Recommendation
Blocking: No
```

---

## 19. Prueba del Blocker

Antes de declarar:

```text
Blocking: Yes
```

el auditor debe poder responder:

1. ¿Qué requisito o restricción aprobada está afectada?
2. ¿Cuál es el defecto concreto?
3. ¿Qué evidencia existe?
4. ¿Cuál es el impacto?
5. ¿Por qué no puede aceptarse la SPEC sin corregirlo?

Si no puede responder esas cinco preguntas, no debe declarar blocker.

---

## 20. Formato Obligatorio de Finding

Los findings significativos deben usar esta estructura:

```markdown
## CODE-001 — Título breve y específico

Severity: High
Blocking: Yes
Classification: Requirement Violation

Affected requirement:
AC-004

Affected scenario:
SC-003

Affected task:
TASK-007

Affected files:
- src/Example.php
- tests/ExampleTest.php

Description:

Descripción concreta del problema.

Evidence:

Evidencia técnica verificable.

Expected:

Comportamiento esperado según SPEC.

Actual:

Comportamiento observado.

Impact:

Consecuencia del defecto.

Required correction:

Condición mínima necesaria para considerar el finding resuelto.
```

Los campos que no apliquen pueden omitirse, salvo:

- ID
- Severity
- Blocking
- Classification
- Description
- Evidence
- Impact

---

## 21. Evidencia Válida

Puede incluir:

- referencia a archivo y método
- test fallido
- resultado de comando
- comportamiento reproducible
- contradicción directa con un AC
- dependencia no permitida
- flujo de autorización incompleto
- consulta insegura
- endpoint sin protección
- reproducción manual documentada

Ejemplo:

```text
File:
src/Enrollment/EnrollmentService.php

Method:
grantAccess()

Evidence:
No existe comprobación previa de matrícula y la tabla permite múltiples filas
activas para el mismo user_id + course_id.
```

---

## 22. Evidencia Débil

No debe usarse sola para bloquear:

> Parece inseguro.

> Tal vez escale mal.

> No me gusta este patrón.

> Podría ser más limpio.

> Generalmente se recomienda otra cosa.

Estas observaciones necesitan evidencia adicional.

---

## 23. Auditoría de Claude Code

Claude debe priorizar trazabilidad.

Cadena esperada:

```text
spec.md
↓
Acceptance Criteria
↓
feature.feature
↓
tasks.md
↓
tests
↓
implementation
```

Debe responder para cada criterio relevante:

```text
Implemented: Yes/No
Verified: Yes/No
Evidence: ...
```

---

## 24. Claude — Findings Bloqueantes Válidos

Ejemplos:

- task marcada Done pero no implementada
- AC sin implementación
- escenario Gherkin incumplido
- test que demuestra comportamiento incorrecto
- código incompatible con el comportamiento esperado
- regresión funcional
- ruta de error obligatoria ausente
- documentación de estado falsa

---

## 25. Claude — Recomendaciones No Bloqueantes

Ejemplos:

- más tests de edge cases
- refactor para claridad
- nombres más expresivos
- documentación adicional
- helper reutilizable no requerido

Claude debe evitar convertirse en auditor de arquitectura general si el problema no afecta cumplimiento.

---

## 26. Auditoría de Qwen

Qwen debe comparar la implementación contra:

- `plan.md`
- `docs/architecture.md`
- restricciones de `spec.md`
- decisiones arquitectónicas aprobadas

Debe revisar:

```text
responsabilidades
boundaries
dependencias
persistencia
cohesión
acoplamiento
extensibilidad
WordPress integration
WooCommerce integration
```

---

## 27. Qwen — Findings Bloqueantes Válidos

Ejemplos:

- dominio depende directamente de WooCommerce cuando el plan exige adapter
- capa de presentación contiene lógica de negocio crítica contra la arquitectura aprobada
- persistencia viola una decisión explícita de la SPEC
- componente crea acoplamiento circular material
- lifecycle incorrecto puede romper activación o upgrades
- diseño genera corrupción o inconsistencia previsible

---

## 28. Qwen — Recomendaciones No Bloqueantes

Ejemplos:

- extraer una interfaz adicional
- renombrar un servicio
- separar una clase que todavía es manejable
- usar otro patrón igualmente válido
- optimización futura
- preparar extensibilidad no requerida

Qwen no debe imponer su arquitectura favorita.

---

## 29. Auditoría de Mimo

Mimo debe realizar una revisión orientada a amenazas reales dentro del alcance implementado.

Debe revisar, cuando aplique:

```text
authentication
authorization
ownership
capabilities
nonces
CSRF
XSS
escaping
sanitization
SQL injection
IDOR
REST
AJAX
uploads
secrets
data exposure
business rule manipulation
```

---

## 30. Mimo — Findings Bloqueantes Válidos

Ejemplos:

- alumno puede abrir un curso sin matrícula
- alumno puede marcar progreso de otro usuario
- nonce presente pero sin validación de ownership
- endpoint REST modifica datos sin autorización
- SQL construido con input no preparado
- certificado puede emitirse manipulando parámetros
- quiz acepta score enviado por el cliente
- acceso pagado depende únicamente de validación frontend

---

## 31. Mimo — Recomendaciones No Bloqueantes

Ejemplos:

- headers defensivos adicionales
- rate limiting futuro
- hardening adicional
- logging de seguridad más detallado
- controles pensados para una escala o amenaza no requerida todavía

---

## 32. Auditoría de Tests

Los auditores deben distinguir:

```text
Test exists
Test passes
Test is meaningful
Test actually covers requirement
```

Un test verde no demuestra por sí solo cumplimiento.

Ejemplo de test débil:

```php
$this->assertTrue( true );
```

No aporta evidencia.

---

## 33. Tests Manipulados

Debe reportarse como finding si Codex:

- elimina una assertion necesaria
- cambia expected behavior para coincidir con un bug
- deshabilita un test obligatorio
- agrega `skip` sin justificación
- reemplaza integración real por mock irrelevante

La severidad dependerá del impacto.

---

## 34. Cobertura Incompleta

La falta de test no siempre implica `FAIL`.

Debe analizarse:

- criticidad
- requirement
- riesgo de regresión
- si existe otra evidencia válida

Un AC central sin prueba ni evidencia reproducible puede ser blocker.

Un edge case opcional sin test suele ser recomendación.

---

## 35. Findings Repetidos

Un auditor no debe crear un ID nuevo para el mismo problema en cada ronda.

Ejemplo correcto:

```text
Round 1:
SEC-001 → OPEN

Round 2:
SEC-001 → STILL OPEN

Round 3:
SEC-001 → RESOLVED
```

Ejemplo incorrecto:

```text
Round 1:
SEC-001

Round 2:
SEC-004 para exactamente el mismo defecto
```

---

## 36. Estado de Findings entre Rondas

Estados permitidos:

```text
NEW
OPEN
RESOLVED
STILL OPEN
PARTIALLY RESOLVED
NOT REPRODUCIBLE
DISPUTED
ACCEPTED RISK
```

`ACCEPTED RISK` requiere decisión humana.

Un auditor no puede aceptar el riesgo por sí mismo.

---

## 37. Nuevo Finding Durante Remediación

Si una corrección introduce un defecto nuevo:

```text
SEC-001 → RESOLVED
SEC-002 → NEW
```

El nuevo problema recibe un identificador nuevo.

---

## 38. Round Summary

`round-summary.md` debe consolidar, no reinterpretar.

Formato recomendado:

```markdown
# Audit Round 2 Summary

SPEC: 003-woocommerce-enrollment
Audit commit: abc1234

## Verdicts

Claude Code: PASS
Qwen: PASS WITH RECOMMENDATIONS
Mimo: FAIL

Overall verdict: FAIL

## Blocking findings

- SEC-004 — Missing ownership validation

## Non-blocking findings

- ARCH-REC-002
- CODE-REC-003

## Previous findings

- CODE-001 — RESOLVED
- ARCH-001 — RESOLVED
- SEC-002 — RESOLVED

## Next action

Return to Codex for remediation of SEC-004.
```

---

## 39. Overall Verdict

La consolidación es mecánica.

Si:

```text
Claude = PASS
Qwen = PASS
Mimo = PASS
```

Resultado:

```text
Overall: PASS
```

También pasa:

```text
Claude = PASS
Qwen = PASS WITH RECOMMENDATIONS
Mimo = PASS
```

Resultado:

```text
Overall: PASS
```

Si cualquiera:

```text
FAIL
```

Resultado:

```text
Overall: FAIL
```

---

## 40. Findings Disputados

Si Codex disputa un finding, el auditor no debe eliminarlo.

Debe permanecer:

```text
Status: DISPUTED
```

hasta decisión humana.

El humano puede resolver:

```text
VALID
INVALID
ACCEPTED RISK
SCOPE CHANGE REQUIRED
```

---

## 41. Auditoría y Scope Creep

Está prohibido usar una auditoría para expandir alcance.

Ejemplo:

SPEC:

> El curso utilizará videos de YouTube.

Finding inválido:

> FAIL porque debería existir integración con Vimeo y Bunny Stream.

Clasificación correcta:

```text
Future Consideration
Blocking: No
```

---

## 42. Auditoría y Requisitos Futuros

Un auditor puede señalar que una decisión actual dificultará algo futuro.

Pero si ese futuro no forma parte del alcance actual:

```text
Recommendation
Blocking: No
```

salvo que contradiga una decisión arquitectónica global explícita.

---

## 43. Auditoría y Escalabilidad

No debe auditarse contra una escala imaginaria.

Debe utilizarse la escala definida por el proyecto.

Un diseño puede ser válido para:

```text
miles de alumnos
cientos de cursos
cientos de miles de progresos
```

sin necesitar arquitectura diseñada para millones de usuarios.

---

## 44. Auditoría y WordPress

No debe considerarse un defecto usar APIs nativas de WordPress solo por no seguir patrones de frameworks externos.

Debe evaluarse:

- corrección
- seguridad
- mantenibilidad
- arquitectura aprobada

No preferencias de framework.

---

## 45. Auditoría y WooCommerce

WooCommerce es una dependencia externa del proyecto.

El auditor debe verificar que:

- no se modifique WooCommerce Core
- integración use mecanismos soportados
- hooks estén correctamente utilizados
- lógica LMS no dependa innecesariamente de detalles internos
- operaciones sean idempotentes cuando el requisito lo exija

---

## 46. Honestidad del Auditor

Todo auditor debe distinguir:

```text
Verified
Observed
Inferred
Not verified
Unable to reproduce
Not applicable
```

Nunca debe presentar una inferencia como hecho verificado.

---

## 47. Limitaciones Ambientales

Si el auditor no puede ejecutar una prueba por entorno:

```text
Verification status: BLOCKED BY ENVIRONMENT
```

Debe explicar:

- qué intentó
- qué falló
- qué pudo revisar estáticamente
- qué queda sin verificar

Esto no implica automáticamente `PASS` ni `FAIL`.

---

## 48. Auditoría Parcial

Una auditoría formal debería evitar ser parcial.

Si una parte material no pudo revisarse:

```text
Audit completeness: Partial
```

El auditor debe explicar qué falta.

Si la parte no verificada es indispensable para determinar cumplimiento, no debe emitir un `PASS` definitivo.

---

## 49. No Corregir Durante Auditoría

Los auditores nunca deben:

- modificar producción
- corregir tests
- cambiar Gherkin
- ajustar acceptance criteria
- aplicar parches
- refactorizar

Deben limitarse a:

```text
inspect
verify
report
```

Codex realiza la corrección.

---

## 50. Regla Final

Una auditoría válida debe ser:

> específica, demostrable, trazable y proporcional al riesgo real.

No se busca perfección teórica.

Se busca confianza suficiente para afirmar que la implementación:

- cumple lo aprobado
- mantiene una arquitectura aceptable
- no introduce riesgos de seguridad materiales
- está lista para revisión humana