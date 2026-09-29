# SPEC-006 — Final Quiz and Course Completion

Status: Ready for audit

## 1. Objetivo

Implementar el quiz final opcional de Kaanbal y la lógica formal para determinar cuándo un alumno ha aprobado un curso.

Esta SPEC debe permitir:

- configurar si un curso requiere quiz final
- crear un quiz final
- crear preguntas de opción múltiple
- crear respuestas
- definir respuesta correcta
- configurar calificación mínima aprobatoria
- configurar límite de intentos
- permitir intentos ilimitados
- evaluar respuestas del lado servidor
- registrar intentos
- calcular score
- determinar si el quiz fue aprobado
- determinar cuándo el curso completo ha sido aprobado
- soportar cursos sin quiz
- informar al alumno cuando apruebe
- mostrar mensaje de seguimiento de certificado cuando corresponda

Kaanbal NO generará ni descargará certificados.

---

## 2. Contexto

Las SPEC anteriores proporcionan:

```text
SPEC-001 → Foundation
SPEC-002 → Courses and Curriculum
SPEC-003 → Enrollment
SPEC-004 → Access and Player
SPEC-005 → Student Progress
```

SPEC-005 permite alcanzar:

```text
100% de lecciones
```

pero deliberadamente NO cambia el curso a completado.

SPEC-006 introduce la política definitiva de aprobación:

```text
Course Completion Policy
```

---

## 3. Principio Fundamental

No todos los cursos requieren quiz final.

Por lo tanto deben existir dos flujos válidos.

### Curso sin quiz

```text
100% lessons completed
        ↓
Course passed
```

### Curso con quiz

```text
100% lessons completed
        +
Final quiz passed
        ↓
Course passed
```

La existencia de certificado NO determina si un curso se aprueba.

---

# 4. Certificados

Kaanbal NO debe:

- generar PDF
- generar documento
- generar archivo descargable
- permitir descarga de certificado
- enviar automáticamente el certificado
- almacenar archivos de certificado

Cuando un curso aprobado tenga configurado que posteriormente se entregará certificado, Kaanbal únicamente debe informar al alumno.

Mensaje conceptual:

> Has aprobado el curso. En breve nos estaremos comunicando contigo para hacerte llegar tu certificado.

La entrega real se realizará externamente por correo electrónico.

---

# 5. Alcance

Esta SPEC incluye:

- configuración de quiz por curso
- quiz final opcional
- preguntas
- respuestas
- respuesta correcta
- orden de preguntas
- calificación mínima
- límite de intentos
- intentos ilimitados
- registro de intentos
- registro de respuestas del intento
- scoring server-side
- aprobación/reprobación
- control de intentos disponibles
- Course Completion Policy
- transición de Enrollment a `completed`
- fecha de finalización
- cursos sin quiz
- mensaje de aprobación
- configuración informativa sobre certificado
- mensaje de seguimiento de certificado

---

# 6. Fuera de Alcance

Esta SPEC NO incluye:

- generación de certificados
- PDFs
- descarga de certificados
- envío de archivos
- envío automático de correo con certificado
- validación pública de certificados
- folios de certificado
- firma digital
- SCORM
- bancos avanzados de preguntas
- preguntas aleatorias
- preguntas abiertas
- verdadero/falso independiente
- matching
- ensayos
- calificación manual
- proctoring
- temporizador
- bloqueo del navegador
- quizzes por módulo
- múltiples quizzes por curso
- analytics avanzados
- reportes administrativos completos
- estadísticas globales de alumnos por curso

Los reportes administrativos se implementarán posteriormente.

---

# 7. Requisitos Funcionales

## RF-001 — Quiz opcional

Un curso puede configurarse con o sin quiz final.

---

## RF-002 — Un quiz final

En el MVP un curso puede tener como máximo un quiz final activo.

---

## RF-003 — Preguntas

El administrador puede crear preguntas para el quiz.

---

## RF-004 — Tipo de pregunta

El MVP soporta:

`single_choice`

