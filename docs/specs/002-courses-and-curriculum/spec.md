# SPEC-002 — Courses and Curriculum

Status: Draft

## 1. Objetivo

Implementar la estructura editorial base de Kaanbal para crear y administrar:

- cursos
- módulos
- lecciones
- orden del temario
- información básica del curso
- video asociado a una lección

Esta SPEC debe permitir construir completamente el contenido de un curso desde WordPress Admin.

No debe implementar todavía:

- matrículas
- control de acceso por compra
- progreso del alumno
- quizzes
- certificados

---

## 2. Contexto

SPEC-001 establece la infraestructura base de Kaanbal.

SPEC-002 será la primera SPEC funcional del LMS.

La jerarquía aprobada es:

```text
Curso
    ↓
Módulos
    ↓
Lecciones
```

Ejemplo:

```text
Hipertrofia basada en evidencia

├── Fundamentos
│   ├── Introducción
│   ├── Adaptación muscular
│   └── Tensión mecánica
│
└── Programación
    ├── Volumen
    ├── Intensidad
    └── Frecuencia
```

Las decisiones globales están documentadas en:

- `docs/project-context.md`
- `docs/architecture.md`
- `docs/data-model.md`

---

## 3. Alcance

Esta SPEC incluye:

- Custom Post Type de cursos
- Custom Post Type de módulos
- Custom Post Type de lecciones
- asociación Course → Modules
- asociación Module → Lessons
- orden de módulos
- orden de lecciones
- administración básica del curso
- duración del curso
- instructor
- proveedor de video de lección
- video de YouTube
- validación de jerarquía
- consultas necesarias para obtener un temario ordenado
- interfaz administrativa mínima para gestionar estas relaciones

---

## 4. Fuera de Alcance

Esta SPEC no incluye:

- WooCommerce
- productos asociados a cursos
- matrículas
- autorización por compra
- panel del alumno
- progreso
- botón "Marcar como completada"
- quizzes
- preguntas
- certificados
- reportes
- frontend definitivo del curso
- tracking de reproducción de video
- porcentaje visto
- posición del reproductor
- Vimeo
- Bunny Stream
- Mux
- Cloudflare Stream
- prerequisitos
- drip content
- navegación secuencial obligatoria
- drag & drop sofisticado tipo LearnDash

Una UI avanzada de Course Builder puede desarrollarse posteriormente.

---

## 5. Requisitos Funcionales

### RF-001 — Cursos

Un administrador autorizado debe poder:

- crear
- editar
- publicar
- enviar a papelera

un curso Kaanbal.

---

### RF-002 — Metadata del curso

Un curso debe permitir configurar al menos:

- nombre
- descripción/contenido
- imagen destacada cuando WordPress lo permita
- duración
- nombre de instructor

---

### RF-003 — Módulos

Un administrador debe poder crear módulos asociados a un curso.

Cada módulo pertenece a un único curso.

---

### RF-004 — Orden de módulos

Los módulos de un curso deben tener un orden explícito.

---

### RF-005 — Lecciones

Un administrador debe poder crear lecciones asociadas a un módulo.

Cada lección pertenece a un único módulo.

---

### RF-006 — Orden de lecciones

Las lecciones dentro de un módulo deben tener un orden explícito.

---

### RF-007 — Video de lección

Una lección debe poder almacenar una fuente de video.

Proveedor inicial soportado:

`youtube`

---

### RF-008 — Fuente YouTube

Para una lección YouTube debe ser posible registrar:

- URL de YouTube

o una representación equivalente que permita obtener un identificador válido del video.

---

### RF-009 — Resolución del temario

Kaanbal debe poder consultar un curso y devolver:

- módulos ordenados
- lecciones ordenadas dentro de cada módulo

---

### RF-010 — Integridad Course → Module

Un módulo no debe asociarse simultáneamente a múltiples cursos.

---

### RF-011 — Integridad Module → Lesson

Una lección no debe asociarse simultáneamente a múltiples módulos.

---

### RF-012 — Curso derivado de una lección

Dada una lección, Kaanbal debe poder determinar a qué:

- módulo
- curso

pertenece.

---

### RF-013 — Curso sin módulos

Debe ser posible guardar inicialmente un curso sin módulos.

Esto permite construir contenido de forma incremental.

---

### RF-014 — Módulo sin lecciones

Debe ser posible guardar inicialmente un módulo sin lecciones.

---

## 6. Requisitos No Funcionales

### RNF-001 — APIs de WordPress

Cursos, módulos y lecciones deben utilizar las APIs editoriales de WordPress según la arquitectura aprobada.

---

### RNF-002 — Sin lógica transaccional

Esta SPEC no debe introducir tablas de:

- progreso
- matrículas
- certificados
- intentos de quiz

---

### RNF-003 — Consultas eficientes

Obtener un temario no debe generar consultas N+1 evitables.

---

### RNF-004 — Seguridad administrativa

Las operaciones administrativas deben validar:

- autenticación
- capabilities
- nonces cuando corresponda
- sanitización
- escaping

---

### RNF-005 — Integridad

Las relaciones inválidas deben rechazarse del lado servidor.

No debe confiarse únicamente en la interfaz.

---

### RNF-006 — YouTube desacoplado

El modelo de lección debe identificar el proveedor de video sin convertir la lógica del curso en dependiente directamente de YouTube.

---

