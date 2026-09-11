# Contexto del Proyecto

## 1. Nombre del Proyecto

Nombre de trabajo del plugin:

**Kaanbal**

Kaanbal será un plugin LMS para WordPress enfocado en:

- creación y administración de cursos
- organización de temarios
- reproducción de contenido en video
- seguimiento de progreso
- quizzes
- certificados
- integración comercial con WooCommerce

El objetivo inicial no es competir funcionalmente con plataformas como LearnDash o Tutor LMS.

El objetivo es construir un LMS más pequeño, mantenible y enfocado en los requerimientos reales del proyecto.

---

## 2. Visión del Producto

Kaanbal debe permitir que un sitio WordPress pueda vender cursos utilizando WooCommerce y entregar posteriormente la experiencia educativa desde el propio sitio.

Flujo principal:

```text id="5q8o3b"
WooCommerce
    ↓
Compra
    ↓
Pedido válido
    ↓
Matrícula
    ↓
Curso
    ↓
Módulos
    ↓
Lecciones
    ↓
Progreso
    ↓
Quiz final
    ↓
Curso completado
    ↓
Certificado
```

WooCommerce será responsable del proceso comercial.

Kaanbal será responsable del proceso educativo.

---

## 3. Separación de Responsabilidades

### WooCommerce

WooCommerce administrará:

- productos
- precios
- promociones
- carrito
- checkout
- métodos de pago
- pedidos
- impuestos
- cupones
- reembolsos comerciales

Kaanbal no debe duplicar estas responsabilidades.

### Kaanbal

Kaanbal administrará:

- cursos
- módulos
- lecciones
- acceso educativo
- matrículas
- progreso
- quizzes
- finalización de cursos
- certificados
- panel del alumno
- información académica

---

## 4. Entorno

El plugin se desarrollará como un repositorio independiente.

No se versionará WordPress Core.

Estructura conceptual:

```text id="3b80z6"
wordpress/
├── wp-admin/
├── wp-includes/
└── wp-content/
    ├── plugins/
    │   ├── woocommerce/
    │   └── kaanbal/
    │       └── ← repositorio Git
    │
    └── themes/
```

Si posteriormente se desarrolla un theme personalizado para el mismo sitio, deberá utilizar un repositorio independiente.

Ejemplo:

```text id="qzmhpf"
kaanbal/
→ plugin LMS

entrena-theme/
→ theme personalizado
```

---

## 5. Principio Plugin vs Theme

La lógica educativa debe vivir en Kaanbal.

El theme puede controlar:

- presentación
- layout
- estilos
- componentes visuales
- experiencia frontend

El theme no debe ser responsable de:

- matrículas
- progreso
- reglas de finalización
- quizzes
- certificados
- control de acceso
- integración comercial

Cambiar de theme no debe romper el funcionamiento académico.

---

## 6. Modelo Conceptual del Curso

La jerarquía inicial será:

```text id="qcfvpy"
Curso
    ↓
Módulos
    ↓
Lecciones
```

Ejemplo:

```text id="44ag6t"
Curso:
Hipertrofia basada en evidencia

Módulo 1:
Fundamentos

    Lección 1:
    Introducción

    Lección 2:
    Adaptación muscular

    Lección 3:
    Tensión mecánica

Módulo 2:
Programación

    Lección 4:
    Volumen

    Lección 5:
    Intensidad

    Lección 6:
    Frecuencia
```

---

## 7. Navegación del Curso

La navegación será libre.

El alumno podrá:

- abrir cualquier módulo disponible
- abrir cualquier lección del curso
- regresar a lecciones anteriores
- repetir contenido
- navegar entre lecciones sin respetar un orden obligatorio

No se implementará inicialmente bloqueo secuencial.

Ejemplo:

```text id="ri3w5n"
Lección 1
Lección 2
Lección 3
Lección 4
```

El alumno puede abrir directamente la Lección 4 si tiene acceso al curso.

---

## 8. Videos

El contenido principal de las lecciones será video.

El proveedor inicial más probable será:

**YouTube**

Kaanbal no debe intentar almacenar videos MP4 pesados directamente en WordPress como estrategia principal.

Una lección debe poder almacenar al menos:

- proveedor
- URL o identificador externo
- información necesaria para renderizar el reproductor

Ejemplo conceptual:

```text id="qth8p9"
provider: youtube
video_id: dQw4w9WgXcQ
```

---

## 9. Abstracción de Proveedor de Video

Aunque inicialmente se utilice YouTube, la arquitectura no debe impedir incorporar posteriormente proveedores como:

- Vimeo
- Bunny Stream
- Cloudflare Stream
- Mux

Esto no significa que dichos proveedores deban implementarse en el MVP.

Son únicamente una consideración arquitectónica.

