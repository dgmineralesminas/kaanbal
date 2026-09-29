# SPEC-007 — Student Dashboard

Status: Ready for implementation

## 1. Objetivo

Implementar el dashboard del alumno de Kaanbal.

El dashboard debe funcionar como punto principal de acceso del estudiante a sus cursos y mostrar, de forma clara:

- cursos disponibles
- progreso
- estado académico
- acceso para continuar el curso
- estado del quiz cuando aplique
- intentos disponibles cuando aplique
- estado de aprobación
- mensaje relacionado con certificado cuando corresponda

Esta SPEC no debe volver a implementar lógica de:

- Enrollment
- Progress
- Course Completion
- Quiz

Debe consumir los servicios existentes.

---

# 2. Contexto

Las SPEC anteriores proporcionan:

```text
SPEC-001
Foundation

SPEC-002
Courses and Curriculum

SPEC-003
Enrollment

SPEC-004
Course Access and Player

SPEC-005
Student Progress

SPEC-006
Final Quiz and Course Completion
```

SPEC-007 reúne esos componentes en una experiencia orientada al alumno.

Flujo conceptual:

```text
Authenticated Student
        ↓
Student Dashboard
        ↓
Enrollments
        ↓
Courses
        ↓
Progress
        ↓
Quiz Status
        ↓
Completion Status
```

---

# 3. Alcance

Esta SPEC incluye:

- página "Mis cursos"
- listado de cursos del usuario
- matrícula activa
- matrícula completed
- manejo de matrícula revoked
- progreso por curso
- lecciones completadas
- lecciones totales
- porcentaje
- estado del curso
- acción "Continuar curso"
- acción "Ver curso"
- estado de quiz cuando aplique
- intentos utilizados/restantes cuando aplique
- estado de aprobación
- mensaje de certificado externo
- estado vacío cuando no existen cursos
- autorización server-side
- consultas eficientes

---

# 4. Fuera de Alcance

Esta SPEC NO incluye:

- administración de cursos
- administración de alumnos
- reportes administrativos
- estadísticas globales
- gráficas administrativas
- creación de Enrollment
- compras
- WooCommerce checkout
- edición de perfil
- historial de pedidos
- generación de certificado
- descarga de certificado
- envío de certificado
- email automático
- notificaciones push
- favoritos
- comentarios
- notas personales
- gamificación
- badges
- recomendaciones de cursos
- búsqueda avanzada
- marketplace
- facturación
- frontend completo de cuenta WooCommerce

---

# 5. Requisitos Funcionales

## RF-001 — Dashboard autenticado

Solo un usuario autenticado puede acceder a su Student Dashboard.

---

## RF-002 — Cursos del alumno

El dashboard debe mostrar los cursos correspondientes a las matrículas relevantes del usuario.

---

## RF-003 — Matrícula activa

Un curso con Enrollment:

`active`

debe aparecer como:

`En curso`

o equivalente.

---

## RF-004 — Matrícula completed

Un curso con Enrollment:

`completed`

debe aparecer como:

`Aprobado`

o equivalente.

---

## RF-005 — Matrícula revoked

Un curso con Enrollment:

`revoked`

no debe mostrarse como curso accesible activo.

La implementación puede:

- ocultarlo del listado principal

o:

- mostrarlo claramente como acceso revocado

según decisión de presentación.

Nunca debe mostrarse como accesible normalmente.

---

## RF-006 — Información del curso

Cada elemento debe poder mostrar:

- título
- imagen destacada cuando exista
- instructor cuando exista
- duración cuando exista

---

## RF-007 — Progreso

Para cada curso activo, el dashboard debe mostrar:

- lecciones completadas
- total de lecciones
- porcentaje

utilizando `CourseProgressService`.

---

## RF-008 — Curso completado

Un Enrollment `completed` debe mostrarse como aprobado independientemente de si el curso:

- tenía quiz
- no tenía quiz
- ofrecía certificado
- no ofrecía certificado

---

## RF-009 — Acción continuar

Un curso en progreso debe ofrecer una acción equivalente a:

`Continuar curso`

---

## RF-010 — Acción curso aprobado

Un curso completado debe ofrecer una acción equivalente a:

`Ver curso`

El alumno conserva acceso según SPEC-004.

---

## RF-011 — Destino de continuar

La acción "Continuar curso" debe enviar al alumno a una ubicación útil del curso.

Como mínimo puede abrir:

- la página principal del curso

Una estrategia de "última lección visitada" está fuera de alcance.

---

# 6. Estado de Quiz

## RF-012 — Curso sin quiz

Si un curso no requiere quiz, el dashboard no debe mostrar un quiz pendiente.

---

## RF-013 — Quiz bloqueado por progreso

