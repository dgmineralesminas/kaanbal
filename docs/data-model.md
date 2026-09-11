# Modelo de Datos

## 1. Propósito

Este documento define el modelo de datos global de Kaanbal.

El objetivo es separar correctamente:

- contenido editorial
- configuración
- relaciones
- datos transaccionales
- historial académico
- datos derivados

La arquitectura debe evitar tanto el abuso de `post_meta` como la creación innecesaria de tablas propias.

---

## 2. Principio General

Kaanbal manejará dos tipos principales de información.

### Contenido editorial

Información creada y editada por administradores:

```text
Courses
Modules
Lessons
Quizzes
Questions
```

Este tipo de información puede beneficiarse de APIs nativas de WordPress.

### Información transaccional

Información generada por comportamiento de usuarios o eventos del sistema:

```text
Enrollments
Lesson progress
Quiz attempts
Certificates
Access origins
```

Este tipo de información debe preferir tablas propias.

---

## 3. Estrategia General

La estrategia inicial será híbrida.

```text
WordPress CPT / metadata
        +
Custom database tables
```

WordPress administrará principalmente contenido.

Kaanbal administrará mediante tablas propias el estado académico y transaccional.

---

# 4. Entidades Principales

Modelo conceptual:

```text
Course
  ├── Module
  │    └── Lesson
  │
  └── Quiz
        └── Question
              └── Answers

WooCommerce Product
        ↕
      Course

User
  └── Enrollment
        └── Course

User
  └── Lesson Progress

User
  └── Quiz Attempts

User
  └── Certificate
```

---

# 5. Course

## Tipo recomendado

Custom Post Type:

```text
kaanbal_course
```

## Justificación

Un curso es contenido editorial.

Se beneficia de:

- título WordPress
- estado draft/publish
- editor
- featured image
- revisiones cuando corresponda
- author
- metadata
- APIs administrativas
- REST API de WordPress si posteriormente se necesita

No existe una razón inicial suficiente para implementar cursos como tabla completamente propia.

---

## 6. Metadata del Curso

Campos posibles:

```text
duration
instructor_name
passing_score
max_quiz_attempts
quiz_attempts_unlimited
certificate_enabled
```

Algunos de estos campos podrían evolucionar a configuración separada.

Para valores simples y de baja cardinalidad, `post_meta` es aceptable.

Ejemplo:

```text
_kaanbal_duration
_kaanbal_instructor_name
_kaanbal_passing_score
```

---

## 7. Estado Editorial del Curso

Se utilizarán inicialmente los estados nativos de WordPress:

```text
draft
publish
private
trash
```

No se requiere crear otra columna para replicarlos.

---

# 8. Module

## Tipo recomendado

Custom Post Type interno:

```text
kaanbal_module
```

o una entidad equivalente gestionada mediante WordPress.

## Justificación

Los módulos son contenido estructural editable por administrador.

Necesitan:

- nombre
- descripción opcional
- curso padre
- orden

No generan gran volumen transaccional.

---

## 9. Relación Course → Module

Relación:

```text
Course 1 → N Modules
```

Cada módulo pertenece inicialmente a un solo curso.

La relación puede almacenarse mediante:

```text
post_parent
```

o metadata explícita:

```text
_kaanbal_course_id
```

Preferencia inicial:

usar una relación explícita que resulte clara y fácil de consultar.

La decisión exacta puede formalizarse en SPEC-002.

---

## 10. Orden de Módulos

Cada módulo requiere posición.

Campo conceptual:

```text
menu_order
```

WordPress ya proporciona `menu_order`, por lo que es un candidato natural.

No es necesario crear un sistema de orden adicional salvo que aparezca una necesidad real.

---

# 11. Lesson

## Tipo recomendado

Custom Post Type:

```text
kaanbal_lesson
```

## Justificación

Una lección es contenido editorial.

Puede utilizar:

- título
- contenido
- estado editorial
- metadata
- orden

---

## 12. Relación Module → Lesson

Relación:

```text
Module 1 → N Lessons
```

Cada lección pertenece inicialmente a un módulo.

Y, por transitividad, a un curso.

La implementación debe permitir resolver eficientemente:

```text
lesson
→ module
→ course
```

---

## 13. Metadata de Lección

Ejemplo:

```text
_kaanbal_video_provider
_kaanbal_video_source
```