No deben convertirse en requisitos de una SPEC salvo aprobación explícita.

---

## 10. Seguimiento de Video

Para el MVP no es requisito guardar:

- segundo exacto reproducido
- posición de reproducción
- porcentaje visto
- historial de reproducción del player

No se requiere reanudar automáticamente el video desde el punto anterior.

Esto reduce el acoplamiento con APIs específicas del reproductor.

---

## 11. Finalización de Lecciones

El progreso de una lección será manual.

Cada lección debe disponer de una acción equivalente a:

**Marcar como completada**

El alumno decide cuándo considerar terminada la lección.

Ejemplo:

```text id="mpg4ly"
[ Reproducir video ]

...

[ Marcar como completada ]
```

Al completarla:

```text id="u8fyaf"
Estado:
Completed
```

---

## 12. Repetición de Contenido

Una lección completada sigue siendo accesible.

Marcar una lección como completada no debe:

- impedir volver a abrirla
- impedir reproducir nuevamente el video
- bloquear el contenido

El estado representa progreso, no restricción de acceso.

---

## 13. Progreso del Curso

El progreso del curso se calculará inicialmente con base en las lecciones completadas.

Ejemplo:

Curso con:

```text id="wfhp2k"
20 lecciones
```

Alumno con:

```text id="1y1ia6"
13 lecciones completadas
```

Resultado:

```text id="ip409c"
13 / 20 = 65%
```

La fórmula conceptual será:

```text id="8vrpcr"
completed_lessons / total_lessons * 100
```

---

## 14. Quiz y Porcentaje de Curso

El quiz final no formará parte del porcentaje de progreso del contenido.

Por ejemplo:

```text id="i70h7x"
Lecciones completadas: 100%
Quiz: Pendiente
Curso: Pendiente de evaluación
```

El alumno puede haber consumido todo el contenido sin haber completado todavía el curso académico.

---

## 15. Quiz Final

Cada curso tendrá inicialmente un quiz final.

No se requiere en el MVP:

- quiz por módulo
- quiz por lección
- assignments
- preguntas abiertas
- evaluación manual

El quiz inicial utilizará preguntas de opción múltiple.

Cada pregunta debe permitir:

- enunciado
- múltiples opciones
- una respuesta correcta

---

## 16. Configuración del Quiz

Cada curso debe poder configurar:

- porcentaje mínimo de aprobación
- número máximo de intentos o intentos ilimitados

Ejemplo:

```text id="zw7mg8"
Passing score:
80%

Attempts:
3
```

o:

```text id="nkcm49"
Attempts:
Unlimited
```

---

## 17. Historial de Intentos

Kaanbal debe poder conservar historial de intentos.

Conceptualmente:

```text id="yo79tb"
Usuario
Quiz
Número de intento
Respuestas
Score
Passed / Failed
Started at
Completed at
```

No debe sobrescribirse necesariamente el intento anterior cuando exista un nuevo intento.

---

## 18. Finalización del Curso

El curso se considerará completado únicamente cuando se cumplan ambas condiciones:

```text id="yvoqc7"
100% de lecciones completadas
+
Quiz final aprobado
```

Resultado:

```text id="dcniha"
Course status:
Completed
```

---

## 19. Estados Conceptuales del Curso

Ejemplos iniciales:

```text id="a7fw7p"
Not Started
In Progress
Pending Evaluation
Completed
```

Estos estados son conceptuales.

La arquitectura final deberá determinar si se persisten explícitamente o se derivan.

Ejemplo:

```text id="xwjdjp"
100% lessons
quiz pending
→ Pending Evaluation
```

---

## 20. Certificados

Cuando un curso esté completado, el alumno podrá obtener un certificado.

El certificado debe contemplar al menos:

- nombre del alumno
- nombre del curso
- fecha de finalización
- instructor
- duración
- folio único

Ejemplo:

```text id="hfkh10"
KAANBAL

CERTIFICADO

Daniel García

ha completado satisfactoriamente

Hipertrofia basada en evidencia

Fecha:
10 de septiembre de 2026

Folio:
KB-HIP-2026-000184
```

El formato definitivo del folio se decidirá en la SPEC correspondiente.

---

## 21. Validación Pública de Certificados

La arquitectura debe permitir posteriormente una página pública de validación de certificados.

Ejemplo conceptual:

```text id="t6nhab"
/certificates/KB-HIP-2026-000184
```

La página podría mostrar:

- certificado válido
- alumno
- curso
- fecha
- folio

Esta capacidad debe contemplarse arquitectónicamente.

No necesariamente debe implementarse en la primera versión de certificados.

---

## 22. Duración del Curso

Para el MVP, la duración del curso puede ser un campo administrativo manual.

Ejemplo:

```text id="uqjbdq"
Duración:
8 horas
```

