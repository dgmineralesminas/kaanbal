# SPEC-008 — Course Administration and Reporting

Status: Draft

## 1. Objetivo

Implementar una vista administrativa que permita consultar el estado académico de los cursos y sus alumnos.

Esta SPEC debe permitir al administrador visualizar:

- listado de cursos
- total de alumnos matriculados
- alumnos en curso
- alumnos aprobados
- alumnos con acceso revocado
- porcentaje promedio de progreso
- detalle de alumnos por curso
- progreso individual
- estado del quiz cuando aplique
- fecha de aprobación
- estado relacionado con certificado cuando aplique

La finalidad es proporcionar visibilidad operativa del avance de los alumnos sin introducir una plataforma avanzada de analytics.

---

# 2. Contexto

Las SPEC anteriores proporcionan:

```text
SPEC-001 → Foundation
SPEC-002 → Courses and Curriculum
SPEC-003 → Enrollment
SPEC-004 → Course Access and Player
SPEC-005 → Student Progress
SPEC-006 → Final Quiz and Course Completion
SPEC-007 → Student Dashboard
```

SPEC-008 agrega una vista administrativa sobre estos mismos datos.

No debe duplicar la lógica académica.

Flujo conceptual:

```text
Administrator
      ↓
Course Reporting
      ↓
Courses
      ↓
Enrollments
      ↓
Progress
      ↓
Quiz State
      ↓
Completion
```

---

# 3. Alcance

Esta SPEC incluye:

- pantalla administrativa de cursos
- métricas resumidas por curso
- total de alumnos
- alumnos activos
- alumnos aprobados
- alumnos revocados
- progreso promedio
- acceso al detalle de un curso
- listado de alumnos por curso
- nombre del alumno
- email del alumno
- estado de matrícula
- progreso individual
- lecciones completadas
- lecciones totales
- estado de quiz
- intentos utilizados cuando aplique
- fecha de aprobación
- indicación de certificado cuando aplique
- filtros básicos
- búsqueda básica de alumnos
- paginación
- consultas eficientes
- autorización administrativa

---

# 4. Fuera de Alcance

Esta SPEC NO incluye:

- edición masiva de progreso
- modificar manualmente Course Completion
- corregir scores desde el reporte
- asignar certificados
- subir certificados
- descargar certificados
- generar PDFs
- enviar certificados
- email masivo
- campañas
- CRM
- analytics avanzados
- cohort analysis
- retención
- funnels
- revenue analytics
- reportes financieros WooCommerce
- exportaciones avanzadas
- CSV obligatorio
- Excel obligatorio
- dashboards gráficos complejos
- BI
- dashboards configurables
- permisos administrativos avanzados por instructor

---

# 5. Requisitos Funcionales

## RF-001 — Listado de cursos

Un administrador autorizado debe poder consultar los cursos Kaanbal.

---

## RF-002 — Total de alumnos

Cada curso debe mostrar el número total de alumnos con Enrollment asociado.

---

## RF-003 — Alumnos en curso

Debe poder mostrar cuántas matrículas se encuentran:

`active`

---

## RF-004 — Alumnos aprobados

Debe poder mostrar cuántas matrículas se encuentran:

`completed`

---

## RF-005 — Alumnos revocados

Debe poder mostrar cuántas matrículas se encuentran:

`revoked`

---

## RF-006 — Progreso promedio

Debe poder calcular un porcentaje promedio de progreso para alumnos relevantes del curso.

---

## RF-007 — Detalle del curso

El administrador puede abrir un reporte detallado de un curso.

---

## RF-008 — Listado de alumnos

El detalle debe mostrar alumnos matriculados en ese curso.

---

## RF-009 — Identidad del alumno

Para cada alumno debe mostrarse como mínimo:

- nombre
- email

cuando estén disponibles.

---

## RF-010 — Estado de matrícula

Cada alumno debe mostrar:

- En curso
- Aprobado
- Revocado

según Enrollment.

---

## RF-011 — Progreso individual

Debe mostrar:

- lecciones completadas
- total de lecciones
- porcentaje

para alumnos activos y cuando resulte útil para completed.

---

## RF-012 — Estado de quiz

Si el curso requiere quiz, debe mostrarse un estado resumido.

Posibles estados conceptuales:

```text
No presentado
Disponible
Reprobado
Aprobado
Intentos agotados
```

---

## RF-013 — Curso sin quiz

Si el curso no requiere quiz:

debe mostrarse:

`No aplica`

o equivalente.

---

## RF-014 — Intentos utilizados

Cuando el quiz tenga límite de intentos, el administrador puede visualizar:

- intentos utilizados
- máximo permitido

---

