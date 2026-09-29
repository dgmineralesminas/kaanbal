# SPEC-004 — Course Access and Player

Status: Ready for implementation

## 1. Objetivo

Implementar la experiencia base mediante la cual un alumno matriculado puede acceder a un curso Kaanbal y consumir sus lecciones.

Esta SPEC debe proporcionar:

- validación server-side de acceso a cursos
- página o vista del curso para alumno
- visualización del temario
- navegación libre entre módulos y lecciones
- página o vista individual de una lección
- reproducción de videos YouTube
- protección de contenido para usuarios sin acceso
- navegación entre lecciones

Esta SPEC NO registra todavía progreso académico.

---

## 2. Contexto

Las SPEC anteriores proporcionan:

### SPEC-001

Infraestructura base del plugin.

### SPEC-002

Modelo editorial:

```text
Course
→ Modules
→ Lessons
```

### SPEC-003

Modelo de acceso:

```text
User
→ Enrollment
→ Course
```

SPEC-004 conecta ambos conceptos:

```text
Enrollment
      ↓
Course Access
      ↓
Curriculum
      ↓
Lesson Player
```

---

## 3. Alcance

Esta SPEC incluye:

- servicio central de autorización de acceso a curso
- validación de matrícula activa
- rutas o mecanismos públicos necesarios para abrir cursos y lecciones
- vista del curso
- listado del temario
- estados visuales básicos
- vista individual de lección
- reproductor YouTube
- navegación anterior/siguiente
- protección backend del contenido
- comportamiento para usuario no autenticado
- comportamiento para usuario autenticado sin matrícula
- comportamiento para matrícula revocada
- comportamiento para contenido inexistente
- navegación libre

---

## 4. Fuera de Alcance

Esta SPEC no incluye:

- progreso
- porcentaje completado
- botón "Marcar como completada"
- estado Completed en lecciones
- guardar última lección visitada
- resume position
- porcentaje de video visto
- tracking de YouTube
- quiz
- certificado
- dashboard completo del alumno
- comentarios
- notas
- favoritos
- bookmarks
- drip content
- prerequisitos
- navegación secuencial obligatoria
- SCORM
- Vimeo
- Bunny Stream
- Mux
- DRM
- descargas offline
- app móvil

---

# 5. Requisitos Funcionales

## RF-001 — Acceso a curso

Kaanbal debe poder determinar si un usuario tiene acceso a un curso.

---

## RF-002 — Matrícula activa

Una matrícula con estado:

`active`

debe conceder acceso.

---

## RF-003 — Matrícula completed

Una matrícula con estado:

`completed`

debe continuar concediendo acceso.

La finalización académica no debe retirar contenido previamente adquirido.

---

## RF-004 — Matrícula revoked

Una matrícula:

`revoked`

no concede acceso.

---

## RF-005 — Usuario no autenticado

Un visitante sin sesión no debe acceder al contenido protegido de un curso.

---

## RF-006 — Usuario autenticado sin matrícula

Un usuario autenticado sin matrícula válida no debe acceder al contenido protegido.

---

## RF-007 — Vista del curso

Un usuario autorizado debe poder visualizar:

- título
- descripción
- imagen cuando exista
- duración
- instructor
- temario

---

## RF-008 — Temario

La vista debe presentar:

```text
Modules
→ Lessons
```

en el orden definido por SPEC-002.

---

## RF-009 — Navegación libre

Un alumno autorizado puede abrir cualquier lección del curso.

No existe bloqueo secuencial.

---

## RF-010 — Vista de lección

Una lección debe mostrar como mínimo:

- título
- contenido
- módulo
- curso
- video cuando exista

---

## RF-011 — YouTube Player

Una lección configurada con proveedor:

`youtube`

debe poder generar un embed válido utilizando su video ID normalizado.

---

## RF-012 — Lección sin video

Una lección sin video debe seguir siendo accesible y mostrar su contenido textual.

---

## RF-013 — Navegación anterior

Cuando exista una lección anterior en el temario, Kaanbal debe poder resolverla.

---

## RF-014 — Navegación siguiente

Cuando exista una siguiente lección en el temario, Kaanbal debe poder resolverla.