No es requisito calcular automáticamente la duración sumando los videos de YouTube.

---

## 23. Instructor

Cada curso debe poder tener información de instructor.

Para el MVP no se requiere un marketplace multi-instructor.

Inicialmente puede modelarse como información asociada al curso.

La arquitectura puede permitir evolucionar posteriormente a una entidad o usuario WordPress si aparece el requisito.

---

## 24. Relación WooCommerce → Cursos

Un producto de WooCommerce puede otorgar acceso a uno o varios cursos.

Ejemplo:

```text id="1ws3tz"
Producto WooCommerce:
Pack Fuerza + Hipertrofia

Concede acceso a:
- Curso Fuerza
- Curso Hipertrofia
```

Esto permite:

- bundles
- promociones
- 2x1
- paquetes
- productos especiales

---

## 25. Relación Cursos → Productos

Un mismo curso puede estar asociado a varios productos.

Ejemplo:

```text id="n0r6xj"
Curso Hipertrofia
    ↑
    ├── Producto individual
    ├── Pack Hipertrofia + Nutrición
    └── Promoción 2x1
```

Por lo tanto, la relación conceptual es:

```text id="9l935c"
WooCommerce Products
        ↕
      Courses
```

Muchos a muchos.

---

## 26. Matrículas

La matrícula pertenece al alumno y al curso.

No debe modelarse simplemente como:

```text id="m98go9"
usuario → producto
```

La compra es el origen comercial.

La matrícula es el resultado académico.

Ejemplo:

```text id="9i0mde"
Pedido #1234
Producto:
Bundle especial

Resultado:

Usuario 50
→ matrícula Curso A
→ matrícula Curso B
→ matrícula Curso C
```

---

## 27. Idempotencia de Matrícula

La creación de matrículas debe ser idempotente.

Si múltiples productos otorgan acceso al mismo curso:

```text id="abpx4v"
Producto A → Curso 10
Producto B → Curso 10
```

y ambos aparecen en el mismo pedido, no deben generarse dos matrículas académicas activas para:

```text id="92z9ha"
user_id = X
course_id = 10
```

La implementación debe evitar duplicidad funcional.

---

## 28. Trazabilidad de Acceso

Aunque la matrícula sea por curso, debe conservarse información suficiente para conocer el origen del acceso.

Ejemplo conceptual:

```text id="a5oqhg"
user_id
course_id
source_type
source_order_id
source_product_id
```

Esto permitirá posteriormente:

- soporte
- diagnóstico
- reembolsos
- promociones
- auditoría
- análisis comercial

El modelo exacto se definirá en `docs/data-model.md`.

---

## 29. Estado de WooCommerce para Conceder Acceso

La regla definitiva sobre qué estado de WooCommerce concede acceso todavía debe formalizarse.

Candidato inicial:

```text id="qnlt9p"
completed
```

Sin embargo, deberán analizarse:

```text id="yhwctq"
processing
completed
cancelled
refunded
failed
```

No deben inventarse políticas silenciosamente.

La SPEC de matrícula WooCommerce definirá el comportamiento exacto.

---

## 30. Reembolsos y Cancelaciones

Kaanbal debe contemplar arquitectónicamente que un pedido puede:

- cancelarse
- reembolsarse parcialmente
- reembolsarse totalmente
- cambiar de estado

La política exacta de revocación de acceso no está definida todavía.

Debe resolverse en la SPEC correspondiente.

Hasta entonces, esta condición es:

```text id="bfvuyn"
Pending product decision
```

y no un blocker para desarrollar la infraestructura base.

---

## 31. Acceso al Curso

El alumno debe tener una matrícula válida para acceder al contenido protegido.

La validación debe realizarse del lado servidor.

No debe depender exclusivamente de:

- ocultar enlaces
- JavaScript
- CSS
- estado visual del frontend

La interfaz puede ocultar contenido, pero la autorización real debe aplicarse en backend.

---

## 32. Panel del Alumno

Kaanbal deberá proporcionar una experiencia tipo:

**Mis cursos**

Ejemplo:

```text id="sfbggh"
Mis cursos

Hipertrofia basada en evidencia
65%
[ Continuar ]

Nutrición Deportiva
100%
[ Ver curso ]
[ Certificado ]

Fuerza
0%
[ Comenzar ]
```

---

## 33. Información del Panel

Idealmente deberá mostrar:

- nombre del curso
- progreso
- estado
- acceso al curso
- resultado de quiz cuando aplique
- certificado cuando aplique

La UX exacta se desarrollará en la SPEC correspondiente.

---

## 34. Administración

Kaanbal deberá proporcionar herramientas administrativas para gestionar:

- cursos
- módulos
- lecciones
- quizzes
- asociaciones WooCommerce
- alumnos
- matrículas
- progreso
- certificados