Si el curso requiere quiz pero el alumno aún no tiene 100% de progreso:

el dashboard puede indicar:

`Completa las lecciones para acceder al quiz`

o equivalente.

---

## RF-014 — Quiz disponible

Si:

```text
progress = 100%
AND
quiz required
AND
not passed
AND
attempt available
```

el dashboard debe indicar que el quiz está disponible.

---

## RF-015 — Quiz aprobado

Cuando el alumno ha aprobado el quiz:

el dashboard puede mostrar:

`Quiz aprobado`

---

## RF-016 — Quiz reprobado con intentos disponibles

Debe ser posible informar que:

- el intento anterior no fue aprobado
- todavía existen intentos disponibles

---

## RF-017 — Sin intentos disponibles

Si el alumno agotó los intentos:

debe mostrarse un estado claro.

Ejemplo:

`Intentos agotados`

Esta SPEC no define un proceso administrativo para otorgar nuevos intentos.

---

## RF-018 — Intentos restantes

Cuando el quiz tiene límite, el dashboard puede mostrar:

```text
2 intentos restantes
```

---

## RF-019 — Intentos ilimitados

Cuando el quiz es ilimitado:

no debe mostrarse un número falso de intentos restantes.

Puede indicarse:

`Intentos ilimitados`

o simplemente omitirse el contador.

---

# 7. Estado de Aprobación

## RF-020 — En curso

Enrollment `active`:

`En curso`

---

## RF-021 — Aprobado

Enrollment `completed`:

`Aprobado`

---

## RF-022 — Fecha de aprobación

Cuando exista:

`completed_at`

puede mostrarse la fecha en que se aprobó el curso.

---

# 8. Certificado

## RF-023 — Curso aprobado con certificado

Si:

```text
Enrollment = completed
AND
certificate_enabled = true
```

el dashboard debe mostrar un mensaje equivalente a:

> Has aprobado este curso. En breve nos estaremos comunicando contigo para hacerte llegar tu certificado.

---

## RF-024 — Curso aprobado sin certificado

Si el curso no ofrece certificado:

debe mostrarse únicamente el estado de aprobación.

---

## RF-025 — Sin descarga

No debe existir:

- botón Descargar certificado
- URL de certificado
- PDF
- documento descargable

---

# 9. Dashboard Vacío

## RF-026 — Sin matrículas

Si el alumno no tiene cursos:

debe mostrarse un estado vacío comprensible.

Ejemplo:

> Aún no tienes cursos disponibles.

---

# 10. Requisitos No Funcionales

## RNF-001 — Autorización

El dashboard siempre debe utilizar al usuario autenticado.

No debe aceptar arbitrariamente:

`user_id`

desde el navegador para decidir de quién mostrar cursos.

---

## RNF-002 — Ownership

Un alumno no puede acceder al dashboard académico de otro mediante manipulación de URLs o parámetros.

---

## RNF-003 — Fuente de verdad

El dashboard no debe duplicar reglas de negocio.

Debe obtener información de:

- Enrollment
- CourseProgressService
- Quiz services
- Course Completion

---

## RNF-004 — Read Only

El dashboard es principalmente una vista.

Cargarlo no debe modificar:

- progreso
- Enrollment
- quiz attempts
- Course Completion

---

## RNF-005 — Rendimiento

No debe producir N+1 importantes al mostrar múltiples cursos.

---

## RNF-006 — Escaping

Todo output debe utilizar escaping apropiado.

---

## RNF-007 — Theme Independence

El dashboard no debe depender obligatoriamente de un theme específico.

---

# 11. Reglas de Negocio

## RB-001

`active` significa:

`En curso`

---

## RB-002

`completed` significa:

`Aprobado`

---

## RB-003

Un porcentaje de 100% no reemplaza el estado del Enrollment.

Ejemplo:

```text
Progress = 100%
Enrollment = active
Quiz required and pending

→ En curso
```

---

## RB-004

Enrollment es la fuente formal del estado de aprobación.

---

## RB-005

El dashboard no recalcula ni cambia Course Completion.

---

## RB-006

Quiz solo se muestra cuando el curso lo requiere.

---

## RB-007

La existencia de certificado no cambia el estado académico.

---

## RB-008

El certificado nunca es descargable desde Kaanbal.

---

# 12. Criterios de Aceptación

## AC-001 — Acceso autenticado

Un alumno autenticado puede abrir su dashboard.

---

## AC-002 — Visitante rechazado

Un visitante no autenticado no puede consultar información académica privada.

---

## AC-003 — Mostrar cursos activos

Las matrículas activas aparecen en el dashboard.

---

## AC-004 — Mostrar cursos aprobados

Las matrículas completed aparecen como aprobadas.

---

## AC-005 — No mostrar revoked como accesible

