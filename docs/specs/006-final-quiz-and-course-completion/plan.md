# Plan — SPEC-006 Final Quiz and Course Completion

## 1. Resumen Técnico

La SPEC se divide conceptualmente en dos responsabilidades:

```text
Quiz
+
Course Completion Policy
```

Flujo con quiz:

```text
Student
   ↓
100% Progress?
   ↓ Yes
Final Quiz
   ↓
Submit Answers
   ↓
Server Evaluation
   ↓
Attempt
   ↓
Passed?
   ↓ Yes
CourseCompletionService
   ↓
Enrollment → completed
```

Sin quiz:

```text
Student reaches 100%
       ↓
CourseCompletionService
       ↓
Enrollment → completed
```

---

# 2. Configuración del Curso

Agregar metadata/configuración equivalente a:

```text
_kaanbal_requires_final_quiz
_kaanbal_certificate_enabled
```

La configuración exacta puede adaptarse a la arquitectura existente.

No almacenar información redundante si la existencia de Quiz puede representar la configuración de forma inequívoca.

---

# 3. Persistencia — Quiz

Quiz editorial:

```text
kaanbal_quiz
```

Relación:

```text
Quiz → Course
```

Máximo:

```text
1 final quiz activo por Course
```

Metadata/configuración:

```text
passing_score
max_attempts
```

Decisión:

```text
max_attempts = NULL → unlimited
max_attempts >= 1   → limited
max_attempts = 0    → invalid
```

`NULL` es la única representación de intentos ilimitados.

---

# 4. Questions

Tabla:

```text
{$wpdb->prefix}kaanbal_questions
```

Columnas mínimas:

```text
id
quiz_id
question_text
question_type
position
active
created_at
updated_at
```

Tipo MVP:

```text
single_choice
```

---

# 5. Answers

Tabla:

```text
{$wpdb->prefix}kaanbal_question_answers
```

Columnas:

```text
id
question_id
answer_text
is_correct
position
created_at
updated_at
```

La aplicación debe garantizar exactamente una correcta para single choice.

---

# 6. Quiz Attempts

Tabla:

```text
{$wpdb->prefix}kaanbal_quiz_attempts
```

Columnas:

```text
id
user_id
course_id
quiz_id
attempt_number
status
score
passed
started_at
completed_at
created_at
updated_at
```

Restricción obligatoria:

```text
UNIQUE(user_id, quiz_id, attempt_number)
```

Estados conceptuales:

```text
in_progress
passed
failed
```

---

# 7. Attempt Answers

Tabla:

```text
{$wpdb->prefix}kaanbal_quiz_attempt_answers
```

Campos:

```text
id
attempt_id
question_id
answer_id
is_correct
question_text_snapshot
answer_text_snapshot
created_at
```

---

# 8. Integridad Histórica

Como las preguntas pueden modificarse después, la implementación debe conservar evidencia suficiente del resultado histórico.

Como mínimo:

- question ID
- selected answer ID
- correctness evaluada en ese momento
- question text snapshot
- selected answer text snapshot

Los snapshots de texto son parte del MVP. Así un intento sigue siendo
interpretable aunque después se editen o eliminen preguntas y respuestas.

---

# 9. QuizEligibilityService

Responsabilidad:

```text
canTakeQuiz(userId, courseId)
```

Debe comprobar:

- acceso
- curso requiere quiz
- progress = 100%
- quiz válido
- intentos disponibles
- no estar ya aprobado cuando corresponda

---

# 10. Attempt Limit

Lógica:

```text
attemptsUsed < maxAttempts
```

o:

```text
unlimited = true
```

Los intentos se cuentan únicamente para submissions válidas registradas según política implementada.

---

# 11. QuizSubmissionService

Input conceptual:

```text
quiz_id
answers[]
```

Usuario:

obtenido de sesión.

Nunca aceptar autoridad de:

```text
user_id
score
passed
```

desde cliente.

---

# 12. Evaluation

Para cada pregunta activa:

1. resolver la pregunta desde DB
2. validar que pertenece al quiz
3. resolver answer seleccionada
4. validar que pertenece a la pregunta
5. evaluar `is_correct`

Después:

```text
score = correct / total * 100
```

---

# 13. Empty Quiz

Un quiz con cero preguntas no debe ser presentable.

No utilizar:

```text
0 / 0
```

como score válido.

---

# 14. CourseCompletionPolicy

Servicio de dominio/aplicación:

```text
isCourseComplete(userId, courseId)
```

Regla:

```text
IF progress < 100:
    false

IF course has no required quiz:
    true

IF course requires quiz:
    return hasPassedQuiz
```

---

# 15. CourseCompletionService

Responsabilidad:

```text
evaluate(userId, courseId)
```

Si corresponde:

```text
Enrollment ACTIVE
→ COMPLETED
```

y establece:

```text
completed_at
```

Debe ser idempotente.

La transición se realizará con una actualización atómica de la matrícula: solo
puede cambiar `active` a `completed`, y `completed_at` se establece una sola vez.
Una reevaluación posterior no restaura `active` ni reemplaza la fecha inicial.