Para YouTube:

```text
provider = youtube
source = video_id o URL
```

No se requiere guardar:

```text
current_second
percentage_watched
watch_history
```

en el MVP.

---

# 14. Quiz

Existen dos opciones razonables:

1. CPT
2. tabla propia

Para el MVP se recomienda:

```text
kaanbal_quiz
```

como Custom Post Type.

## Justificación

El quiz es una definición editorial, no un evento transaccional.

Tiene:

- título
- configuración
- curso asociado
- preguntas

La ejecución del quiz sí se almacenará en tablas propias.

---

## 15. Relación Course → Quiz

Inicialmente:

```text
Course 1 → 0..1 Final Quiz
```

Un curso puede no tener quiz durante su creación.

Para considerarse completado dentro del flujo inicial del LMS, deberá cumplirse la política definida para ese curso.

El MVP asume un único quiz final por curso.

---

# 16. Questions

Para preguntas se recomienda inicialmente una tabla propia editorial:

```text
{$wpdb->prefix}kaanbal_questions
```

en lugar de crear un CPT por cada pregunta.

## Razón

Las preguntas:

- tienen estructura repetitiva
- requieren consultas controladas
- no necesitan interfaz editorial completa de WordPress
- pueden crecer en volumen
- requieren relación explícita con quiz
- tienen posición
- requieren respuestas asociadas

Esto mantiene el modelo más limpio.

---

# 17. Tabla `kaanbal_questions`

Nombre físico:

```text
{$wpdb->prefix}kaanbal_questions
```

Columnas propuestas:

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

Tipos conceptuales:

```text
id              BIGINT UNSIGNED
quiz_id         BIGINT UNSIGNED
question_text   LONGTEXT
question_type   VARCHAR(30)
position        INT UNSIGNED
active          TINYINT(1)
created_at      DATETIME
updated_at      DATETIME
```

---

## 18. Question Type

Inicialmente:

```text
single_choice
```

No agregar todavía:

```text
multiple_choice
open_text
true_false
matching
```

salvo que una SPEC futura los requiera.

---

# 19. Answers

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

Tipos:

```text
id            BIGINT UNSIGNED
question_id   BIGINT UNSIGNED
answer_text   LONGTEXT
is_correct    TINYINT(1)
position      INT UNSIGNED
created_at    DATETIME
updated_at    DATETIME
```

---

## 20. Correct Answer

Para `single_choice` debe existir exactamente una respuesta correcta activa por pregunta.

Esta regla debe validarse en aplicación.

Si resulta viable mediante esquema, puede reforzarse técnicamente.

No debe confiarse únicamente en JavaScript.

---

# 21. WooCommerce Product ↔ Course

La relación es muchos a muchos.

```text
Product N ↔ N Course
```

Ejemplo:

```text
Producto A
→ Curso 1
→ Curso 2

Producto B
→ Curso 2
→ Curso 3
```

---

## 22. Estrategia Recomendada para Product-Course

Usar tabla propia:

```text
{$wpdb->prefix}kaanbal_product_courses
```

en lugar de almacenar arrays serializados en `post_meta`.

## Razones

Permite:

- queries directas
- uniqueness
- bundles
- auditoría
- resolver cursos por producto eficientemente
- resolver productos por curso
- evitar arrays serializados difíciles de consultar

---

## 23. Tabla `kaanbal_product_courses`

Columnas:

```text
id
product_id
course_id
created_at
```

Tipos:

```text
id          BIGINT UNSIGNED
product_id  BIGINT UNSIGNED
course_id   BIGINT UNSIGNED
created_at  DATETIME
```

Constraints lógicos:

```text
UNIQUE(product_id, course_id)
```

Índices:

```text
INDEX product_id
INDEX course_id
UNIQUE product_id_course_id
```

---

# 24. Enrollment

Tabla:

```text
{$wpdb->prefix}kaanbal_enrollments
```

Representa acceso académico.

Una matrícula conecta:

```text
User
↔
Course
```

---

## 25. Columnas de Enrollment

Propuesta:

```text
id
user_id
course_id
status
enrolled_at
completed_at
revoked_at
created_at
updated_at
```

Tipos:

```text
id             BIGINT UNSIGNED
user_id        BIGINT UNSIGNED
course_id      BIGINT UNSIGNED
status         VARCHAR(30)
enrolled_at    DATETIME
completed_at   DATETIME NULL
revoked_at     DATETIME NULL
created_at     DATETIME
updated_at     DATETIME
```

---

## 26. Estados de Enrollment

Estados iniciales:

```text
active
completed
revoked
```

Interpretación:

### active

El alumno tiene acceso activo.

### completed

El alumno terminó académicamente el curso.

Puede conservar acceso.

### revoked

El acceso académico fue revocado.

La política de revocación por reembolso se definirá posteriormente.

---

## 27. Unicidad de Enrollment

Regla:

Un alumno no debe tener múltiples matrículas funcionalmente activas para el mismo curso.

Preferencia:

```text
UNIQUE(user_id, course_id)
```

Esto simplifica fuertemente la idempotencia.

Si en el futuro se necesita historial de múltiples periodos de matrícula, se deberá revisar esta decisión mediante Change Request.

Para el alcance actual:

```text
User + Course = una matrícula
```

---

# 28. Enrollment Source

Una matrícula puede tener múltiples orígenes comerciales.

Ejemplo:

```text
Producto A
→ Curso X

Producto B
→ Curso X
```

El usuario puede haber adquirido ambos productos.

Por ello, no se recomienda guardar un solo:

```text
source_product_id
```

directamente en `enrollments`.

---

# 29. Enrollment Sources

Tabla propuesta:

```text
{$wpdb->prefix}kaanbal_enrollment_sources
```

Columnas:

```text
id
enrollment_id
source_type
order_id
product_id
order_item_id
granted_at
revoked_at
created_at
```

Tipos conceptuales:

```text
id             BIGINT UNSIGNED
enrollment_id  BIGINT UNSIGNED
source_type    VARCHAR(30)
order_id       BIGINT UNSIGNED NULL
product_id     BIGINT UNSIGNED NULL
order_item_id  BIGINT UNSIGNED NULL
granted_at     DATETIME
revoked_at     DATETIME NULL
created_at     DATETIME
```

---

## 30. Source Type

Inicialmente:

```text
woocommerce
manual
```

Esto permite posteriormente conceder acceso manual desde administración sin fingir que existió una compra.

---

## 31. Idempotencia de Source

Para WooCommerce se debe evitar procesar la misma fuente dos veces.

Unique candidate:

```text
UNIQUE(enrollment_id, order_item_id, product_id)
```

La restricción exacta debe considerar cómo WooCommerce representa los items del pedido y será formalizada en SPEC-003.

---

# 32. Lesson Progress

Tabla:

```text
{$wpdb->prefix}kaanbal_lesson_progress
```

Representa el estado de una lección para un alumno.

---

## 33. Columnas de Lesson Progress

Propuesta:

```text
id
user_id
course_id
lesson_id
status
completed_at
created_at
updated_at
```

Tipos:

```text
id            BIGINT UNSIGNED
user_id       BIGINT UNSIGNED
course_id     BIGINT UNSIGNED
lesson_id     BIGINT UNSIGNED
status        VARCHAR(30)
completed_at  DATETIME NULL
created_at    DATETIME
updated_at    DATETIME
```

---

## 34. Estado de Lesson Progress

Para el MVP puede bastar:

```text
completed
```

La inexistencia de registro significa:

```text
not completed
```

Alternativamente podría usarse:

```text
not_started
completed
```

pero persistir `not_started` para todas las combinaciones produciría filas innecesarias.

Preferencia:

```text
no row → not completed
row → completed
```

---

## 35. Unicidad de Lesson Progress

Constraint:

```text
UNIQUE(user_id, lesson_id)
```

Esto garantiza idempotencia.

Si el alumno hace doble click:

```text
Marcar como completada
Marcar como completada
```

no deben generarse dos registros.

---

## 36. Course ID en Lesson Progress

Aunque `course_id` pueda derivarse desde la lección, se propone conservarlo si aporta:

- consultas más rápidas
- reportes
- reducción de joins
- validación contextual

Sin embargo, introduce riesgo de inconsistencia.

La SPEC de progreso debe decidir si:

```text
course_id se persiste
```

o:

```text
course_id se deriva
```

Preferencia inicial:

derivarlo cuando sea razonable para mantener una única fuente de verdad.

---