---

## RF-015 — Primera lección

La primera lección del curso no tiene anterior.

---

## RF-016 — Última lección

La última lección no tiene siguiente.

---

## RF-017 — Validación Course/Lesson

Solicitar una lección debe comprobar que ésta pertenece realmente al curso contextual cuando exista un curso en la ruta/request.

---

## RF-018 — Contenido inexistente

Course o Lesson inexistentes deben terminar de forma segura.

---

## RF-019 — Curso vacío

Un curso sin módulos debe poder mostrarse a un usuario autorizado.

---

## RF-020 — Módulo vacío

Un módulo sin lecciones debe poder aparecer en el temario sin provocar errores.

---

# 6. Requisitos No Funcionales

## RNF-001 — Autorización Server-Side

La seguridad no puede depender de:

- ocultar botones
- JavaScript
- CSS
- URLs difíciles de adivinar

El acceso debe comprobarse en servidor.

---

## RNF-002 — Fuente única de política

La decisión:

`can user access course?`

debe centralizarse.

No debe duplicarse lógica de autorización arbitrariamente en templates.

---

## RNF-003 — No acoplar player a Enrollment Storage

El reproductor no debe consultar directamente tablas de matrícula.

Debe utilizar un servicio de autorización.

---

## RNF-004 — No Tracking

Visualizar una lección no debe modificar:

- progreso
- matrícula
- completion
- quiz

---

## RNF-005 — Output Escaping

Todo contenido generado por Kaanbal debe utilizar escaping contextual correspondiente.

El contenido gestionado mediante WordPress debe seguir las APIs de renderizado permitidas por WordPress.

---

## RNF-006 — Embed Seguro

El iframe de YouTube debe generarse desde información normalizada/controlada.

No debe renderizarse HTML arbitrario proporcionado en el campo de video.

---

## RNF-007 — Theme Independence

La funcionalidad no debe depender de un theme específico.

---

## RNF-008 — Rendimiento

Mostrar un curso completo no debe generar N+1 evitables.

Debe reutilizar la infraestructura de curriculum establecida por SPEC-002.

---

# 7. Reglas de Negocio

## RB-001

Los estados que conceden acceso son inicialmente:

- active
- completed

---

## RB-002

El estado:

- revoked

no concede acceso.

---

## RB-003

Acceso significa autorización para consultar el contenido del curso.

No significa progreso.

---

## RB-004

La autorización de una lección deriva del acceso al curso al que pertenece.

---

## RB-005

Una lección no concede acceso por sí misma.

---

## RB-006

No existe navegación secuencial obligatoria.

---

## RB-007

Abrir una lección no la completa.

---

## RB-008

Reproducir un video no completa una lección.

---

# 8. Criterios de Aceptación

## AC-001 — Resolver acceso activo

Dado un usuario con enrollment `active` para un curso, el servicio de acceso devuelve autorización.

---

## AC-002 — Mantener acceso completed

Un enrollment `completed` continúa concediendo acceso.

---

## AC-003 — Rechazar revoked

Un enrollment `revoked` no concede acceso.

---

## AC-004 — Rechazar usuario sin matrícula

Un usuario autenticado sin matrícula válida no accede al contenido protegido.

---

## AC-005 — Rechazar visitante

Un usuario no autenticado no accede al contenido protegido.

---

## AC-006 — Mostrar curso autorizado

Un alumno autorizado puede abrir la vista del curso.

---

## AC-007 — Mostrar temario ordenado

La vista del curso contiene módulos y lecciones en el orden definido.

---

## AC-008 — Navegación libre

Un alumno puede abrir directamente cualquier lección válida del curso sin completar las anteriores.

---

## AC-009 — Mostrar lección

Una lección autorizada presenta correctamente:

- título
- contenido
- contexto del curso
- contexto del módulo

---

## AC-010 — Reproducir YouTube

Una lección con una fuente YouTube válida produce un embed válido y seguro.

---

## AC-011 — Lección sin video

Una lección sin video se muestra sin error y conserva su contenido textual.

---

## AC-012 — Resolver anterior

Kaanbal devuelve correctamente la lección anterior según orden curricular.

