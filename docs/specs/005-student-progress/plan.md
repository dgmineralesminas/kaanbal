# Plan — SPEC-005 Student Progress

## 1. Resumen Técnico

SPEC-005 introducirá persistencia transaccional de completion por usuario/lección.

Flujo de escritura:

```text
Authenticated User
      ↓
Complete Lesson Request
      ↓
CSRF Validation
      ↓
Resolve Lesson
      ↓
Resolve Course
      ↓
CourseAccessService
      ↓
Validate Curriculum Relationship
      ↓
CompleteLesson Service
      ↓
LessonProgressRepository
```

Flujo de lectura:

```text
Course
  ↓
CurriculumService
  ↓
Lesson IDs
  ↓
LessonProgressRepository
  ↓
completed lesson IDs
  ↓
ProgressCalculator
```

---

# 2. Persistencia

Tabla:

```text
{$wpdb->prefix}kaanbal_lesson_progress
```

Modelo recomendado:

```text
id
user_id
lesson_id
status
completed_at
created_at
updated_at
```

Para MVP:

```text
status = completed
```

puede ser la única condición persistida.

Alternativamente, la existencia del registro puede representar completion.

Debe elegirse la forma más simple coherente con `docs/data-model.md`.

---

# 3. Constraint

Debe existir:

```text
UNIQUE(user_id, lesson_id)
```

Esto proporciona defensa contra:

- double click
- retries
- concurrencia

---

# 4. Course ID

Preferencia:

NO almacenar `course_id` en lesson progress inicialmente.

Razón:

```text
Lesson → Module → Course
```

ya define la relación.

Persistir también `course_id` añadiría riesgo de inconsistencia.

El Course ID se deriva cuando es necesario.

---

# 5. Schema Migration

Debe realizarse mediante Schema Manager.

Debe:

- ser idempotente
- conservar datos existentes
- crear índices
- actualizar schema version

---

# 6. LessonProgressRepository

Responsabilidades previstas:

```text
complete(userId, lessonId)
isCompleted(userId, lessonId)
findCompletedLessonIds(userId, lessonIds)
countCompleted(userId, lessonIds)
```

Evitar métodos vinculados directamente a UI.

---

# 7. CompleteLesson Service

Servicio principal de escritura.

Responsabilidades:

1. obtener usuario autenticado
2. resolver Lesson
3. resolver Course
4. validar jerarquía
5. verificar acceso mediante CourseAccessService
6. completar idempotentemente
7. devolver resultado

No debe confiar en un `user_id` enviado por el navegador.

---

# 8. Ownership

Entrada permitida conceptualmente:

```text
lesson_id
course context if required
nonce/token
```

NO:

```text
user_id arbitrario
```

El usuario debe obtenerse de la sesión/autenticación WordPress.

---

# 9. TOCTOU

El flujo de escritura debe minimizar la ventana entre:

```text
authorization
→ persistence
```

No debe existir lógica como:

```text
request A:
check access

much later

insert progress blindly
```

Cuando el acceso pueda mutar concurrentemente, las invariantes relevantes deben comprobarse dentro del flujo de escritura.

La implementación debe documentar cómo protege:

- matrícula válida
- Lesson → Course
- uniqueness

---

# 10. ProgressCalculator

Debe ser lógica derivada.

Input conceptual:

```text
totalLessons
completedLessons
```

Output:

```text
percentage
```

Reglas:

```text
total = 0 → 0
completed = 0 → 0
completed >= total → 100
otherwise → completed / total * 100
```

---

# 11. Precisión

La lógica puede devolver un valor decimal.

Ejemplo:

```text
10 / 11 = 90.9090...
```

La UI puede formatearlo.

Ejemplo:

```text
91%
```

o:

```text
90.9%
```

La precisión visual debe ser consistente.

---

# 12. Progress Service

Puede existir:

```text
CourseProgressService
```

Responsabilidad:

```text
getProgress(userId, courseId)
```

Resultado conceptual:

```text
total_lessons
completed_lessons
percentage
completed_lesson_ids
```

---

# 13. Curriculum Actual

El denominador siempre debe provenir del curriculum actual.

No utilizar un valor histórico guardado de:

```text
total_lessons
```

como fuente de verdad.

---

# 14. Completions Huérfanas

Si existe progress para lesson ID que ya no pertenece al curso:

debe ignorarse en cálculo actual.

No es obligatorio eliminarlo físicamente.

Esto preserva historial y evita operaciones destructivas.

---

# 15. Endpoint de Escritura

Utilizar mecanismo soportado por WordPress.

Opciones:

- REST API
- AJAX
- admin-post
- endpoint propio controlado

Debe soportar:

- autenticación
- CSRF
- respuesta clara
- manejo de errores

La elección debe integrarse con la arquitectura existente.

---

# 16. Respuestas

Resultados conceptuales:

```text
COMPLETED
ALREADY_COMPLETED
UNAUTHORIZED
INVALID_LESSON
COURSE_MISMATCH
ACCESS_DENIED
```