Una pregunta presenta múltiples respuestas y exactamente una debe ser correcta.

---

## RF-005 — Orden

Las preguntas deben poder recuperarse en orden determinista.

---

## RF-006 — Respuestas

Cada pregunta debe disponer de opciones de respuesta.

---

## RF-007 — Respuesta correcta

Una pregunta `single_choice` debe tener exactamente una respuesta correcta válida para ser utilizada en un quiz publicable/utilizable.

---

## RF-008 — Passing Score

El administrador puede definir la calificación mínima necesaria para aprobar.

Ejemplo:

```text
80%
```

---

## RF-009 — Intentos

El administrador puede configurar:

- número máximo de intentos

o:

- intentos ilimitados

La representación persistida es obligatoria:

```text
max_attempts = NULL → intentos ilimitados
max_attempts >= 1   → límite de intentos
0                  → valor inválido
```

---

## RF-010 — Iniciar intento

Un alumno autorizado puede comenzar un intento si:

- tiene acceso al curso
- el curso requiere quiz
- tiene 100% de lecciones completadas
- dispone de intentos

---

## RF-011 — Lecciones pendientes

Un alumno que no tiene 100% de lecciones completadas no puede presentar el quiz final.

---

## RF-012 — Evaluación Server-Side

El navegador no determina el score.

Kaanbal debe evaluar las respuestas del lado servidor.

---

## RF-013 — Score

Kaanbal debe calcular:

```text
correct answers / total questions * 100
```

para el MVP.

Todas las preguntas tendrán el mismo peso.

---

## RF-014 — Quiz aprobado

Si:

```text
score >= passing_score
```

el intento queda aprobado.

---

## RF-015 — Quiz reprobado

Si:

```text
score < passing_score
```

el intento queda reprobado.

---

## RF-016 — Registrar intento

Cada submission válida debe registrarse como intento independiente.

---

## RF-017 — Número de intento

Los intentos deben numerarse de forma consistente por:

```text
user + quiz
```

---

## RF-018 — Límite alcanzado

Cuando un alumno consume todos sus intentos permitidos sin aprobar, no puede iniciar otro intento.

---

## RF-019 — Intentos ilimitados

Cuando el quiz está configurado como ilimitado, un alumno puede continuar intentando hasta aprobar.

---

## RF-020 — Aprobar una vez

Una vez aprobado el quiz, no es necesario volver a presentarlo para completar el curso.

---

# 8. Course Completion

## RF-021 — Curso sin quiz

Si un curso NO requiere quiz:

```text
progress = 100%
```

es suficiente para aprobar el curso.

---

## RF-022 — Curso con quiz

Si un curso requiere quiz:

```text
progress = 100%
AND
quiz passed
```

son necesarios para aprobar.

---

## RF-023 — No completar antes de tiempo

Un curso con quiz no debe marcarse como completado únicamente por alcanzar 100% de lecciones.

---

## RF-024 — Enrollment completed

Cuando el alumno cumple la política de aprobación:

```text
enrollment.status = completed
```

y debe registrarse:

```text
completed_at
```

---

## RF-025 — Idempotencia de completion

Evaluar repetidamente la finalización no debe:

- duplicar eventos
- alterar fechas arbitrariamente
- crear múltiples completions

---

## RF-026 — Acceso después de aprobar

Una matrícula `completed` continúa permitiendo acceso al curso según SPEC-004.

---

# 9. Configuración de Certificado

## RF-027 — Curso con seguimiento de certificado

Un curso puede configurarse para indicar que después de aprobar se entregará un certificado externamente.

---

## RF-028 — Curso sin certificado

Un curso puede no ofrecer certificado.

Esto no afecta la aprobación.

---

## RF-029 — Mensaje con certificado

Cuando el alumno aprueba un curso configurado con certificado, debe mostrarse un mensaje equivalente a:

> Has aprobado el curso. En breve nos estaremos comunicando contigo para hacerte llegar tu certificado.

---

## RF-030 — Mensaje sin certificado

Cuando el alumno aprueba un curso sin certificado, debe mostrarse un mensaje de éxito sin prometer entrega de certificado.