### RNF-007 — Navegación libre

La estructura de contenido no debe introducir restricciones secuenciales.

---

## 7. Reglas de Negocio

### RB-001

Un módulo pertenece exactamente a un curso cuando está asociado.

---

### RB-002

Una lección pertenece exactamente a un módulo cuando está asociada.

---

### RB-003

La pertenencia al curso de una lección se deriva de su módulo.

---

### RB-004

El orden de módulos es independiente entre cursos.

---

### RB-005

El orden de lecciones es independiente entre módulos.

---

### RB-006

Una lección puede existir mientras el administrador construye contenido aunque todavía no tenga video.

---

### RB-007

La fuente de video no determina si una lección está completada.

El progreso pertenece a una SPEC posterior.

---

## 8. Criterios de Aceptación

### AC-001 — Registrar Course CPT

Kaanbal registra un tipo de contenido para cursos administrable desde WordPress.

---

### AC-002 — Crear curso

Un usuario con permisos puede crear y guardar un curso con:

- título
- contenido
- duración
- instructor

---

### AC-003 — Registrar Module CPT

Kaanbal registra módulos como contenido gestionable.

---

### AC-004 — Asociar módulo a curso

Un módulo puede asociarse a exactamente un curso válido.

---

### AC-005 — Ordenar módulos

Los módulos asociados a un curso pueden recuperarse en el orden configurado.

---

### AC-006 — Registrar Lesson CPT

Kaanbal registra lecciones como contenido gestionable.

---

### AC-007 — Asociar lección a módulo

Una lección puede asociarse a exactamente un módulo válido.

---

### AC-008 — Ordenar lecciones

Las lecciones de un módulo pueden recuperarse en el orden configurado.

---

### AC-009 — Configurar video YouTube

Una lección puede guardar una fuente YouTube válida.

---

### AC-010 — Rechazar proveedor no soportado

Una lección no debe aceptar silenciosamente un proveedor de video desconocido como si fuera válido.

---

### AC-011 — Recuperar temario

Dado un curso, Kaanbal puede construir una estructura ordenada:

```text
Course
→ Modules
→ Lessons
```

---

### AC-012 — Resolver curso desde lección

Dada una lección válida, Kaanbal puede resolver:

- módulo
- curso

---

### AC-013 — Validar relaciones

No debe ser posible persistir una relación:

- Module → Course inexistente
- Lesson → Module inexistente

como relación válida.

---

### AC-014 — Curso vacío válido

Un curso puede existir sin módulos.

---

### AC-015 — Módulo vacío válido

Un módulo puede existir sin lecciones.

---

### AC-016 — Seguridad administrativa

Usuarios sin las capabilities requeridas no pueden modificar relaciones o metadata de Kaanbal mediante las acciones administrativas implementadas.

---

### AC-017 — Sin funcionalidades futuras

La implementación no introduce lógica de:

- progreso
- matrícula
- quiz
- certificados

como efecto colateral de esta SPEC.

---

## 9. Casos Límite

### EC-001

Curso sin módulos.

Resultado:

válido.

---

### EC-002

Módulo sin lecciones.

Resultado:

válido.

---

### EC-003

Módulo apunta a curso eliminado o inexistente.

La relación debe considerarse inválida.

---

### EC-004

Lección apunta a módulo eliminado o inexistente.

La relación debe considerarse inválida.

---

### EC-005

Dos módulos con el mismo `menu_order`.

La implementación debe definir un criterio secundario determinista.

Ejemplo posible:

```text
menu_order
→ ID
```

---

### EC-006

Dos lecciones con el mismo orden.

Debe aplicarse orden secundario determinista.

---

### EC-007

URL YouTube inválida.

No debe convertirse en un embed válido.

---

### EC-008

Proveedor vacío.

La lección puede permanecer sin video mientras está siendo editada.

---

## 10. Dependencias

### SPEC-001

Required.

La infraestructura Foundation debe estar implementada y aprobada.

---

### WordPress

Required.

---

### WooCommerce

No requerido para SPEC-002.

---

## 11. Riesgos

### RISK-001 — Jerarquía difícil de mantener

Modelar Course, Module y Lesson de forma incorrecta puede complicar futuras consultas.

Mitigación:

mantener relaciones simples y explícitas.

---

### RISK-002 — N+1

Construir un temario cargando cada entidad individualmente puede generar consultas excesivas.

Mitigación:

diseñar queries/batching razonable.

---

### RISK-003 — Acoplamiento con YouTube

Integrar directamente lógica de YouTube en Course/Lesson puede dificultar futuros providers.

Mitigación:

usar metadata de provider y una abstracción mínima.

---

### RISK-004 — Course Builder prematuro

Intentar replicar el builder visual de LearnDash aumentaría notablemente el alcance.

Mitigación:

administración funcional mínima primero.

---

## 12. Decisiones Pendientes

La SPEC puede definir durante `plan.md`:

- uso exacto de `post_parent` vs metadata para relaciones
- uso exacto de `menu_order`
- capabilities de edición
- forma mínima de UI administrativa
- normalización de URL/ID YouTube

Estas decisiones deben respetar:

`docs/architecture.md`

y:

`docs/data-model.md`

---

## 13. Referencias

- `docs/project-context.md`
- `docs/architecture.md`
- `docs/data-model.md`
- `docs/testing-strategy.md`
- `docs/specs/001-plugin-foundation/`