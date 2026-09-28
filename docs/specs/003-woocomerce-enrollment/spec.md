# SPEC-003 — WooCommerce Enrollment

Status: Ready for implementation

## 1. Objetivo

Implementar la integración entre WooCommerce y Kaanbal para que la compra de productos pueda conceder acceso académico a uno o varios cursos.

Esta SPEC debe cubrir:

- asociación entre productos WooCommerce y cursos Kaanbal
- creación de matrículas
- múltiples cursos por producto
- múltiples productos por curso
- idempotencia
- trazabilidad del origen comercial
- concesión automática de acceso al procesar un pedido válido
- comportamiento base ante cancelaciones y reembolsos según política aprobada

No debe implementar todavía:

- progreso
- reproducción de curso
- quiz
- certificados
- dashboard del alumno

---

## 2. Contexto

Kaanbal separa explícitamente:

```text
WooCommerce
→ proceso comercial

Kaanbal
→ proceso educativo
```

WooCommerce gestiona:

- productos
- precios
- promociones
- checkout
- pagos
- pedidos
- cupones
- reembolsos

Kaanbal interpreta determinados eventos comerciales para conceder o revocar acceso académico.

La relación aprobada es:

```text
WooCommerce Product
       N
       ↕
       N
Kaanbal Course
```

Un producto puede conceder varios cursos.

Un mismo curso puede formar parte de varios productos.

---

## 3. Alcance

Esta SPEC incluye:

- relación Product → Courses
- tabla de relación producto-curso
- interfaz administrativa para seleccionar cursos asociados a un producto
- Enrollment
- Enrollment Source
- matrícula manual a nivel de infraestructura si resulta necesaria para el modelo
- listener/adaptador WooCommerce
- resolución de productos comprados
- resolución de cursos concedidos
- concesión automática de matrícula
- idempotencia
- trazabilidad de order/product/order item
- comportamiento ante procesamiento repetido
- comportamiento ante usuario ya matriculado
- comportamiento ante múltiples productos que conceden el mismo curso
- revocación basada en fuentes comerciales cuando aplique
- hooks/events internos relevantes

---

## 4. Fuera de Alcance

Esta SPEC no incluye:

- progreso de lecciones
- botón Marcar como completada
- reproductor del curso
- frontend del alumno
- quiz
- certificados
- estadísticas académicas
- reportes comerciales
- bundles propios de Kaanbal
- checkout propio
- carrito propio
- pasarelas de pago
- suscripciones
- memberships
- recurring payments
- WooCommerce Subscriptions
- recuperación de carrito
- afiliados
- licenciamiento
- gift cards
- códigos promocionales propios

WooCommerce sigue siendo responsable del comercio.

---

## 5. Requisitos Funcionales

### RF-001 — Asociación producto-curso

Un producto WooCommerce debe poder asociarse a uno o varios cursos Kaanbal.

---

### RF-002 — Asociación inversa

Un curso puede estar asociado a varios productos WooCommerce.

---

### RF-003 — Administración de asociación

Un administrador autorizado debe poder configurar los cursos concedidos por un producto.

---

### RF-004 — Producto sin cursos

Un producto WooCommerce puede existir sin conceder ningún curso.

Kaanbal no debe alterar su funcionamiento comercial.

---

### RF-005 — Matrícula

Kaanbal debe poder crear una matrícula para:

```text
user_id + course_id
```

---

### RF-006 — Matrícula única

Un usuario no debe tener múltiples matrículas funcionales para el mismo curso.

---

### RF-007 — Fuente de matrícula

La matrícula debe conservar información sobre las fuentes que concedieron acceso.

---

### RF-008 — Múltiples fuentes

Una matrícula puede tener varias fuentes de acceso válidas.

Ejemplo:

```text
Producto A → Curso X
Producto B → Curso X
```

---

### RF-009 — Pedido válido

Cuando un pedido alcance el estado aprobado para conceder acceso, Kaanbal debe:

1. identificar al usuario
2. identificar los productos relevantes
3. resolver cursos asociados
4. crear o reutilizar matrículas
5. registrar fuentes de acceso

---

### RF-010 — Compra de bundle

Si un producto concede múltiples cursos, todos los cursos aplicables deben ser procesados.

---

### RF-011 — Productos solapados

Si un pedido contiene varios productos que conceden el mismo curso, debe existir una sola matrícula para ese usuario y curso.

Las diferentes fuentes pueden conservarse.

---

### RF-012 — Reprocesamiento

Procesar nuevamente el mismo pedido no debe duplicar:

- matrículas
- fuentes comerciales equivalentes

---

### RF-013 — Usuario ya matriculado

Si el usuario ya tiene matrícula válida, procesar una nueva compra del mismo curso debe reutilizar la matrícula existente y registrar la nueva fuente cuando corresponda.