---

# 10. Requisitos No Funcionales

## RNF-001 — Server-Side Scoring

Nunca confiar en:

```text
score
passed
correct
```

enviados por el cliente.

---

## RNF-002 — Authorization

Solo el alumno autenticado correspondiente puede presentar su quiz.

---

## RNF-003 — IDOR

No se puede:

- presentar intento por otro usuario
- utilizar quiz de otro curso
- enviar preguntas de otro quiz

---

## RNF-004 — CSRF

La submission debe utilizar protección adecuada.

---

## RNF-005 — Prepared Queries

Persistencia transaccional debe utilizar queries seguras.

---

## RNF-006 — Idempotencia de Course Completion

Evaluar la política varias veces mantiene estado consistente.

---

## RNF-007 — Historial

Los intentos históricos no deben sobrescribirse.

---

## RNF-008 — Integridad histórica

Un intento debe conservar suficiente información para entender el resultado obtenido en el momento en que fue presentado, incluidos snapshots del texto de pregunta y de la respuesta seleccionada.

---

# 11. Reglas de Negocio

## RB-001

Quiz final es opcional.

---

## RB-002

Certificado es opcional.

---

## RB-003

Quiz y certificado son configuraciones independientes.

Son válidos, por ejemplo:

```text
Quiz: No
Certificado: No

Quiz: No
Certificado: Sí

Quiz: Sí
Certificado: No

Quiz: Sí
Certificado: Sí
```

---

## RB-004

Curso sin quiz:

```text
100% lessons
=
passed
```

---

## RB-005

Curso con quiz:

```text
100% lessons
+
quiz passed
=
passed
```

---

## RB-006

El quiz no forma parte del porcentaje de progreso.

---

## RB-007

Passing score es inclusivo.

Ejemplo:

```text
passing = 80
score = 80
→ PASS
```

---

## RB-008

Todas las preguntas valen lo mismo en MVP.

---

## RB-009

Un alumno no puede presentar quiz antes de completar todas las lecciones.

---

## RB-010

Una vez `completed`, reevaluar el curso no debe regresar automáticamente a `active` porque posteriormente se edite el curriculum.

La política sobre reapertura de cursos completados pertenece a una futura decisión de negocio.

---

## RB-011

La existencia o ausencia de certificado no modifica el estado académico.

---

# 12. Criterios de Aceptación

## AC-001 — Curso sin quiz

Un curso puede configurarse sin quiz final.

---

## AC-002 — Curso con quiz

Un curso puede asociarse con un quiz final.

---

## AC-003 — Crear preguntas

Un administrador autorizado puede crear preguntas single-choice.

---

## AC-004 — Configurar respuestas

Cada pregunta puede tener múltiples respuestas y una respuesta correcta.

---

## AC-005 — Passing Score

El quiz puede configurar un passing score válido.

---

## AC-006 — Intentos limitados

El quiz puede configurar una cantidad máxima de intentos.

---

## AC-007 — Intentos ilimitados

El quiz puede configurarse sin límite de intentos.

---

## AC-008 — Bloquear quiz antes de 100%

Un alumno con progreso menor a 100% no puede presentar el quiz.

---

## AC-009 — Presentar quiz

Un alumno elegible puede enviar sus respuestas.

---

## AC-010 — Score Server-Side

Kaanbal calcula el score sin confiar en valores enviados por el navegador.

---

## AC-011 — Aprobar quiz

Score igual o superior al passing score produce:

`passed`

---

## AC-012 — Reprobar quiz

Score inferior produce:

`failed`

---

## AC-013 — Registrar intento

Cada presentación válida queda registrada.

---

## AC-014 — Respetar límite

Después de consumir todos los intentos permitidos, no se permite otro intento.

---

## AC-015 — Curso sin quiz se completa

Cuando un alumno alcanza 100% de lecciones en un curso sin quiz, el curso puede pasar a `completed`.

---

## AC-016 — Curso con quiz no se completa solo con lecciones

