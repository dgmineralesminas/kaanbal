# SPEC-005 — Student Progress

Status: Draft

## 1. Objetivo

Implementar el seguimiento de progreso académico por alumno y curso.

Esta SPEC debe permitir:

- marcar manualmente una lección como completada
- evitar completar lecciones de otro usuario
- evitar progreso en cursos sin acceso
- consultar lecciones completadas
- calcular progreso porcentual del curso
- mostrar el estado de progreso en el player
- mantener la operación idempotente
- manejar unidades de contenido dinámicas sin almacenar porcentajes derivados

El progreso se basa exclusivamente en las lecciones del curso.

El quiz final NO forma parte del porcentaje de progreso.

---

## 2. Contexto

Las SPEC anteriores proporcionan:

### SPEC-001

Infraestructura base.

### SPEC-002

Estructura académica:

```text
Course
→ Modules
→ Lessons
```

### SPEC-003

Enrollment y acceso comercial.

### SPEC-004

Autorización y player.

SPEC-005 añade:

```text
User
  ↓
Course
  ↓
Lesson Completion
  ↓
Progress
```

---

## 3. Alcance

Esta SPEC incluye:

- persistencia de lecciones completadas
- marcado manual de lección como completada
- estado Completed de una lección
- consulta de progreso
- cálculo de porcentaje
- conteo de lecciones totales
- conteo de lecciones completadas
- visualización básica de progreso
- integración con Course View y Lesson View
- idempotencia
- autorización server-side
- protección contra IDOR
- validación de pertenencia Lesson → Course
- comportamiento cuando el contenido del curso cambia

---

## 4. Fuera de Alcance

Esta SPEC no incluye:

- quiz final
- score
- intentos de quiz
- certificado
- finalización definitiva del curso
- emisión de certificado
- porcentaje de video visto
- tracking de YouTube
- tiempo visto
- posición de reproducción
- última lección visitada
- bookmarks
- notas
- badges
- gamificación
- XP
- streaks
- analytics avanzados
- progreso por módulo almacenado
- progreso almacenado como porcentaje
- navegación secuencial obligatoria
- bloqueo de lecciones
- prerequisitos
- drip content

---

# 5. Requisitos Funcionales

## RF-001 — Marcar lección como completada

Un alumno autorizado debe poder marcar manualmente una lección como completada.

---

## RF-002 — Operación explícita

Abrir una lección no debe completarla automáticamente.

Debe existir una acción explícita del alumno.

Ejemplo:

`Marcar como completada`

---

## RF-003 — Persistencia

Kaanbal debe almacenar qué lecciones ha completado cada usuario.

---

## RF-004 — Idempotencia

Marcar la misma lección como completada varias veces no debe crear múltiples registros.

---

## RF-005 — Ownership

Un usuario solo puede registrar progreso para sí mismo mediante la interfaz normal de alumno.

---

## RF-006 — Acceso requerido

Solo un usuario con acceso válido al curso puede marcar una lección como completada.

---

## RF-007 — Pertenencia curricular

La lección que se intenta completar debe pertenecer realmente al curso al que el usuario tiene acceso.

---

## RF-008 — Consultar estado de lección

Kaanbal debe poder determinar si una lección está:

- completed
- not completed

para un usuario.

---

## RF-009 — Lecciones completadas del curso

Kaanbal debe poder obtener las lecciones completadas de un usuario dentro de un curso.

---

## RF-010 — Total de lecciones

Kaanbal debe poder determinar el número total de lecciones válidas del curso.

---

## RF-011 — Progreso porcentual

El progreso debe calcularse como:

```text
completed lessons / total lessons * 100
```

---

## RF-012 — Porcentaje derivado

El porcentaje no debe almacenarse como fuente de verdad.

Debe derivarse de:

- curriculum actual
- completions existentes

---

## RF-013 — Curso sin lecciones

Un curso con cero lecciones debe devolver un progreso coherente sin división por cero.

Para esta SPEC:

```text
progress = 0%
```

---

## RF-014 — Curso parcialmente completado

Ejemplo:

```text
3 completadas
10 totales
→ 30%
```

---

## RF-015 — Todas las lecciones completadas

Si todas las lecciones válidas están completadas:

```text
progress = 100%
```

Esto NO significa todavía que el curso esté académicamente completado.

La finalización definitiva dependerá del quiz en una SPEC posterior.