Una matrícula revoked no aparece como curso disponible normalmente.

---

## AC-006 — Mostrar progreso

Cada curso activo muestra correctamente:

- completed lessons
- total lessons
- percentage

---

## AC-007 — Progreso obtenido de servicio

El dashboard utiliza la lógica existente de Student Progress y no mantiene un porcentaje independiente.

---

## AC-008 — Mostrar En curso

Enrollment active se muestra como En curso.

---

## AC-009 — Mostrar Aprobado

Enrollment completed se muestra como Aprobado.

---

## AC-010 — Continuar curso

Un curso activo ofrece una acción para continuar/acceder al curso.

---

## AC-011 — Ver curso aprobado

Un curso completed sigue permitiendo acceso.

---

## AC-012 — Curso sin quiz

No se muestra estado de quiz pendiente para un curso que no requiere quiz.

---

## AC-013 — Quiz bloqueado

Un curso con quiz y progreso menor a 100% indica que todavía no puede presentarse.

---

## AC-014 — Quiz disponible

Con 100% y quiz pendiente con intento disponible se informa que el quiz puede presentarse.

---

## AC-015 — Quiz aprobado

El dashboard puede identificar correctamente un quiz aprobado.

---

## AC-016 — Intentos restantes

Cuando aplica límite, el dashboard muestra un estado coherente de intentos disponibles.

---

## AC-017 — Intentos agotados

Cuando no quedan intentos, se muestra claramente.

---

## AC-018 — Fecha de aprobación

Un curso completed puede mostrar `completed_at`.

---

## AC-019 — Mensaje de certificado

Un curso aprobado con certificado muestra el mensaje informativo correspondiente.

---

## AC-020 — Sin certificado descargable

No existe descarga de certificado.

---

## AC-021 — Curso aprobado sin certificado

El dashboard muestra correctamente un curso aprobado aunque no tenga certificado.

---

## AC-022 — Dashboard vacío

Un usuario sin cursos recibe un estado vacío válido.

---

## AC-023 — Protección de ownership

Un usuario no puede manipular la request para consultar cursos/progreso de otro usuario.

---

## AC-024 — Dashboard read-only

Abrir el dashboard no crea ni modifica progreso, Enrollment ni intentos.

---

## AC-025 — Sin N+1 evidente

La carga de múltiples cursos no ejecuta una consulta individual innecesaria por cada propiedad cuando exista una estrategia batch razonable.

---

# 13. Casos Límite

## EC-001 — Curso eliminado

Una matrícula vinculada a un curso eliminado no debe provocar fatal error.

---

## EC-002 — Enrollment huérfano

Debe manejarse de forma segura.

---

## EC-003 — Curso sin lecciones

Progreso:

`0%`

---

## EC-004 — 100% con quiz pendiente

Debe seguir:

`En curso`

---

## EC-005 — 100% sin quiz

Tras la evaluación correcta de SPEC-006 debería existir Enrollment completed.

El dashboard debe confiar en ese estado.

---

## EC-006 — Quiz sin intentos

Debe mostrarse el estado correspondiente.

---

## EC-007 — Curso completado con curriculum modificado

Aunque el porcentaje actual pudiera cambiar posteriormente, Enrollment `completed` continúa representando que el alumno aprobó históricamente el curso según la regla de SPEC-006.

---

## EC-008 — Certificate enabled después de aprobar

El dashboard puede mostrar el mensaje de certificado según la configuración actual.

No implica que Kaanbal genere uno.

---

# 14. Dependencias

SPEC-001:
Required

SPEC-002:
Required

SPEC-003:
Required

SPEC-004:
Required

SPEC-005:
Required

SPEC-006:
Required

---

# 15. Riesgos

## RISK-001 — N+1

Cada curso podría provocar consultas independientes de:

- progress
- quiz
- metadata

Mitigación:

batch loading y servicios agregadores.

---

## RISK-002 — Duplicación de reglas

El dashboard podría reinventar Course Completion.

Mitigación:

Enrollment es fuente formal del estado.

---

## RISK-003 — IDOR

Permitir `?user_id=123` podría exponer información de otros alumnos.

Mitigación:

identidad de sesión.

---

## RISK-004 — Dashboard con demasiado comportamiento

Convertir la vista en un nuevo dominio complicaría mantenimiento.

Mitigación:

crear un Read Model / Query Service.

---

# 16. Referencias

- `docs/project-context.md`
- `docs/architecture.md`
- `docs/data-model.md`
- `docs/testing-strategy.md`
- `docs/specs/003-woocommerce-enrollment/`
- `docs/specs/004-course-access-and-player/`
- `docs/specs/005-student-progress/`
- `docs/specs/006-final-quiz-and-course-completion/`