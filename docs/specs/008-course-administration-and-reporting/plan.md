# Plan — SPEC-008 Course Administration and Reporting

## 1. Resumen Técnico

SPEC-008 implementará una capa de lectura administrativa.

Preferencia:

```text
CourseReportingQuery
```

y:

```text
CourseStudentsQuery
```

como servicios orientados a lectura.

La lógica académica no debe implementarse nuevamente.

---

# 2. Flujo de Resumen

```text
Admin Request
     ↓
Capability Check
     ↓
CourseReportingQuery
     ↓
Courses
     ↓
Enrollment Aggregates
     ↓
Report View
```

---

# 3. Flujo de Detalle

```text
Admin
  ↓
Course Detail
  ↓
CourseStudentsQuery
  ↓
Paginated Enrollments
  ↓
Batch Users
  ↓
Batch Progress
  ↓
Batch Quiz State
  ↓
View Models
  ↓
Admin Table
```

---

# 4. Componentes

Posibles componentes:

```text
CourseReportingQuery
CourseStudentsQuery
CourseReportViewModel
StudentCourseReportViewModel
AdminCourseReportController
```

No crear componentes separados si no tienen responsabilidad real.

---

# 5. Menú Administrativo

Puede añadirse:

```text
Kaanbal
└── Reportes
```

o:

```text
Kaanbal
└── Cursos
    └── Reporte
```

La decisión debe seguir la estructura administrativa existente.

---

# 6. Course Summary Query

Debe obtener por curso:

```text
course_id
title
active_count
completed_count
revoked_count
total_count
```

Idealmente mediante agregación SQL.

Evitar contar cada estado mediante requests independientes si puede resolverse en una consulta razonable.

---

# 7. Approval Rate

Fórmula preferida:

```text
completed / (active + completed) * 100
```

Si:

```text
active + completed = 0
```

resultado:

```text
0%
```

Revoked se excluye del denominador porque ya no representa alumnado actualmente participante.

Esta métrica debe presentarse de forma descriptiva.

---

# 8. Average Progress

Preferencia:

calcular progreso promedio de:

```text
Enrollment active
```

No utilizar completed para la media operativa porque esos alumnos ya aprobaron y el curriculum actual puede haber cambiado posteriormente.

---

# 9. Course Detail Query

Input:

```text
course_id
page
per_page
search
status
quiz_filter
```

Debe validar todos los parámetros.

---

# 10. Paginación

Preferencia inicial:

```text
20–50 alumnos por página
```

La cifra exacta puede seleccionarse durante implementación.

Debe existir:

- total
- current page
- total pages

---

# 11. User Batch Loading

Después de obtener Enrollment:

```text
user_ids
→ batch user resolution
```

No ejecutar una consulta individual adicional innecesaria por usuario.

---

# 12. Progress Batch

Preferir un servicio de lectura capaz de resolver progreso para varios alumnos del mismo curso.

Conceptualmente:

```text
getProgressForUsers(courseId, userIds)
```

Debe reutilizar las reglas de `CourseProgressService`.

---

# 13. Quiz Batch

No cargar preguntas.

Solo estado de attempt relevante:

```text
passed
failed
attempts_used
max_attempts
```

Puede resolverse mediante agregación/repository.

---

# 14. Estado Académico

No inferir desde Progress.

Mapeo:

```text
active    → En curso
completed → Aprobado
revoked   → Revocado
```

---

# 15. Curso sin Quiz

View Model:

```text
quiz_required = false
quiz_status = NOT_APPLICABLE
```

---

# 16. Course with Quiz

Estados útiles:

```text
NOT_STARTED
AVAILABLE
FAILED
PASSED
EXHAUSTED
```

La representación puede reutilizar lógica de SPEC-006/007.

---

# 17. Search

Buscar de forma segura en:

- display name
- user email

No concatenar términos directamente en SQL.

---

# 18. Status Filter

Valores permitidos:

```text
all
active
completed
revoked
```

No aceptar valores arbitrarios como SQL.

---

# 19. Quiz Filter

Opcional inicial:

```text
all
passed
not_passed
```

Puede mantenerse simple.

---

# 20. Orden

Orden por defecto sugerido:

```text
student display name ASC
```

o:

```text
enrolled_at DESC
```

Debe ser determinista.

---

# 21. Admin Table

Columnas mínimas:

```text
Alumno
Email
Estado
Progreso
Quiz
Intentos
Fecha de aprobación
```

