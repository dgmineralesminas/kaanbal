# Plan — SPEC-007 Student Dashboard

## 1. Resumen Técnico

SPEC-007 implementará una capa de lectura orientada al alumno.

Preferencia:

```text
StudentDashboardQuery
```

o servicio equivalente que agregue información de:

```text
Enrollment
Course
Progress
Quiz
Completion
```

El template no debe realizar consultas de negocio directamente.

---

# 2. Flujo

```text
Authenticated User
      ↓
StudentDashboardController
      ↓
StudentDashboardQuery
      ↓
Enrollments
      ↓
Courses
      ↓
Batch Progress
      ↓
Quiz State
      ↓
View Models
      ↓
Dashboard Template
```

---

# 3. StudentDashboardQuery

Responsabilidad:

```text
getDashboard(userId)
```

Debe devolver una estructura preparada para presentation.

Ejemplo conceptual:

```text
courses: [
    {
        course_id,
        title,
        image,
        instructor,
        duration,

        enrollment_status,
        completed_at,

        progress: {
            completed,
            total,
            percentage
        },

        quiz: {
            required,
            status,
            attempts_remaining
        },

        certificate_enabled,
        access_url
    }
]
```

---

# 4. View Model

La vista no debe decidir reglas.

Ejemplo:

```text
status_label = "En curso"
```

puede resolverse antes del template.

Esto permite que presentation sea sencilla.

---

# 5. Enrollment Query

Obtener matrículas relevantes del usuario.

Evitar:

```text
all users
→ filter in PHP
```

Consultar solo el usuario actual.

---

# 6. Revoked

Preferencia MVP:

no mostrar cursos revoked en el listado principal.

Si posteriormente se requiere historial de accesos revocados, será una feature separada.

---

# 7. Course Batch Loading

Después de obtener enrollments:

```text
course IDs
→ batch resolve courses
```

Evitar una consulta individual repetitiva por curso.

---

# 8. Progress Batch Loading

Idealmente obtener el progreso de múltiples cursos de forma eficiente.

Si `CourseProgressService` actual solo opera por curso:

puede incorporarse una operación batch sin duplicar sus reglas.

Ejemplo:

```text
getProgressForCourses(userId, courseIds)
```

---

# 9. Quiz State Query

Para cada curso con quiz:

resolver únicamente información necesaria para dashboard:

```text
required
passed
attempts_used
max_attempts
attempts_remaining
available
```

No cargar todas las preguntas/respuestas.

---

# 10. Course Status

Mapeo:

```text
Enrollment active
→ EN_CURSO

Enrollment completed
→ APROBADO
```

No inferir Aprobado desde porcentaje.

---

# 11. Quiz State

Estados conceptuales de presentación:

```text
NOT_REQUIRED
LOCKED
AVAILABLE
FAILED_CAN_RETRY
PASSED
NO_ATTEMPTS_LEFT
```

No es obligatorio implementar enum si una estructura más simple basta.

---

# 12. Certificate Message

Condición:

```text
enrollment completed
AND
certificate_enabled
```

Mensaje:

```text
Has aprobado este curso.

En breve nos estaremos comunicando contigo
para hacerte llegar tu certificado.
```

No enlazar documentos.

---

# 13. Routing

Debe existir una URL o endpoint de alumno equivalente a:

```text
/mis-cursos/
```

La URL exacta puede adaptarse a la arquitectura WordPress existente.

---

# 14. Integración WooCommerce

No es necesario cargar pedidos WooCommerce en el dashboard.

La fuente académica es Enrollment.

---

# 15. Template

Propuesta:

```text
templates/
└── student/
    └── dashboard.php
```

Posibles partials:

```text
course-card.php
empty-state.php
quiz-status.php
```

Solo si reducen duplicación real.

---

# 16. Course Card

Información mínima:

```text
Course title
Image
Instructor
Duration
Status
Progress
Primary action
```

Información de Quiz solo cuando aplica.

---

# 17. Primary Action

Enrollment active:

```text
Continuar curso
```

Enrollment completed:

```text
Ver curso
```

Ambas acciones deben utilizar las URLs válidas definidas por SPEC-004.

---

# 18. Empty State

Si no existen cursos:

```text
Aún no tienes cursos disponibles.
```

No se requiere catálogo/upsell.

---

# 19. Security

Obligatorio:

- usuario autenticado
- identidad obtenida de sesión
- no aceptar user ID arbitrario
- escaping
- URLs seguras
- no exposición de Quiz Answers
- no exposición de datos de otros estudiantes

---

# 20. Read Only

Carga normal:

```text
GET dashboard
```

no debe:

- completar lecciones
- cambiar Enrollment
- registrar Quiz Attempt
- modificar Course Completion

---

# 21. Testing Unitario

Candidatos:

```text
StudentDashboardQueryTest
StudentCourseViewModelTest
QuizDashboardStateTest
```

---

# 22. Testing de Integración

Candidatos:

```text
StudentDashboardAccessTest
StudentDashboardCoursesTest
StudentDashboardProgressTest
StudentDashboardQuizTest
StudentDashboardCompletedCourseTest
```

---

# 23. Security Tests

Obligatorios:

```text
anonymous
user A cannot view user B
forged user_id ignored/rejected
revoked course inaccessible
```

---

# 24. Dashboard State Tests

Casos:

```text
no courses

active + 30%

active + 100% + quiz pending

active + failed quiz + attempts remaining

active + no attempts remaining

completed + certificate

completed + no certificate

course without quiz
```

---

# 25. Performance

Auditar queries con varios cursos.

Escenario razonable:

```text
10–20 enrolled courses
```

No se requiere benchmark formal, pero sí evitar N+1 evidente.

---

# 26. No Analytics

No calcular:

- promedio global de alumnos
- tasa de aprobación
- número total de estudiantes del curso
- revenue

Eso pertenece al dashboard administrativo.

---

# 27. Secuencia de Implementación

```text
1. dashboard route/controller
2. StudentDashboardQuery
3. enrollment loading
4. batch Course loading
5. progress aggregation
6. quiz state aggregation
7. View Models
8. dashboard template
9. active state
10. completed state
11. certificate message
12. empty state
13. security
14. performance review
15. tests
16. quality gate
```

---

# 28. Riesgos Técnicos

## TECH-001

Dashboard Query puede convertirse en "God Service".

Mitigación:

mantener solo composición de lectura.

## TECH-002

N+1.

Mitigación:

batch.

## TECH-003

Recalcular Course Completion accidentalmente.

Mitigación:

dashboard read-only.

---

# 29. Decisiones

## DEC-001

Enrollment es fuente del estado:

- active
- completed

## DEC-002

Progress solo representa porcentaje.

## DEC-003

Quiz status se obtiene de SPEC-006.

## DEC-004

No mostrar revoked en listado principal del MVP.

## DEC-005

No existe descarga de certificado.

## DEC-006

No implementar last lesson visited.

`Continuar curso` puede abrir la vista del curso.

---

# 30. Condición de Finalización

SPEC-007 está lista para auditoría cuando:

- alumno autenticado puede abrir dashboard
- muestra únicamente sus cursos
- active aparece En curso
- completed aparece Aprobado
- progreso es correcto
- quiz status es correcto
- certificate message es correcto
- no existe descarga
- empty state funciona
- ownership está protegido
- dashboard es read-only
- performance no presenta N+1 evidente
- tests pasan
- Quality Gate pasa
- `Ready for audit: Yes`