No es obligatorio implementar enums si agrega complejidad innecesaria.

---

# 17. Course View

El curriculum renderizado debe obtener completions en batch.

Evitar:

```text
foreach lesson:
    query isCompleted()
```

Preferir:

```text
all lesson IDs
→ one/bounded query
→ completed IDs set
```

---

# 18. Lesson View

Debe consultar:

```text
is current lesson completed?
```

Puede utilizar el mismo dataset/servicio de progreso.

---

# 19. UI

Lección pendiente:

```text
[ Marcar como completada ]
```

Lección completada:

```text
✓ Completada
```

No se requiere diseño final sofisticado.

---

# 20. Progressive Enhancement

La operación debe poder ser segura aunque JavaScript sea manipulado.

JavaScript mejora UX.

No constituye el control de seguridad.

---

# 21. Course Progress UI

Debe poder mostrar algo equivalente a:

```text
7 de 10 lecciones completadas
70%
```

---

# 22. Curriculum UI

Ejemplo:

```text
Módulo 1

✓ Introducción
✓ Anatomía
○ Fundamentos

Módulo 2

○ Programación
```

El icono/estilo exacto no es requisito contractual.

---

# 23. No Course Completion

Al alcanzar:

```text
100%
```

NO ejecutar todavía:

```text
enrollment → completed
```

porque la política final requerirá quiz.

Esto pertenece a SPEC posterior.

---

# 24. Seguridad

Controles:

- logged-in user
- CSRF
- access policy
- Lesson type
- Module relation
- Course relation
- input validation
- prepared DB queries
- ownership

---

# 25. SQL

Toda entrada variable debe:

- parametrizarse
- validarse

No concatenar IDs del request en SQL.

---

# 26. Testing Unitario

Candidatos:

```text
ProgressCalculatorTest
CompleteLessonServiceTest
CourseProgressServiceTest
```

---

# 27. ProgressCalculator Tests

Casos:

```text
0/0 → 0
0/10 → 0
1/10 → 10
3/10 → 30
10/10 → 100
10/11 → expected decimal
```

---

# 28. CompleteLesson Tests

Casos:

```text
authorized
already completed
no enrollment
revoked enrollment
lesson does not exist
course mismatch
different user
```

---

# 29. Integration Tests

Candidatos:

```text
LessonProgressRepositoryTest
CompleteLessonEndpointTest
CourseProgressIntegrationTest
```

---

# 30. Idempotency Tests

Obligatorios:

```text
same lesson twice
same lesson many times
simulated duplicate insert
```

Si el test framework lo permite, añadir escenario razonable de concurrencia.

---

# 31. Security Tests

Obligatorios:

```text
anonymous request
missing nonce
invalid nonce
user A tries user B
course A / lesson B
revoked enrollment
```

---

# 32. Dynamic Curriculum Tests

Casos:

```text
10/10 = 100

add lesson
10/11 < 100

remove incomplete lesson
percentage recalculates

remove completed lesson
orphan completion ignored
```

---

# 33. Performance Tests

No se requiere benchmark formal.

Sí revisar que Course View no genere N+1 por cada lesson.

---

# 34. Scope Protection

No implementar:

- quiz logic
- certificate
- enrollment completion
- video tracking

---

# 35. Secuencia de Implementación

```text
1. schema migration
2. LessonProgressRepository
3. ProgressCalculator
4. CourseProgressService
5. CompleteLessonService
6. authorization integration
7. write endpoint
8. Course View integration
9. Lesson View integration
10. progress UI
11. unit tests
12. integration tests
13. security tests
14. dynamic curriculum tests
15. quality gate
```

---

# 36. Riesgos Técnicos

## TECH-001

Double-click/concurrencia.

Mitigación:

unique constraint + idempotent service.

## TECH-002

TOCTOU entre access check y write.

Mitigación:

escritura con invariantes revalidadas.

## TECH-003

N+1 en curriculum.

Mitigación:

batch completions.

## TECH-004

Guardar porcentaje derivado.

Mitigación:

no persistirlo.

---

# 37. Decisiones

## DEC-001

Completion es manual.

## DEC-002

`UNIQUE(user_id, lesson_id)`.

## DEC-003

No almacenar course ID salvo que se apruebe mediante Change Request con justificación.

## DEC-004

No almacenar porcentaje.

## DEC-005

100% no completa el curso todavía.

## DEC-006

Quiz no cuenta en porcentaje.

## DEC-007

No existe undo completion en MVP.

---

# 38. Condición de Finalización

SPEC-005 estará lista para auditoría cuando:

- completion funcione
- sea idempotente
- ownership esté protegido
- IDOR esté protegido
- acceso sea requerido
- porcentaje sea correcto
- curso vacío sea seguro
- curriculum dinámico recalcule correctamente
- Course View muestre progreso
- Lesson View muestre estado
- no se complete enrollment automáticamente
- tests pasen
- Quality Gate pase
- `Ready for audit: Yes`