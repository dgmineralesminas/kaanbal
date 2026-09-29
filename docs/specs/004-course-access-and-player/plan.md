# Plan — SPEC-004 Course Access and Player

## 1. Resumen Técnico

SPEC-004 introducirá una capa de acceso académico entre Enrollment y las vistas del curso.

Flujo conceptual:

```text
HTTP Request
    ↓
Course / Lesson resolver
    ↓
CourseAccessService
    ↓
Enrollment Repository
    ↓
Authorized?
    ├── No → Deny
    └── Yes
          ↓
    CurriculumService
          ↓
       Template
```

Para una lección:

```text
Lesson request
    ↓
Resolve lesson
    ↓
Resolve module
    ↓
Resolve course
    ↓
CourseAccessService
    ↓
Authorized
    ↓
Lesson Player
```

---

# 2. Componentes previstos

Estructura conceptual:

```text
src/
├── Access/
│   ├── Application/
│   └── Domain/
│
├── Courses/
│   └── Application/
│
└── Presentation/
    └── Frontend/
```

Posibles componentes:

```text
CourseAccessService
CourseViewController
LessonViewController
LessonNavigationService
CourseTemplateResolver
LessonTemplateResolver
YouTubeEmbedRenderer
```

No es obligatorio crear todas estas clases si una solución más simple mantiene las responsabilidades claras.

---

# 3. CourseAccessService

Será la única fuente principal para responder:

```text
canAccessCourse(userId, courseId)
```

Debe considerar:

```text
Enrollment ACTIVE     → true
Enrollment COMPLETED  → true
Enrollment REVOKED    → false
No enrollment         → false
```

No debe consultar WooCommerce.

---

# 4. CourseAccessResult

Puede utilizarse un resultado estructurado cuando sea útil.

Ejemplo conceptual:

```text
GRANTED
NOT_AUTHENTICATED
NOT_ENROLLED
REVOKED
COURSE_NOT_FOUND
```

No es obligatorio introducir enum/clase si genera complejidad innecesaria.

---

# 5. Resolución de Course

Antes de autorizar:

- validar ID
- validar post existente
- validar `kaanbal_course`
- validar estado de publicación según contexto

No confiar en parámetros de URL.

---

# 6. Resolución de Lesson

Debe comprobar:

- lesson existente
- `kaanbal_lesson`
- relación Module válida
- relación Course válida

La lección debe resolver su curso utilizando la infraestructura de SPEC-002.

---

# 7. Protección contra IDOR

Caso crítico:

```text
/course/10/lesson/99
```

Si:

```text
lesson 99 → course 15
```

no puede tratarse como:

```text
lesson 99 → course 10
```

El servidor debe verificar la relación.

---

# 8. Routing

Debe utilizar mecanismos soportados por WordPress.

Opciones válidas:

- rewrite endpoints
- virtual routes
- templates de CPT
- template_include
- shortcodes únicamente si existe una razón clara

La decisión debe:

- ser compatible con themes
- permitir autorización antes de exponer contenido
- ser testeable
- evitar modificar Core

---

# 9. URLs

La forma concreta se resolverá durante implementación.

Ejemplo conceptual:

```text
/courses/{course-slug}/
/courses/{course-slug}/lesson/{lesson-slug}/
```

No convertir la URL propuesta en requisito si WordPress ofrece una solución más simple.

---

# 10. Course View

Debe obtener:

- Course
- metadata
- Curriculum

y renderizar:

```text
Course title
Instructor
Duration
Description
Curriculum
```

Puede incluir imagen destacada.

---

# 11. Curriculum UI

Presentación mínima:

```text
Module 1
 ├ Lesson 1
 └ Lesson 2

Module 2
 ├ Lesson 3
 └ Lesson 4
```

Todas las lecciones deben ser navegables para usuario autorizado.

No mostrar estados Completed porque SPEC-005 todavía no existe.

---

# 12. Lesson View

Debe incluir:

```text
Course title
Module title
Lesson title
Lesson content
Video
Previous lesson
Next lesson
```

---

# 13. Lesson Navigation Service

Debe operar sobre el curriculum ordenado.

Conceptualmente:

```text
flatten:

Module 1
  L1
  L2

Module 2
  L3

→ [L1, L2, L3]
```

Entonces:

```text
L1 → previous null / next L2
L2 → previous L1 / next L3
L3 → previous L2 / next null
```

Módulos vacíos no deben afectar navegación.

---

# 14. YouTube Renderer

Debe recibir únicamente información normalizada.

Ejemplo:

```text
provider: youtube
source: dQw4w9WgXcQ
```

y construir internamente una URL permitida.

No debe aceptar:

```text
<iframe>...</iframe>
```

desde metadata.

---

# 15. Embed

Ejemplo conceptual:

```text
https://www.youtube.com/embed/{VIDEO_ID}
```

Debe utilizar escaping apropiado.

Los atributos exactos del iframe deben seguir prácticas compatibles con WordPress.

---

# 16. Lección sin video