---

### RF-014 — Acceso activo

Una matrícula con al menos una fuente válida debe permitir determinar que el usuario conserva acceso académico.

---

### RF-015 — Revocación de fuente

Una fuente comercial debe poder marcarse como revocada sin eliminar el historial.

---

### RF-016 — Revocación de matrícula

Cuando ya no exista ninguna fuente válida, Kaanbal debe poder cambiar la matrícula a estado revocado según la política definida.

---

### RF-017 — Historial

Las operaciones de revocación no deben borrar el historial comercial de la matrícula.

---

### RF-018 — WooCommerce ausente

Kaanbal debe seguir cargando sin fatal error cuando WooCommerce está desactivado.

La funcionalidad de enrollment comercial simplemente no estará disponible.

---

## 6. Política Inicial de Estados WooCommerce

Para esta SPEC se adopta inicialmente la siguiente política:

### Conceder acceso

```text
processing
completed
```

Ambos estados podrán conceder acceso.

Justificación:

Los pedidos pagados de productos virtuales/digitales pueden transitar por distintos estados dependiendo de la configuración de WooCommerce y la pasarela.

La operación debe ser idempotente, por lo que recibir ambos eventos no debe duplicar matrícula.

---

### No conceder acceso

```text
pending
on-hold
failed
cancelled
refunded
```

por sí solos no deben crear una nueva matrícula.

---

### Revocación

Para el MVP de esta SPEC:

```text
cancelled
refunded
```

deben provocar la revocación de las fuentes comerciales correspondientes al pedido.

Si después de revocar esas fuentes existe otra fuente válida para el mismo curso:

```text
enrollment remains active
```

Si no queda ninguna fuente válida:

```text
enrollment → revoked
```

---

### Completed después de Processing

Si un pedido ya fue procesado en:

```text
processing
```

y posteriormente cambia a:

```text
completed
```

no debe crear duplicados.

---

## 7. Reglas de Negocio

### RB-001

Un usuario tiene como máximo una matrícula por curso.

---

### RB-002

Una matrícula puede tener múltiples fuentes.

---

### RB-003

Una fuente WooCommerce representa una concesión concreta derivada de un item de pedido/producto.

---

### RB-004

La concesión debe ser idempotente.

---

### RB-005

La revocación debe actuar sobre fuentes antes de decidir si se revoca la matrícula.

---

### RB-006

Una matrícula revocada puede volver a activarse si posteriormente aparece una nueva fuente válida.

---

### RB-007

El estado del pedido no debe modificar:

- progreso
- quizzes
- certificados

en esta SPEC.

---

### RB-008

La eliminación física de matrículas o fuentes no es el mecanismo normal para manejar refunds/cancelaciones.

---

## 8. Criterios de Aceptación

### AC-001 — Relación muchos a muchos

Un producto puede asociarse con múltiples cursos y un curso puede asociarse con múltiples productos.

---

### AC-002 — Evitar asociación duplicada

La misma combinación:

```text
product_id + course_id
```

no puede almacenarse dos veces.

---

### AC-003 — Configuración administrativa

Un usuario autorizado puede configurar los cursos asociados a un producto WooCommerce.

---

### AC-004 — Producto sin asociación

Un producto sin cursos Kaanbal sigue funcionando normalmente en WooCommerce.

---

### AC-005 — Crear matrícula

Dado un usuario válido y un curso válido, Kaanbal puede crear una matrícula activa.

---

### AC-006 — Matrícula única

Crear matrícula repetidamente para el mismo:

```text
user_id + course_id
```

no genera múltiples matrículas.

---

### AC-007 — Registrar fuente

Una concesión WooCommerce registra suficiente información para identificar:

- pedido
- item del pedido cuando esté disponible
- producto
- matrícula

---

### AC-008 — Producto concede múltiples cursos

Si un producto está asociado a N cursos, el procesamiento válido concede matrícula para los N cursos.

---

### AC-009 — Productos solapados

Si dos productos del pedido conceden el mismo curso:

- existe una sola matrícula
- pueden existir múltiples fuentes válidas

---

### AC-010 — Procesamiento idempotente

Procesar el mismo pedido más de una vez no duplica matrículas ni fuentes equivalentes.

---

### AC-011 — Processing concede acceso

Cuando un pedido válido pasa a `processing`, Kaanbal concede los cursos asociados.

---

### AC-012 — Completed es seguro

Cuando posteriormente el mismo pedido pasa a `completed`, el resultado académico no se duplica.

---

### AC-013 — Pedido cancelado

Cuando un pedido previamente concedido pasa a `cancelled`, las fuentes provenientes de ese pedido son revocadas.

---

### AC-014 — Pedido reembolsado