## RF-015 — Fecha de aprobación

Un alumno `completed` debe poder mostrar:

`completed_at`

---

## RF-016 — Certificado aplicable

Si el curso tiene:

`certificate_enabled = true`

el reporte puede indicar que el curso contempla entrega externa de certificado.

---

## RF-017 — Certificado no entregado por sistema

El reporte no debe asumir ni indicar automáticamente que el certificado ya fue enviado.

Kaanbal no registra entrega del documento en esta SPEC.

---

# 6. Filtros y Búsqueda

## RF-018 — Buscar alumno

El administrador debe poder buscar por:

- nombre
- email

dentro del curso.

---

## RF-019 — Filtrar por estado

Debe poder filtrar por:

- Todos
- En curso
- Aprobados
- Revocados

---

## RF-020 — Filtrar por quiz

Cuando corresponda, puede filtrar por estados relevantes de quiz.

Como mínimo:

- aprobado
- no aprobado

si la implementación lo permite de forma sencilla.

---

## RF-021 — Paginación

El listado debe paginarse.

No cargar todos los alumnos del curso indefinidamente en una sola request.

---

# 7. Métricas del Curso

## RF-022 — Total

```text
total_enrollments
```

---

## RF-023 — Active

```text
active_count
```

---

## RF-024 — Completed

```text
completed_count
```

---

## RF-025 — Revoked

```text
revoked_count
```

---

## RF-026 — Tasa descriptiva de aprobación

Puede calcularse una métrica descriptiva:

```text
completed / relevant enrollments * 100
```

si se presenta claramente como dato operativo y no como evaluación cualitativa.

La fórmula exacta debe documentar si incluye o excluye revoked.

Preferencia MVP:

```text
completed / (active + completed) * 100
```

Revoked se reporta por separado.

---

## RF-027 — Progreso promedio

Preferencia:

calcular sobre Enrollment `active`.

Los completed ya representan aprobación formal y su progreso actual puede cambiar si el curriculum se modifica posteriormente.

La UI debe evitar mezclar métricas ambiguas.

---

# 8. Requisitos No Funcionales

## RNF-001 — Fuente de verdad

Los reportes deben reutilizar:

- Enrollment
- CourseProgressService
- Quiz services
- CourseCompletion

No deben mantener estados duplicados.

---

## RNF-002 — Read Only

Cargar reportes no debe modificar:

- progreso
- Enrollment
- Course Completion
- quiz attempts

---

## RNF-003 — Autorización

Solo usuarios con capability administrativa adecuada pueden consultar estos reportes.

---

## RNF-004 — Protección de datos

Información como email de alumnos no debe exponerse a usuarios sin autorización.

---

## RNF-005 — Rendimiento

Las consultas deben evitar N+1 y procesar datos de forma agregada/batch.

---

## RNF-006 — Paginación

Los detalles deben usar paginación server-side o equivalente razonable.

---

## RNF-007 — Escaping

Todo contenido mostrado debe escapar correctamente.

---

## RNF-008 — Prepared Queries

Filtros, búsqueda y consultas SQL deben utilizar parametrización apropiada.

---

# 9. Reglas de Negocio

## RB-001

Enrollment `active`:

`En curso`

---

## RB-002

Enrollment `completed`:

`Aprobado`

---

## RB-003

Enrollment `revoked`:

`Revocado`

---

## RB-004

El porcentaje de progreso no determina por sí mismo si un alumno aprobó.

---

## RB-005

Enrollment `completed` es la fuente formal de aprobación.

---

## RB-006

Un curso sin quiz debe mostrarse con quiz:

`No aplica`.

---

## RB-007

Certificado no influye en la aprobación.

---

## RB-008

Kaanbal no conoce en esta SPEC si el certificado físico/documental fue efectivamente enviado.

---

# 10. Criterios de Aceptación

## AC-001 — Acceso administrativo

Un administrador autorizado puede abrir el reporte de cursos.

---

## AC-002 — Usuario no autorizado

Un usuario sin capability suficiente no puede consultar los reportes.

---

## AC-003 — Listar cursos

Los cursos Kaanbal aparecen en la vista administrativa.

---

## AC-004 — Contar matrículas

Cada curso muestra un total coherente de Enrollment.

---

## AC-005 — Contar activos

El total active coincide con las matrículas activas.

---

## AC-006 — Contar aprobados

El total completed coincide con alumnos aprobados.

---

## AC-007 — Contar revoked

El total revoked coincide con matrículas revocadas.

---

## AC-008 — Abrir detalle

El administrador puede abrir el detalle de un curso válido.

---

## AC-009 — Mostrar alumnos

El detalle muestra únicamente alumnos relacionados con ese curso.