Si:

```text
video_provider = empty
```

o no hay source:

- no renderizar player
- sí renderizar contenido

---

# 17. Acceso Denegado

Debe distinguir razonablemente:

### Usuario no autenticado

Puede:

- mostrar login
- redirigir al login

### Usuario autenticado sin acceso

Puede mostrar:

- acceso requerido
- curso no disponible

No debe revelar el contenido protegido.

---

# 18. Templates

Preferencia:

templates propios del plugin con posibilidad futura de override controlado.

Ejemplo:

```text
templates/
├── course.php
├── lesson.php
└── partials/
    └── curriculum.php
```

No implementar un sistema complejo de template overrides si todavía no es necesario.

---

# 19. Escaping

Aplicar según contexto:

```text
esc_html()
esc_attr()
esc_url()
wp_kses_post()
```

No escapar indiscriminadamente contenido que WordPress debe renderizar legítimamente, pero tampoco imprimir metadata sin protección.

---

# 20. Authorization Boundary

Ningún template debe decidir autónomamente:

```text
if enrollment exists...
```

La política debe estar centralizada en:

`CourseAccessService`

o equivalente.

---

# 21. Integración con Enrollment

Solo leer:

```text
user_id
course_id
status
```

No modificar Enrollment desde el player.

---

# 22. No Progress Writes

El flujo:

```text
GET course
GET lesson
```

debe ser read-only respecto a progreso.

No crear registros placeholder.

No actualizar last_seen.

No completar lecciones.

---

# 23. Testing Unitario

Candidatos:

```text
CourseAccessServiceTest
LessonNavigationServiceTest
YouTubeEmbedRendererTest
```

Casos:

```text
active → allowed
completed → allowed
revoked → denied
missing → denied
```

---

# 24. Testing de Integración

Candidatos:

```text
AuthorizedCourseAccessTest
UnauthorizedCourseAccessTest
AuthorizedLessonAccessTest
CrossCourseLessonAccessTest
CourseViewTest
LessonViewTest
```

---

# 25. Tests de Seguridad

Obligatorios:

```text
anonymous cannot access protected lesson
user without enrollment cannot access
revoked user cannot access
user enrolled in course A cannot use access to read lesson from course B
forged course/lesson combination rejected
```

---

# 26. Tests de Curriculum

Casos:

```text
course with modules
course without modules
module without lessons
one lesson
multiple modules
empty module between lessons
```

---

# 27. Tests del Player

Casos:

```text
valid YouTube video
lesson without video
invalid stored source
unsupported provider
```

La última situación no debe generar HTML peligroso.

---

# 28. Tests de Navegación

Debe comprobarse:

```text
first
middle
last
single lesson
multiple modules
empty modules
```

---

# 29. Performance

La vista del curso debe reutilizar:

`CurriculumService`

No volver a implementar consultas individuales dentro del template.

---

# 30. Cache

No introducir cache custom en esta SPEC salvo evidencia de necesidad.

WordPress object cache podrá aprovechar naturalmente sus primitivas.

---

# 31. Scope Protection

No crear:

```text
lesson_progress
completed_at
progress %
quiz attempts
certificate
```

como efecto de visitas.

---

# 32. Secuencia de Implementación

```text
1. CourseAccessService
2. tests de access policy
3. frontend routing
4. course resolver
5. lesson resolver
6. course authorization boundary
7. lesson authorization boundary
8. course template
9. curriculum rendering
10. YouTube renderer
11. lesson template
12. previous/next navigation
13. security tests
14. integration tests
15. scope verification
16. quality gate
```

---

# 33. Riesgos Técnicos

## TECH-001

Dependencia excesiva del theme.

Mitigación:

templates del plugin.

## TECH-002

URLs públicas con IDs pueden favorecer IDOR si no existe verificación.

Mitigación:

authorization + hierarchy validation.

## TECH-003

Múltiples consultas de curriculum.

Mitigación:

servicio reutilizable.

## TECH-004

Convertir el player en un módulo de progreso anticipadamente.

Mitigación:

read-only.

---

# 34. Decisiones

## DEC-001

Access policy depende de Enrollment.

## DEC-002

Estados con acceso:

```text
active
completed
```

## DEC-003

`revoked` no tiene acceso.

## DEC-004

No se consulta WooCommerce durante una request normal del player.

## DEC-005

Navegación libre.

## DEC-006

YouTube es el único player soportado inicialmente.

## DEC-007

No se escribe progreso.

---

# 35. Condición de Finalización

SPEC-004 estará lista para auditoría cuando:

- autorización esté centralizada
- active tenga acceso
- completed tenga acceso
- revoked sea rechazado
- usuario sin enrollment sea rechazado
- visitante sea rechazado
- Course View funcione
- Curriculum View funcione
- Lesson View funcione
- YouTube player sea seguro
- previous/next funcione
- IDOR esté cubierto
- visitas no modifiquen progreso
- tests pasen
- Quality Gate pase
- `Ready for audit: Yes`