---

## RF-016 — Visualización Course View

El Course View debe poder mostrar:

- lecciones completadas
- lecciones pendientes
- porcentaje de progreso

---

## RF-017 — Visualización Lesson View

Lesson View debe poder mostrar si la lección actual ya está completada.

---

## RF-018 — Botón de completar

Si una lección no está completada, debe mostrarse una acción adecuada para marcarla.

Si ya está completada, la interfaz debe reflejar su estado.

---

## RF-019 — No desmarcar en MVP

Esta SPEC no requiere que un alumno pueda volver una lección de:

`completed`

a:

`not completed`.

---

## RF-020 — Cambio de curriculum

Si se agregan nuevas lecciones a un curso después de que un usuario tenía 100%:

el porcentaje debe recalcularse usando el curriculum actual.

Ejemplo:

```text
10/10 = 100%

se agrega 1 lección

10/11 = 90.91%
```

---

## RF-021 — Lección eliminada

Si una lección completada deja de pertenecer al curriculum válido, no debe contar para el porcentaje actual.

---

## RF-022 — Progreso aislado por usuario

El progreso de un usuario no debe afectar al de otro.

---

# 6. Requisitos No Funcionales

## RNF-001 — Autorización Server-Side

La operación de completar lección debe verificarse en servidor.

---

## RNF-002 — CSRF

Las acciones de escritura deben utilizar protección CSRF apropiada al mecanismo WordPress elegido.

---

## RNF-003 — IDOR

No debe ser posible modificar progreso de:

- otro usuario
- otra lección
- otro curso

mediante manipulación de IDs.

---

## RNF-004 — Idempotencia

La persistencia debe disponer de defensa contra duplicados tanto a nivel de aplicación como de base de datos cuando corresponda.

---

## RNF-005 — No porcentaje persistido

No crear un campo de porcentaje como fuente de verdad.

---

## RNF-006 — Rendimiento

Consultar progreso del curso no debe ejecutar una query por cada lección cuando pueda evitarse.

---

## RNF-007 — Consistencia

El estado de progreso debe calcularse utilizando únicamente lecciones válidas del curriculum actual.

---

## RNF-008 — Separación

Progress no debe decidir:

- si el usuario compró el curso
- si puede acceder

Debe delegar autorización a la capa creada en SPEC-004.

---

# 7. Reglas de Negocio

## RB-001

Una lección cuenta como completada si existe un registro de completion válido para:

```text
user_id + lesson_id
```

---

## RB-002

Solo se considera progreso para lecciones que actualmente pertenecen al curso.

---

## RB-003

Completar una lección es idempotente.

---

## RB-004

No se puede completar una lección de un curso al que el usuario no tiene acceso.

---

## RB-005

El porcentaje de progreso nunca incluye quiz.

---

## RB-006

El porcentaje nunca se almacena como autoridad.

---

## RB-007

100% de lecciones completadas no equivale todavía a Course Completed.

---

## RB-008

Una lección eliminada del curriculum deja de contar para el progreso actual.

---

## RB-009

Agregar una lección puede reducir el porcentaje existente.

---

## RB-010

El alumno no puede escribir progreso para otro alumno.

---

# 8. Criterios de Aceptación

## AC-001 — Completar lección

Un alumno con acceso válido puede marcar una lección de su curso como completada.

---

## AC-002 — No completar automáticamente

Abrir una lección no genera un registro de progreso.

---

## AC-003 — Idempotencia

Marcar una lección ya completada nuevamente no crea otro registro.

---

## AC-004 — Rechazar usuario sin acceso

Un usuario sin acceso válido al curso no puede completar sus lecciones.

---

## AC-005 — Rechazar lección de otro curso

Un usuario con acceso al Curso A no puede utilizar ese acceso para registrar progreso sobre una lección del Curso B.

---

## AC-006 — Evitar progreso de otro usuario

La operación normal no permite especificar arbitrariamente otro `user_id`.

---

## AC-007 — Consultar completion

Kaanbal puede determinar si una lección está completada para el usuario actual.

---

## AC-008 — Calcular progreso parcial

Dado:

```text
3 completed
10 total
```

el progreso calculado es:

```text
30%
```

---

## AC-009 — Calcular 100%

Si todas las lecciones están completadas:

```text
progress = 100%
```

---

## AC-010 — Curso vacío