---

# 16. Cuándo Evaluar Completion

Debe evaluarse al menos:

### Después de completar una lección

Especialmente la última.

### Después de aprobar un quiz

Esto permite ambos flujos:

```text
sin quiz
100% lessons
→ completed
```

y:

```text
con quiz
quiz passed
→ completed
```

---

# 17. Integración con SPEC-005

SPEC-005 deberá invocar el servicio de completion después de una completion válida.

Esto NO cambia la definición del porcentaje.

---

# 18. Enrollment Completed

No crear una tabla duplicada de Course Completion.

Usar:

```text
enrollments.status
enrollments.completed_at
```

como estado formal actual.

---

# 19. Certificado

No existe Certificate Generator.

No existe PDF service.

No existe download endpoint.

Config conceptual:

```text
certificate_enabled: bool
```

Uso únicamente para presentación/mensaje posterior a Course Completion.

---

# 20. Success Message

Con certificado:

```text
Has aprobado el curso.

En breve nos estaremos comunicando contigo
para hacerte llegar tu certificado.
```

Sin certificado:

```text
Has completado y aprobado el curso.
```

El copy final puede ajustarse en UI.

---

# 21. Admin UI — Quiz

Debe permitir:

- crear quiz
- asignarlo al curso
- passing score
- max attempts
- unlimited attempts
- preguntas
- respuestas
- respuesta correcta
- orden

No se requiere builder sofisticado.

---

# 22. Admin UI — Course Completion Configuration

Debe permitir configurar:

```text
Final quiz required: Yes/No
Certificate follow-up: Yes/No
```

La UI exacta puede adaptarse al modelo implementado.

---

# 23. Seguridad

Obligatorio:

- autenticación
- CourseAccessService
- CSRF
- ownership
- quiz/course relationship
- question/quiz relationship
- answer/question relationship
- server scoring
- prepared queries

---

# 24. Attempts Concurrency

Asignar:

```text
attempt_number
```

se realiza dentro de una transacción. La implementación bloquea la secuencia de
attempts del mismo `(user_id, quiz_id)`, vuelve a comprobar elegibilidad y límite
dentro de la transacción, y crea el attempt con la restricción única
`(user_id, quiz_id, attempt_number)`. Un conflicto de unicidad se reintenta desde
la comprobación de elegibilidad; nunca se usa `SELECT MAX(...) + 1` sin bloqueo.

---

# 25. Testing Unitario

Candidatos:

```text
QuizScoreCalculatorTest
QuizEligibilityServiceTest
CourseCompletionPolicyTest
CourseCompletionServiceTest
```

---

# 26. Score Tests

```text
10/10 → 100
8/10 → 80
7/10 → 70
0/10 → 0
```

---

# 27. Passing Tests

```text
passing 80 / score 79 → failed
passing 80 / score 80 → passed
passing 80 / score 100 → passed
```

---

# 28. Course Completion Tests

Obligatorios:

```text
progress 90 + no quiz
→ active

progress 100 + no quiz
→ completed

progress 100 + quiz required + no attempt
→ active

progress 100 + failed quiz
→ active

progress 100 + passed quiz
→ completed
```

---

# 29. Certificate Configuration Tests

```text
completed + certificate yes
→ certificate follow-up message

completed + certificate no
→ normal completion message
```

Ningún test debe esperar un PDF.

---

# 30. Security Tests

Casos:

```text
anonymous submission
user A → attempt for user B
quiz from another course
question from another quiz
answer from another question
client forged score
client forged passed=true
missing/invalid CSRF
```

---

# 31. Attempt Tests

```text
limited attempts
last allowed attempt
attempt after limit
unlimited
already passed
```

---

# 32. Idempotency Tests

```text
completion evaluated twice
completion evaluated N times
passed quiz submitted/replayed
```

`completed_at` debe permanecer coherente.

---

# 33. Scope Protection

No implementar:

- PDFs
- certificado descargable
- email attachment
- public verification
- admin analytics completo

---

# 34. Secuencia de Implementación

```text
1. schema migrations
2. Quiz configuration
3. Question repository
4. Answer repository
5. Attempt repository
6. Quiz eligibility
7. Quiz submission/evaluation
8. attempt limits
9. CourseCompletionPolicy
10. CourseCompletionService
11. integration with progress
12. integration with quiz pass
13. success messages
14. certificate informational configuration
15. admin UI
16. tests
17. security tests
18. quality gate
```

---

# 35. Condición de Finalización

SPEC-006 estará lista para auditoría cuando:

- quiz pueda ser opcional
- preguntas/respuestas funcionen
- score sea server-side
- passing score funcione
- intentos funcionen
- límites sean respetados
- 100% sin quiz complete curso
- 100% + quiz failed no complete curso
- 100% + quiz passed complete curso
- Enrollment cambie de forma idempotente
- certificado sea únicamente informativo
- no exista descarga/generación de documentos
- tests pasen
- Quality Gate pase
- `Ready for audit: Yes`