Cuando el curso no tiene quiz:

```text
Quiz → No aplica
Intentos → —
```

---

# 22. Course Summary UI

Ejemplo:

```text
Curso                        Alumnos   En curso   Aprobados   Revocados
-----------------------------------------------------------------------
Hipertrofia basada en ciencia  125       41          84          0
```

También puede mostrar:

```text
Progreso promedio: 58%
Tasa de aprobación: 67%
```

si las métricas están calculadas de acuerdo con esta SPEC.

---

# 23. Course Detail UI

Ejemplo:

```text
Alumno          Progreso    Quiz          Estado
---------------------------------------------------
Ana López         100%      Aprobado      Aprobado
Carlos Pérez       70%      Bloqueado     En curso
Luis García       100%      Reprobado     En curso
Sofía Martínez    100%      No aplica     Aprobado
```

---

# 24. Certificate Indicator

Puede existir una indicación a nivel curso:

```text
Certificado externo: Sí
```

No mostrar:

```text
Certificado enviado
```

porque el sistema no conoce ese dato.

---

# 25. Read Only

Ninguna operación normal del reporte debe:

- completar curso
- completar lección
- aprobar quiz
- cambiar Enrollment
- crear attempts

---

# 26. Seguridad

Debe validar:

- authentication
- administrative capability
- Course ID
- filters
- pagination
- search
- output escaping

---

# 27. Capability

Preferir capability Kaanbal específica si existe infraestructura para ello.

Evitar hardcodear exclusivamente:

```text
manage_options
```

si existe una opción más adecuada.

---

# 28. Testing Unitario

Candidatos:

```text
CourseReportMetricsTest
CourseReportViewModelTest
StudentCourseReportViewModelTest
```

---

# 29. Integration Tests

Candidatos:

```text
CourseReportingQueryTest
CourseStudentsQueryTest
AdminCourseReportAccessTest
CourseReportFiltersTest
CourseReportPaginationTest
```

---

# 30. Metrics Tests

Casos:

```text
0 enrollments
5 active
5 completed
2 revoked
```

Verificar cada total.

---

# 31. Approval Rate Tests

```text
5 active + 5 completed
→ 50%

0 active + 5 completed
→ 100%

0 active + 0 completed
→ 0%
```

Revoked no altera el denominador.

---

# 32. Progress Tests

Verificar que:

```text
User A progress != User B progress
```

y que completed no se recalifique por porcentaje.

---

# 33. Quiz Tests

Casos:

```text
course without quiz
not started
failed
passed
attempts exhausted
unlimited attempts
```

---

# 34. Security Tests

Obligatorios:

```text
anonymous admin endpoint
student user
user without capability
forged course ID
invalid status filter
SQL-like search input
```

---

# 35. Pagination Tests

Verificar:

- page 1
- page 2
- última página
- fuera de rango
- filtros persistentes

---

# 36. Performance Review

Escenario mínimo recomendado:

```text
1 course
100 enrollments
paginated 25 rows
```

Revisar query count.

No se requiere benchmark formal.

---

# 37. Scope Protection

No implementar:

- manual score editing
- manual course completion
- PDF
- certificate delivery
- CRM
- emails
- advanced BI

---

# 38. Secuencia de Implementación

```text
1. admin route/menu
2. authorization
3. course summary query
4. enrollment aggregates
5. metrics
6. summary UI
7. detail query
8. pagination
9. search
10. status filters
11. batch users
12. batch progress
13. batch quiz states
14. detail UI
15. security
16. performance review
17. tests
18. quality gate
```

---

# 39. Decisiones

## DEC-001

Enrollment.status es la fuente formal del estado académico.

## DEC-002

Approval rate excluye revoked.

## DEC-003

Average progress se calcula preferentemente sobre active.

## DEC-004

Certificate delivery status no se administra.

## DEC-005

Reportes son read-only.

## DEC-006

Paginación es obligatoria.

---

# 40. Condición de Finalización

SPEC-008 estará lista para auditoría cuando:

- administrador autorizado pueda abrir reportes
- usuarios sin permiso sean rechazados
- cursos muestren métricas correctas
- detalle liste alumnos correctos
- progreso sea correcto
- estado académico sea correcto
- quiz state sea correcto
- búsqueda funcione
- filtros funcionen
- paginación funcione
- emails/datos estén protegidos
- no exista N+1 evidente
- el reporte sea read-only
- tests pasen
- Quality Gate pase
- `Ready for audit: Yes`