Cuando un pedido previamente concedido pasa a `refunded`, las fuentes provenientes de ese pedido son revocadas.

---

### AC-015 — Mantener matrícula por fuente alternativa

Si una matrícula tiene al menos otra fuente válida después de refund/cancel:

```text
status != revoked
```

---

### AC-016 — Revocar sin fuentes válidas

Si después de refund/cancel no quedan fuentes válidas:

```text
enrollment.status = revoked
```

---

### AC-017 — Reactivar matrícula

Si una matrícula revocada recibe posteriormente una nueva fuente válida:

```text
enrollment.status = active
```

---

### AC-018 — WooCommerce ausente

Si WooCommerce está desactivado, Kaanbal carga sin fatal errors.

---

### AC-019 — Seguridad administrativa

Un usuario sin capability suficiente no puede modificar asociaciones producto-curso.

---

### AC-020 — Integridad

No se aceptan como válidas asociaciones a:

- producto inexistente
- curso inexistente
- post que no sea producto WooCommerce
- post que no sea curso Kaanbal

---

### AC-021 — Sin scope creep académico

Procesar pedidos no debe:

- marcar lecciones completas
- aprobar quizzes
- emitir certificados

---

## 9. Casos Límite

### EC-001 — Pedido sin usuario

Si el pedido no puede asociarse a un usuario WordPress válido, Kaanbal no debe crear una matrícula inválida.

Debe registrar el resultado de forma controlada cuando exista logging disponible.

---

### EC-002 — Producto sin cursos

Se ignora desde el punto de vista académico.

---

### EC-003 — Curso eliminado

Una asociación a un curso inexistente no debe producir matrícula válida.

---

### EC-004 — Producto eliminado

Una asociación huérfana no debe provocar fatal error.

---

### EC-005 — Procesamiento doble

Dos callbacks cercanos o repetidos no deben generar duplicados.

---

### EC-006 — Processing y Completed

Ambos eventos sobre el mismo pedido deben terminar en el mismo resultado académico.

---

### EC-007 — Refund con otra compra válida

La matrícula debe continuar activa.

---

### EC-008 — Refund completo sin otra fuente

La matrícula pasa a revoked.

---

### EC-010 — Refund parcial

Los refunds parciales no revocan fuentes ni matrículas en este MVP.

La revocación comercial se limita al pedido que alcanza el estado `refunded`
por un reembolso total.

---

### EC-009 — Nueva compra después de revocación

La matrícula existente puede reactivarse.

No es necesario crear una segunda matrícula.

---

### EC-010 — Dos items del mismo producto

La implementación debe evitar fuentes duplicadas equivalentes y conservar trazabilidad razonable.

---

## 10. Dependencias

### SPEC-001 — Plugin Foundation

Required.

---

### SPEC-002 — Courses and Curriculum

Required.

La relación comercial necesita cursos existentes.

---

### WooCommerce

Required para la funcionalidad comercial de esta SPEC.

Kaanbal debe seguir cargando cuando WooCommerce esté ausente.

---

## 11. Riesgos

### RISK-001 — Doble procesamiento

WooCommerce puede disparar más de un evento relevante para el mismo pedido.

Mitigación:

idempotencia en aplicación y esquema.

---

### RISK-002 — Refund incorrecto

Revocar directamente matrícula al primer refund podría quitar acceso adquirido por otra compra.

Mitigación:

separar Enrollment de Enrollment Sources.

---

### RISK-003 — Acoplamiento con WooCommerce

Usar `WC_Order` y `WC_Product` dentro del dominio complicaría mantenimiento.

Mitigación:

boundary/adapters.

---

### RISK-004 — Concurrencia

Dos requests pueden intentar conceder el mismo curso al mismo tiempo.

Mitigación:

unique constraints + operaciones idempotentes.

---

### RISK-005 — Guest checkout

Un pedido sin usuario WordPress no puede producir una matrícula académica normal.

Mitigación:

no crear registros inválidos y documentar comportamiento.

---

## 12. Decisiones Pendientes

No son blocker para definir esta SPEC, pero el `plan.md` debe concretar:

- capability usada para configurar asociaciones
- hooks WooCommerce exactos
- estrategia para identificar fuente única por order item
- transacciones cuando apliquen
- tratamiento de refunds parciales por item si WooCommerce provee suficiente información

Los refunds parciales por item exceden el alcance de este MVP: no revocan
fuentes ni matrículas. Un Change Request futuro podrá definir revocación
granular por item.

---

## 13. Referencias

- `docs/project-context.md`
- `docs/architecture.md`
- `docs/data-model.md`
- `docs/testing-strategy.md`
- `docs/specs/001-plugin-foundation/`
- `docs/specs/002-courses-and-curriculum/`