No todo debe implementarse en una sola SPEC.

---

## 35. Escala Esperada

La arquitectura no se diseñará inicialmente para millones de alumnos.

Debe ser razonable para escenarios como:

```text id="l6ag4o"
cientos de cursos
miles de alumnos
cientos de miles de registros de progreso
```

Las decisiones de persistencia deben considerar esta escala.

---

## 36. Persistencia

La inclinación inicial es separar:

### Contenido editorial

Posibles candidatos:

```text id="d1c3mx"
Courses
Modules
Lessons
```

Estos pueden utilizar mecanismos nativos de WordPress cuando tenga sentido.

### Datos transaccionales

Posibles candidatos a tablas propias:

```text id="8db9as"
Enrollments
Progress
Quiz attempts
Certificates
```

La decisión final se documentará en:

`docs/data-model.md`

---

## 37. Principios Técnicos

Kaanbal debe evitar:

- modificar WordPress Core
- modificar WooCommerce Core
- grandes archivos monolíticos
- abuso de `post_meta` para datos transaccionales
- consultas N+1 evitables
- dependencia innecesaria entre dominio y WooCommerce
- lógica de negocio en templates
- autorización únicamente frontend
- dependencias externas innecesarias

---

## 38. Namespaces

El nombre técnico de trabajo será:

```text id="1hmxd8"
Kaanbal
```

Namespace base previsto:

```php id="ryctqf"
namespace Kaanbal;
```

Ejemplos conceptuales:

```php id="19a10m"
Kaanbal\Courses
Kaanbal\Enrollment
Kaanbal\Progress
Kaanbal\Quiz
Kaanbal\Certificates
Kaanbal\WooCommerce
```

La arquitectura definitiva se formalizará en:

`docs/architecture.md`

---

## 39. Prefijos Técnicos

Slug previsto:

```text id="b8qx37"
kaanbal
```

Text domain previsto:

```text id="l57l3w"
kaanbal
```

Las tablas propias, cuando existan, deberían utilizar un prefijo consistente.

Ejemplo conceptual:

```text id="ztzc38"
{$wpdb->prefix}kaanbal_enrollments
{$wpdb->prefix}kaanbal_progress
{$wpdb->prefix}kaanbal_quiz_attempts
{$wpdb->prefix}kaanbal_certificates
```

Los nombres definitivos se resolverán en el modelo de datos.

---

## 40. Hooks Propios

La arquitectura podrá exponer hooks propios para permitir extensibilidad futura.

Ejemplos conceptuales:

```text id="cm87eq"
kaanbal_student_enrolled
kaanbal_lesson_completed
kaanbal_course_completed
kaanbal_quiz_passed
kaanbal_certificate_issued
```

Estos nombres no constituyen todavía una API pública estable.

Deben formalizarse antes de considerarlos contratos públicos.

---

## 41. Funcionalidades Fuera del MVP

Salvo aprobación explícita, no forman parte del MVP:

- marketplace de instructores
- SCORM
- xAPI
- LTI
- assignments
- foros
- gamificación
- badges complejos
- gradebook avanzado
- live classes
- drip content avanzado
- prerequisite engine complejo
- secuencia obligatoria
- preguntas abiertas evaluadas manualmente
- grupos
- cohorts
- multi-tenancy
- app móvil nativa
- tracking de segundo exacto del video
- DRM
- protección absoluta contra descarga/copia de videos

---

## 42. Seguridad y Video

El uso de YouTube implica que Kaanbal no puede garantizar protección absoluta del contenido audiovisual.

El objetivo de Kaanbal será proteger:

- acceso a la página del curso
- acceso a lecciones
- estado académico
- progreso
- quizzes
- certificados

No se debe afirmar que un video externo es imposible de copiar.

---

## 43. Principio de Simplicidad

El proyecto prioriza:

```text id="g9n1l4"
simple
correct
maintainable
secure
```

sobre:

```text id="wq7i79"
feature-rich
complex
over-engineered
```

Las abstracciones deberán justificarse mediante una necesidad actual o una extensión futura razonablemente prevista y explícitamente aceptada.

---

## 44. Gobierno del Alcance

Las decisiones incluidas en este documento representan contexto global aprobado del proyecto.

Una SPEC puede precisar estos comportamientos.

No debe contradecirlos silenciosamente.

Si una SPEC requiere cambiar una decisión global, debe documentarse mediante un `Change Request` y aprobación humana.

---

## 45. Regla Final

Kaanbal debe mantenerse conceptualmente simple:

```text id="q06b4d"
WooCommerce vende.

Kaanbal enseña.

El alumno consume el contenido.

Kaanbal registra su avance.

El quiz valida el aprendizaje.

El certificado confirma la finalización.
```