---

## AC-010 — Mostrar identidad

Nombre y email corresponden al usuario correcto.

---

## AC-011 — Mostrar estado

Cada alumno refleja correctamente:

- En curso
- Aprobado
- Revocado

---

## AC-012 — Mostrar progreso

El progreso mostrado corresponde al alumno y curso correctos.

---

## AC-013 — No confundir 100% con aprobado

Un alumno con 100% de progreso pero quiz pendiente sigue apareciendo:

`En curso`

si Enrollment continúa active.

---

## AC-014 — Curso sin quiz

Quiz se muestra como:

`No aplica`

o equivalente.

---

## AC-015 — Quiz aprobado

El estado de quiz refleja correctamente un resultado aprobado.

---

## AC-016 — Quiz reprobado

Puede identificarse correctamente un alumno que no ha aprobado el quiz.

---

## AC-017 — Intentos

Cuando aplica límite, el reporte muestra un número coherente de intentos usados.

---

## AC-018 — Fecha de aprobación

Enrollment completed muestra su fecha de finalización.

---

## AC-019 — Buscar alumno

Buscar por nombre o email devuelve resultados del curso correspondiente.

---

## AC-020 — Filtrar estado

El filtro de estado muestra únicamente matrículas correspondientes.

---

## AC-021 — Paginación

El listado puede navegar entre páginas sin devolver duplicados ni perder el filtro actual.

---

## AC-022 — Progreso promedio

La métrica agregada utiliza una fórmula documentada y datos reales.

---

## AC-023 — Reporte read-only

Abrir o filtrar el reporte no cambia estado académico.

---

## AC-024 — Datos privados protegidos

Un usuario no autorizado no puede utilizar el endpoint para consultar emails/progreso de alumnos.

---

## AC-025 — Curso inexistente

Solicitar el detalle de un Course ID inválido termina de forma segura.

---

## AC-026 — No N+1 evidente

El detalle paginado no realiza una consulta individual de progreso/quiz por cada alumno cuando una estrategia batch es razonable.

---

# 11. Casos Límite

## EC-001 — Curso sin alumnos

Debe mostrar:

```text
0 alumnos
```

y un estado vacío válido.

---

## EC-002 — Curso solo con revoked

Las métricas deben permanecer coherentes.

---

## EC-003 — Alumno eliminado

Una matrícula que referencia un usuario inexistente no debe provocar fatal error.

Debe manejarse como registro inconsistente/histórico según corresponda.

---

## EC-004 — Curso eliminado

No debe producir errores fatales.

---

## EC-005 — 100% con quiz fallido

Estado académico:

`En curso`

---

## EC-006 — Completed aunque curriculum haya cambiado

Debe continuar apareciendo:

`Aprobado`

porque Enrollment conserva el estado formal.

---

## EC-007 — Quiz unlimited

No mostrar un máximo artificial.

---

## EC-008 — Búsqueda sin coincidencias

Mostrar estado vacío dentro del filtro.

---

## EC-009 — Página fuera de rango

Resolver de forma segura.

---

# 12. Dependencias

SPEC-001:
Required

SPEC-002:
Required

SPEC-003:
Required

SPEC-004:
Required indirectly

SPEC-005:
Required

SPEC-006:
Required

SPEC-007:
Not strictly required for reporting logic, but expected to be completed in MVP sequence.

---

# 13. Riesgos

## RISK-001 — N+1

Un reporte de 50 alumnos podría ejecutar consultas individuales de progreso y quiz.

Mitigación:

queries batch/read models.

---

## RISK-002 — Métricas ambiguas

Promedios mal definidos pueden confundir.

Mitigación:

documentar claramente denominadores.

---

## RISK-003 — Datos personales

El reporte contiene información privada de alumnos.

Mitigación:

capabilities y autorización server-side.

---

## RISK-004 — Duplicación de lógica

Calcular "Aprobado" nuevamente desde progreso/quiz podría contradecir Enrollment.

Mitigación:

usar Enrollment.status.

---

# 14. Decisiones Pendientes

El plan puede concretar:

- menú administrativo exacto
- capability específica
- tamaño de página por defecto
- filtros exactos
- si el promedio incluye únicamente active
- formato de fechas
- orden por defecto
- posible export CSV futuro

Exportación no forma parte obligatoria de esta SPEC.

---

# 15. Referencias

- `docs/project-context.md`
- `docs/architecture.md`
- `docs/data-model.md`
- `docs/testing-strategy.md`
- `docs/specs/003-woocommerce-enrollment/`
- `docs/specs/005-student-progress/`
- `docs/specs/006-final-quiz-and-course-completion/`
- `docs/specs/007-student-dashboard/`