---

## AC-013 — Resolver siguiente

Kaanbal devuelve correctamente la siguiente lección.

---

## AC-014 — Primera sin anterior

Para la primera lección:

`previous = null`

o equivalente.

---

## AC-015 — Última sin siguiente

Para la última lección:

`next = null`

o equivalente.

---

## AC-016 — Impedir acceso cruzado

Una URL/request que presente:

- Course A
- Lesson perteneciente a Course B

no debe conceder acceso al contenido como si la relación fuera válida.

---

## AC-017 — Curso inexistente seguro

Solicitar un curso inexistente produce una respuesta segura acorde a WordPress.

---

## AC-018 — Lección inexistente segura

Solicitar una lección inexistente produce una respuesta segura.

---

## AC-019 — Curso vacío

Un curso sin módulos puede renderizarse correctamente.

---

## AC-020 — Módulo vacío

Un módulo sin lecciones puede aparecer correctamente.

---

## AC-021 — Sin modificación de progreso

Abrir un curso o lección no crea ni modifica registros de progreso.

---

## AC-022 — No depender del frontend para seguridad

Acceder directamente a una URL protegida sin matrícula debe fallar aunque el usuario conozca el identificador de la lección.

---

# 9. Casos Límite

## EC-001 — Matrícula revocada durante sesión

La siguiente request protegida debe reevaluar autorización y negar acceso.

No es necesario cerrar instantáneamente una página ya cargada.

---

## EC-002 — Curso eliminado

Debe tratarse como contenido inexistente.

---

## EC-003 — Módulo eliminado

Una lección con relación huérfana no debe producir fatal error.

---

## EC-004 — Lección huérfana

No debe exponerse como contenido válido del curso.

---

## EC-005 — Fuente YouTube inválida almacenada previamente

No debe generarse un iframe inseguro.

---

## EC-006 — Curso con una sola lección

La lección tendrá:

- previous = null
- next = null

---

## EC-007 — Curso con módulos vacíos intermedios

La navegación anterior/siguiente debe considerar únicamente las lecciones reales del curriculum.

---

# 10. Dependencias

## SPEC-001

Required.

## SPEC-002

Required.

Debe proporcionar cursos, módulos, lecciones y Curriculum Service.

## SPEC-003

Required.

Debe proporcionar enrollments y estados de acceso.

## WooCommerce

No debe ser requerido directamente por la capa de player.

El acceso depende de Enrollment, no de consultar pedidos WooCommerce en cada request.

---

# 11. Riesgos

## RISK-001 — IDOR

Permitir abrir lecciones por ID sin validar acceso podría exponer contenido.

Mitigación:

autorización server-side y ownership curricular.

---

## RISK-002 — Lógica de acceso duplicada

Diferentes controllers/templates podrían interpretar estados de forma distinta.

Mitigación:

servicio único de autorización.

---

## RISK-003 — Player demasiado acoplado

Consultar directamente WooCommerce desde el player generaría dependencia innecesaria.

Mitigación:

usar Enrollment.

---

## RISK-004 — Embed inseguro

Renderizar URL/HTML recibido directamente puede introducir riesgos XSS.

Mitigación:

usar provider normalizado y generar URL controlada.

---

## RISK-005 — Scope creep hacia progreso

Es fácil convertir navegación/player en tracking.

Mitigación:

SPEC-004 es estrictamente read-only respecto al progreso.

---

# 12. Decisiones Pendientes

El plan puede resolver:

- mecanismo público exacto: rewrite rules, endpoints, templates o shortcodes
- URLs definitivas
- política UX para acceso denegado
- si usuario no autenticado redirige a login o recibe vista de acceso requerida
- cómo integrar templates con themes
- atributos permitidos del iframe YouTube

Estas decisiones no pueden debilitar la autorización server-side.

---

# 13. Referencias

- `docs/project-context.md`
- `docs/architecture.md`
- `docs/data-model.md`
- `docs/testing-strategy.md`
- `docs/specs/001-plugin-foundation/`
- `docs/specs/002-courses-and-curriculum/`
- `docs/specs/003-woocommerce-enrollment/`