Si el curso no tiene lecciones:

```text
progress = 0%
```

sin división por cero.

---

## AC-011 — Quiz excluido

La existencia o estado del quiz no modifica el porcentaje de progreso de lecciones.

---

## AC-012 — Mostrar estado en Course View

El alumno puede distinguir lecciones:

- completadas
- pendientes

en el temario.

---

## AC-013 — Mostrar porcentaje

Course View muestra el progreso calculado del alumno.

---

## AC-014 — Mostrar estado en Lesson View

Lesson View identifica correctamente si la lección está completada.

---

## AC-015 — Mostrar acción de completar

Una lección pendiente ofrece una acción para marcarla como completada.

---

## AC-016 — Lección ya completada

Una lección completada no requiere crear un nuevo completion al repetir la acción.

---

## AC-017 — Curriculum dinámico

Agregar una nueva lección cambia el porcentaje calculado sin necesidad de actualizar registros históricos de porcentaje.

---

## AC-018 — Lección retirada

Una lección que ya no forma parte del curso no cuenta para el porcentaje del curso actual.

---

## AC-019 — Progreso independiente

Dos usuarios del mismo curso pueden tener porcentajes distintos sin interferencia.

---

## AC-020 — No completar curso

Al alcanzar 100% de lecciones, esta SPEC no cambia automáticamente la matrícula a `completed`.

---

# 9. Casos Límite

## EC-001 — Double click

El alumno pulsa dos veces rápidamente "Marcar como completada".

Resultado:

una sola completion.

---

## EC-002 — Requests concurrentes

Dos requests intentan completar la misma lección.

Resultado:

una sola completion persistente.

---

## EC-003 — Enrollment revocado antes del request

Debe denegarse la escritura.

---

## EC-004 — Lesson inexistente

No debe crear progreso.

---

## EC-005 — Module inexistente

Una lección con jerarquía curricular inválida no debe generar progreso válido.

---

## EC-006 — Course/Lesson manipulados

Debe rechazarse la combinación inconsistente.

---

## EC-007 — Course vacío

0%.

---

## EC-008 — Lección nueva

El progreso puede bajar legítimamente.

---

## EC-009 — Lección eliminada

Su completion histórica puede permanecer para auditoría, pero no debe contar en el porcentaje actual.

---

## EC-010 — Completion huérfana

Un registro de completion que referencia una lección ya no válida no debe romper el cálculo.

---

# 10. Dependencias

## SPEC-001

Required.

## SPEC-002

Required.

Proporciona Curriculum.

## SPEC-003

Required.

Proporciona Enrollment.

## SPEC-004

Required.

Proporciona CourseAccessService y player.

---

# 11. Riesgos

## RISK-001 — IDOR

Un endpoint mal diseñado podría permitir completar lecciones de otro usuario.

Mitigación:

usar identidad autenticada del servidor.

---

## RISK-002 — TOCTOU

Comprobar acceso y luego escribir progreso sin proteger adecuadamente la operación podría permitir inconsistencias bajo concurrencia o cambios de estado.

Mitigación:

revalidar invariantes dentro del flujo de escritura y usar operaciones atómicas cuando corresponda.

---

## RISK-003 — Duplicados

Double-clicks y reintentos pueden crear completions duplicadas.

Mitigación:

unique constraint + idempotencia.

---

## RISK-004 — Porcentaje obsoleto

Persistir porcentaje produciría inconsistencias cuando cambia el curriculum.

Mitigación:

calcular dinámicamente.

---

## RISK-005 — N+1

Consultar completion individual por cada lección puede degradar Course View.

Mitigación:

obtener completions en batch.

---

# 12. Decisiones Pendientes

El plan puede definir:

- mecanismo de escritura: REST, AJAX, admin-post o endpoint equivalente
- respuesta síncrona o JSON
- UI exacta del botón
- precisión/formato visual del porcentaje
- retención de completions huérfanas
- uso exacto de transacción/bloqueo según motor de base de datos

Estas decisiones no pueden debilitar:

- autorización
- ownership
- idempotencia

---

# 13. Referencias

- `docs/project-context.md`
- `docs/architecture.md`
- `docs/data-model.md`
- `docs/testing-strategy.md`
- `docs/specs/002-courses-and-curriculum/`
- `docs/specs/003-woocommerce-enrollment/`
- `docs/specs/004-course-access-and-player/`