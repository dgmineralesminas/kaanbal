# Plan — SPEC-003 WooCommerce Enrollment

## 1. Resumen Técnico

SPEC-003 implementará el boundary comercial entre WooCommerce y Kaanbal.

La integración seguirá el flujo:

```text
WooCommerce order event
        ↓
WooCommerce listener / adapter
        ↓
Resolve purchased products
        ↓
Resolve associated courses
        ↓
Enrollment application service
        ↓
Enrollment Repository
        ↓
Enrollment Sources Repository
```

WooCommerce no debe convertirse en dependencia transversal del dominio académico.

---

## 2. Componentes Afectados

Estructura conceptual:

```text
src/
├── Enrollment/
│   ├── Application/
│   ├── Domain/
│   └── Infrastructure/
│
└── WooCommerce/
    ├── Application/
    ├── Infrastructure/
    └── Presentation/
```

Posibles componentes:

```text
EnrollmentRepository
EnrollmentSourceRepository
ProductCourseRepository

EnrollUserInCourse
GrantCoursesFromOrder
RevokeCoursesFromOrder

WooCommerceOrderListener
WooCommerceOrderAdapter
WooCommerceProductCourseAdmin
```

Los nombres pueden simplificarse.

---

## 3. Persistencia

Se implementarán las tablas previstas en `docs/data-model.md`.

### Product Courses

```text
{$wpdb->prefix}kaanbal_product_courses
```

Columnas mínimas:

```text
id
product_id
course_id
created_at
```

Constraint:

```text
UNIQUE(product_id, course_id)
```

---

### Enrollments

```text
{$wpdb->prefix}kaanbal_enrollments
```

Columnas mínimas:

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

Constraint:

```text
UNIQUE(user_id, course_id)
```

---

### Enrollment Sources

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

---

## 4. Schema Migration

Las nuevas tablas deben incorporarse mediante el Schema Manager definido en SPEC-001.

La migración debe:

- ser versionada
- ser idempotente
- crear índices necesarios
- no destruir datos existentes

---

## 5. ProductCourseRepository

Responsabilidades:

```text
attachCourse(productId, courseId)
detachCourse(productId, courseId)
findCoursesByProduct(productId)
findProductsByCourse(courseId)
```

Debe validar relaciones a través de la capa apropiada.

---

## 6. EnrollmentRepository

Operaciones previstas:

```text
findByUserAndCourse()
create()
activate()
revoke()
markCompleted()
```

`markCompleted()` puede existir como preparación del modelo pero no debe utilizarse todavía para finalización académica si no lo requiere la SPEC.

Evitar implementar comportamiento futuro innecesario.

---

## 7. EnrollmentSourceRepository

Operaciones previstas:

```text
addSource()
findValidSources()
findByOrder()
revokeByOrder()
```

Debe permitir decidir posteriormente:

```text
Does this enrollment still have a valid source?
```

---

## 8. Idempotencia

La idempotencia debe protegerse en dos capas:

### Base de datos

```text
UNIQUE(user_id, course_id)
UNIQUE(product_id, course_id)
```

y una restricción adecuada para fuentes.

### Aplicación

Antes de crear:

```text
find existing
→ reuse
```

La aplicación no debe depender únicamente de un `SELECT` previo para evitar carreras.

---

## 9. Fuente Comercial Única

Preferencia:

utilizar:

```text
order_id
order_item_id
product_id
```

como identidad funcional de una fuente WooCommerce.

Si `order_item_id` identifica inequívocamente el item:

```text
UNIQUE(enrollment_id, order_item_id, product_id)
```

puede ser suficiente.

Decisión para WooCommerce: la fuente se identifica por
`enrollment_id + source_type + order_item_id + product_id`. Los productos
variables se resuelven mediante el `product_id` padre del item; las variaciones
heredan las asociaciones del producto padre.

---

## 10. Adapter WooCommerce

Debe encapsular operaciones como:

```text
getCustomerId()
getItems()
getOrderId()
getStatus()
```

La lógica de Enrollment no debería recorrer directamente objetos WooCommerce donde pueda evitarse.

---

## 11. Hooks WooCommerce

Se deben utilizar hooks públicos y soportados por WooCommerce.

Hooks validados para esta versión:

```text
woocommerce_order_status_processing
woocommerce_order_status_completed
woocommerce_order_status_cancelled
woocommerce_order_status_refunded
```

`woocommerce_order_status_processing` y
`woocommerce_order_status_completed` conceden acceso. `cancelled` revoca las
fuentes comerciales del pedido. `refunded` se escucha solo mediante
`woocommerce_order_status_refunded`, que representa el estado de reembolso
total; no se registra `woocommerce_order_refunded` ni
`woocommerce_order_partially_refunded` en este MVP.

---

## 12. Grant Flow

```text
Order → processing/completed
        ↓
customer ID
        ↓
order items
        ↓
product IDs
        ↓
associated courses
        ↓
for each course
    ↓
find/create enrollment
    ↓
activate if needed
    ↓
find/create source
```

Debe ser idempotente.

---

## 13. Revoke Flow

```text
Order → cancelled/refunded
        ↓
find sources from order
        ↓
mark sources revoked
        ↓
for each affected enrollment
    ↓
valid source still exists?
        │
      Yes → keep active
        │
       No → revoke enrollment
```