# 37. Porcentaje de Progreso

No se requiere una columna:

```text
progress_percentage
```

en el MVP.

El valor puede derivarse:

```text
completed lessons
/
total published lessons
*
100
```

Esto evita inconsistencias.

Puede cachearse posteriormente si las métricas demuestran necesidad.

---

# 38. Quiz Attempts

Tabla:

```text
{$wpdb->prefix}kaanbal_quiz_attempts
```

Cada fila representa un intento.

---

## 39. Columnas de Quiz Attempts

Propuesta:

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

Tipos:

```text
id              BIGINT UNSIGNED
user_id         BIGINT UNSIGNED
course_id       BIGINT UNSIGNED
quiz_id         BIGINT UNSIGNED
attempt_number  INT UNSIGNED
status          VARCHAR(30)
score           DECIMAL(5,2) NULL
passed          TINYINT(1) NULL
started_at      DATETIME
completed_at    DATETIME NULL
created_at      DATETIME
updated_at      DATETIME
```

---

## 40. Estados de Quiz Attempt

Iniciales:

```text
in_progress
passed
failed
```

Puede evaluarse posteriormente si `passed`/`failed` deberían derivarse del resultado en lugar de duplicarse con `status`.

Evitar redundancia innecesaria.

---

# 41. Quiz Attempt Answers

Tabla:

```text
{$wpdb->prefix}kaanbal_quiz_attempt_answers
```

Columnas:

```text
id
attempt_id
question_id
answer_id
is_correct
created_at
```

Tipos:

```text
id           BIGINT UNSIGNED
attempt_id   BIGINT UNSIGNED
question_id  BIGINT UNSIGNED
answer_id    BIGINT UNSIGNED
is_correct   TINYINT(1)
created_at   DATETIME
```

---

## 42. Snapshot Histórico de Quiz

Existe una consideración importante.

Si un administrador modifica una pregunta después de que un alumno haya presentado el quiz, el historial no debería reinterpretarse incorrectamente.

Por ello puede ser conveniente guardar snapshot de:

```text
question_text
answer_text
```

dentro del intento.

No necesariamente debe resolverse en el MVP inicial.

La SPEC de quizzes debe decidir explícitamente la política histórica.

---

# 43. Score

El score debe calcularse exclusivamente en backend.

Nunca confiar en:

```text
score enviado desde JavaScript
passed enviado desde frontend
```

El cliente puede enviar respuestas.

Kaanbal calcula:

```text
correct answers
total questions
score
passed
```

---

# 44. Certificates

Tabla:

```text
{$wpdb->prefix}kaanbal_certificates
```

Cada fila representa un certificado emitido.

---

## 45. Columnas de Certificate

Propuesta:

```text
id
user_id
course_id
enrollment_id
certificate_number
student_name
course_name
instructor_name
course_duration
completed_at
issued_at
status
created_at
updated_at
```

Tipos conceptuales:

```text
id                  BIGINT UNSIGNED
user_id             BIGINT UNSIGNED
course_id           BIGINT UNSIGNED
enrollment_id       BIGINT UNSIGNED
certificate_number  VARCHAR(100)
student_name        VARCHAR(255)
course_name         VARCHAR(255)
instructor_name     VARCHAR(255)
course_duration     VARCHAR(100)
completed_at        DATETIME
issued_at           DATETIME
status              VARCHAR(30)
created_at          DATETIME
updated_at          DATETIME
```

---

# 46. Snapshot del Certificado

A diferencia del progreso, aquí sí se recomienda duplicar algunos valores.

Ejemplo:

```text
student_name
course_name
instructor_name
course_duration
```

## Razón

El certificado es un documento histórico.

Si posteriormente:

```text
el alumno cambia su nombre
el curso cambia de nombre
el instructor cambia
```

el certificado previamente emitido no debería cambiar silenciosamente.

---

# 47. Certificate Number

Debe ser único.

Constraint:

```text
UNIQUE(certificate_number)
```

La estrategia de generación se definirá en SPEC-007.

Debe poder utilizarse para validación pública futura.

---

# 48. Un Certificado por Completion

Para el alcance inicial:

```text
User + Course
```

debe producir un único certificado vigente.

Posible constraint:

```text
UNIQUE(user_id, course_id)
```