100% de lecciones no es suficiente si el curso requiere quiz.

---

## AC-017 — Curso con quiz se completa al aprobar

100% de lecciones + quiz aprobado produce `completed`.

---

## AC-018 — Fecha de finalización

La primera transición válida a `completed` registra `completed_at`.

---

## AC-019 — Completion idempotente

Reevaluar un curso ya completado conserva un estado consistente y no reemplaza arbitrariamente la fecha original.

---

## AC-020 — Acceso continúa

El alumno conserva acceso después de completar el curso.

---

## AC-021 — Certificado opcional

Un curso puede indicar que entregará certificado o que no lo hará.

---

## AC-022 — Mensaje de certificado

Un alumno que aprueba un curso con certificado recibe el mensaje informativo correspondiente.

---

## AC-023 — Sin descarga

Kaanbal no genera ni ofrece descarga de PDF/documento de certificado.

---

## AC-024 — Curso sin certificado

Un curso sin certificado puede aprobarse normalmente.

---

## AC-025 — Quiz no altera progreso

Presentar o aprobar quiz no modifica el porcentaje de lecciones.

---

## AC-026 — Ownership

Un usuario no puede registrar intentos en nombre de otro usuario.

---

## AC-027 — Quiz-Course Integrity

No puede enviarse como válido un quiz que no corresponde al curso contextual.

---

# 13. Casos Límite

## EC-001 — Curso sin lecciones y sin quiz

Según SPEC-005:

```text
progress = 0%
```

Por lo tanto no se considera automáticamente aprobado.

---

## EC-002 — Quiz sin preguntas

No debe ser presentable como quiz válido.

---

## EC-003 — Pregunta sin respuesta correcta

No debe formar parte de un quiz utilizable hasta corregirse.

---

## EC-004 — Exactamente passing score

Debe aprobar.

---

## EC-005 — Manipular score

Se ignora el score enviado por cliente.

---

## EC-006 — Enviar Answer ID de otro Quiz

Debe rechazarse o considerarse inválido de forma segura.

---

## EC-007 — Double submit

No debe producir resultados incoherentes ni Course Completion duplicado.

---

## EC-008 — Quiz ya aprobado

El sistema puede impedir nuevos intentos innecesarios.

---

## EC-009 — Último intento aprobado

El alumno aprueba normalmente.

---

## EC-010 — Último intento fallido

Queda sin intentos disponibles.

---

## EC-011 — Curso sin quiz con certificado

Al alcanzar requisitos de finalización se marca `completed` y se muestra aviso de certificado.

---

## EC-012 — Curso con quiz sin certificado

Puede aprobarse normalmente sin mostrar promesa de certificado.

---

# 14. Dependencias

## SPEC-001

Required.

## SPEC-002

Required.

## SPEC-003

Required.

## SPEC-004

Required.

## SPEC-005

Required.

Proporciona:

```text
CourseProgressService
```

y progreso de lecciones.

---

# 15. Riesgos

## RISK-001 — Score Manipulation

Mitigación:

calcular siempre del lado servidor.

---

## RISK-002 — IDOR

Mitigación:

usuario autenticado + Course/Quiz ownership.

---

## RISK-003 — Questions Modified After Attempt

Cambiar preguntas después de un intento puede dificultar reconstruir históricamente el resultado.

Mitigación:

persistir suficientes datos del intento/respuesta para conservar evidencia del resultado.

---

## RISK-004 — Double Submission

Mitigación:

una transacción de attempt, bloqueo de la secuencia por usuario y quiz, y una restricción única sobre `(user_id, quiz_id, attempt_number)`.

---

## RISK-005 — Completion Race

Progress completion y quiz submission pueden ocurrir cerca temporalmente.

Mitigación:

CourseCompletionService centralizado e idempotente mediante una transición atómica que conserva el primer `completed_at`.

---

# 16. Referencias

- `docs/project-context.md`
- `docs/architecture.md`
- `docs/data-model.md`
- `docs/testing-strategy.md`
- `docs/specs/005-student-progress/`