---

## 14. Reactivation

Si un enrollment está:

```text
revoked
```

y aparece una nueva fuente válida:

```text
status → active
revoked_at → null
```

Debe conservar historial previo de fuentes.

---

## 15. Product Admin UI

Dentro de WooCommerce Product Admin se debe añadir una interfaz mínima para seleccionar cursos.

Preferencia:

- metabox en el editor clásico de producto WooCommerce instalado
- selector múltiple de cursos publicados/administrables

No se requiere UI visual avanzada.

---

## 16. Seguridad Administrativa

Al guardar asociaciones:

- nonce
- `current_user_can('edit_post', $product_id)`
- product validation
- course validation
- sanitización de IDs

No confiar en opciones enviadas por el navegador.

---

## 17. Guest Orders

Si:

```text
customer_id = 0
```

no se debe crear Enrollment.

El listener debe terminar de forma segura.

El soporte a guest checkout + creación automática de cuenta sería una feature separada.

---

## 18. Eventos Internos

Después de una matrícula nueva o reactivada puede exponerse:

```text
kaanbal_student_enrolled
```

Solo si la API interna se considera suficientemente estable.

No deben dispararse eventos duplicados por reprocesamiento del mismo source.

---

## 19. Transacciones

Evaluar transacción para:

```text
create/reactivate enrollment
+
create source
```

mediante una transacción de base de datos con `START TRANSACTION`, `COMMIT` y
`ROLLBACK`. Los constraints únicos siguen siendo la defensa final ante carreras.

Debe evitarse un estado donde:

```text
source exists
but enrollment missing
```

o viceversa.

---

## 20. Concurrencia

Escenarios:

```text
processing + completed casi simultáneos
double webhook
manual retry
```

Los constraints deben ser la última defensa contra duplicados.

---

## 21. Testing Unitario

Candidatos:

```text
GrantCoursesFromOrderTest
RevokeCoursesFromOrderTest
EnrollmentAccessPolicyTest
```

---

## 22. Testing de Integración

Candidatos:

```text
ProductCourseRepositoryTest
EnrollmentRepositoryTest
EnrollmentSourceRepositoryTest

WooCommerceProcessingEnrollmentTest
WooCommerceCompletedEnrollmentTest
WooCommerceCancelledEnrollmentTest
WooCommerceRefundedEnrollmentTest
```

---

## 23. Casos Obligatorios de Test

### Asociación

```text
1 product → 1 course
1 product → N courses
N products → same course
```

### Idempotencia

```text
same processing event twice
processing → completed
same order handled multiple times
```

### Estado

```text
cancelled
refunded
alternative valid source
reactivation
```

### Seguridad

```text
unauthorized product association save
invalid course
invalid product
```

### Edge

```text
guest order
product without courses
deleted course
```

---

## 24. N+1

Al procesar un pedido con múltiples items debe evitarse:

```text
product
→ query
product
→ query
product
→ query
```

cuando una consulta agrupada por IDs pueda resolver asociaciones.

No es necesario sobreoptimizar pedidos pequeños, pero el diseño debe ser razonable.

---

## 25. Scope Protection

No implementar:

```text
lesson progress
course player
quiz
certificate
student dashboard
```

La existencia de Enrollment únicamente representa acceso.

---

## 26. Secuencia de Implementación

```text
1. schema migration
2. ProductCourseRepository
3. product-course admin UI
4. EnrollmentRepository
5. EnrollmentSourceRepository
6. enrollment application service
7. WooCommerce adapter
8. grant listener
9. revoke listener
10. reactivation
11. idempotency/concurrency handling
12. security
13. tests
14. quality gate
```

---

## 27. Riesgos Técnicos

### TECH-001

Hooks de estado pueden ejecutarse múltiples veces.

### TECH-002

Refund parcial puede requerir granularidad por order item.

### TECH-003

Unique constraints sin tratamiento apropiado de race conditions pueden generar errores visibles.

### TECH-004

Un adapter demasiado abstracto puede agregar complejidad sin valor.

Mantenerlo mínimo.

---

## 28. Decisiones

### DEC-001

`processing` concede acceso.

### DEC-002

`completed` concede acceso de forma idempotente.

### DEC-003

`cancelled` revoca fuentes del pedido.

### DEC-004

`refunded` revoca fuentes del pedido.

### DEC-005

Una fuente alternativa válida conserva la matrícula activa.

### DEC-006

Una matrícula puede reactivarse.

### DEC-007

Guest checkout no crea matrícula en esta SPEC.

### DEC-008

Enrollment y Enrollment Source son entidades distintas.

### DEC-009

Solo el reembolso total revoca fuentes comerciales. Los refunds parciales no
alteran acceso académico en esta SPEC.

### DEC-010

La administración de asociaciones requiere editar el producto WordPress;
la validación server-side exige producto WooCommerce y curso Kaanbal válidos.

---

## 29. Condición de Finalización

SPEC-003 estará lista para auditoría cuando:

- Product ↔ Course funcione
- bundle funcione
- matrícula sea idempotente
- sources sean trazables
- processing funcione
- completed no duplique
- cancelled/refunded actualicen fuentes
- acceso alternativo se preserve
- reactivación funcione
- guest order sea seguro
- tests obligatorios pasen
- quality gate pase
- `Ready for audit: Yes`