Si en el futuro se requiere renovación o recertificación, esta decisión deberá evolucionar.

---

# 49. Course Completion

No se propone inicialmente una tabla independiente:

```text
course_completions
```

La finalización puede quedar representada por:

```text
enrollments.status = completed
enrollments.completed_at
```

y un certificado asociado cuando corresponda.

Esto reduce duplicación.

---

# 50. Course Completion Derivation

Antes de completar matrícula:

```text
all required lessons completed
AND
final quiz passed
```

Cuando ambas condiciones se cumplen:

```text
enrollment.status = completed
completed_at = now
```

Después puede emitirse certificado.

---

# 51. Manual Enrollment

Debe contemplarse acceso manual futuro.

Ejemplo:

```text
Administrador
→ concede Curso A a Usuario X
```

Esto reutiliza:

```text
kaanbal_enrollments
kaanbal_enrollment_sources
```

con:

```text
source_type = manual
```

No requiere una segunda arquitectura.

---

# 52. Revocation

La revocación debe afectar:

```text
enrollment.status
```

y potencialmente las fuentes.

Debe diferenciarse conceptualmente entre:

```text
fuente revocada
```

y:

```text
matrícula sin ninguna fuente válida
```

Ejemplo:

```text
Source A → refunded
Source B → still valid
```

La matrícula puede permanecer activa.

Esta es una razón importante para separar:

```text
enrollment
```

de:

```text
enrollment_sources
```

---

# 53. Política de Revocación

Regla conceptual futura:

```text
si al menos una fuente válida concede acceso
→ enrollment puede permanecer active

si ninguna fuente válida concede acceso
→ aplicar política de revocación
```

La política exacta se definirá en SPEC-003.

---

# 54. Índices Generales

Las tablas transaccionales deben indexarse según queries reales.

Índices iniciales importantes:

### Product Courses

```text
UNIQUE(product_id, course_id)
INDEX(course_id)
```

### Enrollments

```text
UNIQUE(user_id, course_id)
INDEX(course_id, status)
INDEX(user_id, status)
```

### Enrollment Sources

```text
INDEX(enrollment_id)
INDEX(order_id)
INDEX(product_id)
```

### Lesson Progress

```text
UNIQUE(user_id, lesson_id)
INDEX(user_id)
INDEX(lesson_id)
```

### Quiz Attempts

```text
INDEX(user_id, quiz_id)
INDEX(course_id)
INDEX(quiz_id)
INDEX(user_id, course_id)
```

### Certificates

```text
UNIQUE(certificate_number)
UNIQUE(user_id, course_id)
INDEX(course_id)
```

---

# 55. Foreign Keys

WordPress Core tradicionalmente no depende fuertemente de foreign keys SQL.

Kaanbal debe evaluar cuidadosamente su uso.

Ventajas:

- integridad referencial

Desventajas:

- compatibilidad
- lifecycle
- eliminación de posts/users
- comportamiento de `dbDelta`
- despliegues

Preferencia inicial:

mantener integridad desde aplicación e índices, sin depender obligatoriamente de foreign keys físicas.

Si se decide utilizarlas, debe documentarse explícitamente.

---

# 56. IDs de WordPress

Referencias como:

```text
user_id
course_id
module_id
lesson_id
quiz_id
product_id
order_id
```

utilizarán:

```text
BIGINT UNSIGNED
```

para mantener compatibilidad con IDs WordPress/WooCommerce.

---

# 57. Fechas

Las fechas propias deben guardar tiempo consistente.

Preferencia:

usar helpers WordPress apropiados y respetar la política temporal del proyecto.

Debe evitarse mezclar sin criterio:

```text
UTC
site local time
server time
```

La estrategia exacta debe ser consistente en todas las tablas.

---

# 58. Soft Delete

No se requiere `soft delete` generalizado.

Contenido editorial puede utilizar:

```text
WordPress trash
```

Datos transaccionales históricos deben conservarse cuando sea necesario.

Estados como:

```text
revoked
```

son preferibles a borrar una matrícula que forma parte de historial.

---

# 59. Eliminación de Usuario

WordPress puede eliminar usuarios.

Kaanbal deberá decidir posteriormente qué sucede con:

- matrículas
- progreso
- intentos
- certificados

Por tratarse de datos históricos, no debe asumirse automáticamente `CASCADE DELETE`.

La política deberá ser explícita.

---

# 60. Eliminación de Curso

Eliminar o enviar un curso a papelera no debe destruir automáticamente:

- progreso histórico
- quiz attempts
- certificados

El historial académico debe preservarse salvo decisión explícita.

---

# 61. Datos Derivados

Preferir derivar:

```text
course progress percentage
total completed lessons
pending lessons
course state visual
```

cuando sea razonable.

Persistir únicamente cuando:

- sea histórico
- sea costoso recalcular
- exista necesidad demostrada
- requiera atomicidad

---

# 62. Datos Históricos

Persistir:

```text
enrollment date
completion date
quiz attempts
quiz answers
certificate data
commercial origin
revocation data
```

porque representan eventos históricos.

---

# 63. Tabla de Eventos

No se requiere inicialmente una tabla genérica de eventos.

Evitar crear:

```text
kaanbal_events
```

como event store sin necesidad real.

Logging técnico y auditoría funcional son conceptos diferentes.

---

# 64. Logs

Los logs técnicos no deben mezclarse con tablas académicas.

Si se implementa logging persistente, debe tener propósito específico.

No forma parte del modelo MVP salvo requisito.

---

# 65. Compatibilidad con Multisite

No se requiere soporte especial de WordPress Multisite en el MVP.

El uso de:

```php
$wpdb->prefix
```

mantiene compatibilidad básica por sitio.

No deben añadirse requisitos multisite sin aprobación.

---

# 66. Tabla Resumen

Modelo inicial:

| Entidad | Persistencia |
|---|---|
| Course | CPT |
| Module | CPT |
| Lesson | CPT |
| Quiz | CPT |
| Question | Custom table |
| Question Answer | Custom table |
| Product-Course | Custom table |
| Enrollment | Custom table |
| Enrollment Source | Custom table |
| Lesson Progress | Custom table |
| Quiz Attempt | Custom table |
| Quiz Attempt Answer | Custom table |
| Certificate | Custom table |

---

# 67. Diagrama Relacional Conceptual

```text
WP User
   │
   │ 1
   │
   ├───────────────┐
   │               │
   ▼               ▼
Enrollment      Quiz Attempt
   │               │
   │               └── Attempt Answers
   │
   ├── Enrollment Sources
   │
   ├── Lesson Progress
   │
   └── Certificate
   │
   ▼
Course
   │
   ├── Modules
   │     └── Lessons
   │
   └── Quiz
          └── Questions
                 └── Answers


WooCommerce Product
        │
        └── Product-Course
                 │
                 ▼
               Course
```

---

# 68. Decisiones Diferidas

Las siguientes decisiones se dejan para SPEC específicas:

- comportamiento exacto de refunds
- comportamiento exacto de cancelaciones
- estados WooCommerce que conceden acceso
- snapshot histórico detallado de quiz
- recertificación
- eliminación GDPR/privacidad
- soporte multisite
- import/export
- bancos de preguntas reutilizables
- múltiples quizzes por curso
- prerequisitos

No son blockers para SPEC-001.

---

# 69. Principio de Consistencia

La base de datos debe proteger invariantes importantes.

Ejemplos:

```text
No duplicate Product-Course relation
No duplicate User-Course enrollment
No duplicate User-Lesson completion
Unique certificate number
```

La aplicación complementará estas restricciones con validaciones.

---

# 70. Principio de Idempotencia

Los siguientes casos deben poder repetirse de forma segura:

```text
procesar el mismo pedido
matricular el mismo usuario al mismo curso
marcar la misma lección completada
evaluar finalización de curso
emitir certificado
```

La combinación de:

```text
unique constraints
application checks
transactions cuando correspondan
```

debe evitar duplicados.

---

# 71. Principio de Evolución

El esquema debe poder evolucionar mediante migraciones/versionado.

No se debe asumir que el schema inicial será permanente.

Cambios futuros pueden incluir:

```text
new columns
new indexes
new tables
data migrations
```

sin destruir información existente.

---

# 72. Regla Final

El modelo de datos de Kaanbal sigue una separación intencional:

```text
WordPress
→ contenido que el administrador edita

Kaanbal tables
→ comportamiento que el sistema registra
```

La regla práctica es:

> El contenido se administra como contenido; el estado académico se almacena como datos transaccionales.