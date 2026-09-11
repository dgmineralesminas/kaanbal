# SPEC-001 — Plugin Foundation

Status: Ready for implementation

## 1. Objetivo

Crear la infraestructura mínima necesaria para que Kaanbal funcione como un plugin WordPress independiente, mantenible y preparado para soportar las siguientes SPEC del LMS.

Esta SPEC debe establecer:

- bootstrap del plugin
- autoloading
- lifecycle básico
- validación de requisitos
- estructura modular inicial
- infraestructura base de persistencia
- versionado interno
- infraestructura de pruebas
- quality gate inicial

No debe implementar todavía funcionalidades LMS completas.

---

## 2. Contexto

Kaanbal será un plugin LMS para WordPress.

WooCommerce será responsable del proceso comercial y Kaanbal del proceso educativo.

Las decisiones globales relevantes están documentadas en:

- `docs/project-context.md`
- `docs/architecture.md`
- `docs/data-model.md`
- `docs/testing-strategy.md`
- `docs/agents.md`

La infraestructura creada en esta SPEC será utilizada por futuras SPEC como:

- cursos y temarios
- integración WooCommerce
- matrículas
- progreso
- quizzes
- certificados
- panel del alumno

---

## 3. Alcance

Esta SPEC incluye:

- archivo principal del plugin
- metadata básica del plugin
- protección contra acceso directo
- Composer
- PSR-4 autoloading
- namespace raíz `Kaanbal\`
- clase principal de bootstrap
- lifecycle de activación
- lifecycle de desactivación
- verificación de requisitos mínimos
- versionado interno del plugin
- base para versionado de schema
- schema manager inicial
- registro inicial de servicios
- estructura modular base
- infraestructura inicial de pruebas
- configuración de checks de calidad razonables para el entorno
- compatibilidad segura cuando WooCommerce no esté activo

---

## 4. Fuera de Alcance

Esta SPEC NO incluye:

- creación de cursos
- módulos
- lecciones
- reproductor de YouTube
- matrículas
- asociaciones producto-curso
- integración funcional con pedidos WooCommerce
- progreso del alumno
- quizzes
- preguntas
- respuestas
- certificados
- panel del alumno
- reportes
- frontend LMS
- endpoints REST
- endpoints AJAX
- roles de alumno
- marketplace de instructores
- lógica de reembolsos
- lógica de finalización de cursos

Estas funcionalidades pertenecen a SPEC posteriores.

---

## 5. Requisitos Funcionales

### RF-001 — Registro del plugin

Kaanbal debe aparecer correctamente en la pantalla de plugins de WordPress.

### RF-002 — Activación

Un administrador debe poder activar Kaanbal sin errores fatales cuando el entorno cumple los requisitos mínimos definidos.

### RF-003 — Desactivación

Un administrador debe poder desactivar Kaanbal sin errores fatales.

La desactivación no debe eliminar datos persistentes del plugin.

### RF-004 — Bootstrap

Al cargarse WordPress, Kaanbal debe inicializar su bootstrap de forma controlada.

### RF-005 — Autoloading

Las clases internas deben cargarse mediante Composer PSR-4 usando el namespace:

`Kaanbal\`

### RF-006 — Requisitos del entorno

Kaanbal debe poder detectar si el entorno no cumple los requisitos mínimos soportados.

La política exacta de incompatibilidad deberá evitar fatal errors innecesarios.

### RF-007 — WooCommerce opcional en foundation

La ausencia o desactivación de WooCommerce no debe provocar un error fatal durante esta SPEC.

### RF-008 — Versionado interno

Kaanbal debe disponer de una constante o mecanismo equivalente para identificar la versión del plugin.

### RF-009 — Versionado de schema

Debe existir infraestructura inicial para almacenar y consultar la versión de schema del plugin.

### RF-010 — Schema Manager

Debe existir un componente central responsable de instalación o actualización futura de tablas propias.

La SPEC-001 no necesita crear todavía todas las tablas del LMS.

### RF-011 — Registro de servicios

Debe existir un mecanismo simple y explícito para registrar e inicializar servicios o módulos.

No se requiere un contenedor de dependencias complejo.

### RF-012 — Testing

El proyecto debe disponer de infraestructura suficiente para ejecutar pruebas automatizadas iniciales.

---

## 6. Requisitos No Funcionales

### RNF-001 — No modificar Core

Kaanbal no debe modificar:

- WordPress Core
- WooCommerce Core

### RNF-002 — Bootstrap pequeño

El archivo principal del plugin debe mantenerse pequeño y sin lógica de negocio.

### RNF-003 — Namespaces

El código PHP propio debe utilizar namespaces bajo:

`Kaanbal\`

salvo archivos especiales de WordPress que requieran otro formato.

### RNF-004 — Mantenibilidad

La infraestructura no debe introducir abstracciones innecesarias ni clases vacías sin responsabilidad real.

### RNF-005 — Seguridad

Los archivos PHP ejecutables directamente deben protegerse adecuadamente cuando corresponda.

### RNF-006 — Idempotencia

La activación y la preparación de schema deben ser seguras al ejecutarse múltiples veces dentro del lifecycle previsto.

### RNF-007 — Compatibilidad

Las versiones mínimas reales de PHP, WordPress y WooCommerce deben documentarse después de inspeccionar el entorno de desarrollo.

No deben inventarse sin evidencia.

### RNF-008 — Internacionalización

El text domain del plugin será:

`kaanbal`

### RNF-009 — Slug

El slug técnico será:

`kaanbal`

---

## 7. Reglas de Negocio

Esta SPEC no introduce reglas académicas.

Reglas técnicas relevantes:

### RB-001

Desactivar Kaanbal no debe eliminar datos.

### RB-002

La ausencia de WooCommerce no debe impedir cargar la infraestructura base de Kaanbal.

### RB-003

El lifecycle de instalación debe poder ejecutarse de forma idempotente.

---

## 8. Criterios de Aceptación

### AC-001 — Plugin reconocido por WordPress

WordPress reconoce Kaanbal como plugin instalable y muestra su metadata básica en administración.

### AC-002 — Activación exitosa

Dado un entorno compatible, Kaanbal puede activarse sin producir errores fatales.

### AC-003 — Reactivación segura

Activar Kaanbal nuevamente no duplica infraestructura persistente ni genera errores por recursos ya existentes.

### AC-004 — Desactivación segura

Kaanbal puede desactivarse sin errores fatales y sin eliminar datos persistentes.

### AC-005 — Bootstrap funcional

Cuando WordPress carga un Kaanbal activo, el bootstrap principal se inicializa correctamente.

### AC-006 — Autoload PSR-4

Las clases bajo `Kaanbal\` pueden resolverse mediante Composer PSR-4 sin `require_once` manual disperso.

### AC-007 — Acceso directo protegido

Los archivos PHP que lo requieran no deben ejecutar comportamiento sensible cuando se acceden directamente fuera de WordPress.

### AC-008 — WooCommerce ausente

Si WooCommerce está desactivado o no instalado, Kaanbal Foundation sigue cargando sin error fatal.

### AC-009 — Requisitos detectables

Kaanbal dispone de un componente capaz de determinar si el entorno cumple los requisitos mínimos configurados.

### AC-010 — Versionado de plugin

La versión actual de Kaanbal puede consultarse desde una fuente técnica única.

### AC-011 — Versionado de schema

Existe un mecanismo central para conocer la versión instalada del schema de Kaanbal.

### AC-012 — Schema Manager idempotente

El Schema Manager puede ejecutarse repetidamente sin recrear destructivamente recursos existentes ni producir duplicados por diseño.

### AC-013 — Registro de servicios

El bootstrap puede registrar e inicializar servicios de manera explícita y predecible.

### AC-014 — Pruebas automatizadas disponibles

Existe una configuración de testing funcional para ejecutar al menos pruebas base de la infraestructura.

### AC-015 — Quality Gate documentado

Los checks configurados y ejecutables del proyecto pueden ejecutarse mediante comandos documentados.

---

## 9. Casos Límite

### EC-001 — WooCommerce desactivado

Kaanbal Foundation debe permanecer cargable.

### EC-002 — Activación repetida

No debe fallar por tablas, options o recursos previamente creados.

### EC-003 — Composer autoload ausente

La implementación debe manejar este escenario de forma controlada y evitar errores opacos cuando sea razonable.

### EC-004 — Requisito mínimo no cumplido

La incompatibilidad debe detectarse antes de ejecutar funcionalidad que dependa de ese requisito.

### EC-005 — Desactivación y reactivación

La información persistente creada por Kaanbal debe permanecer disponible.

---

## 10. Dependencias

### WordPress

Required.

La versión mínima exacta debe determinarse mediante inspección del entorno y decisión técnica posterior.

### PHP

Required.

La versión mínima exacta debe determinarse mediante inspección.

### Composer

Required para desarrollo y autoloading del proyecto.

### WooCommerce

No requerido para que SPEC-001 cargue.

Será requerido funcionalmente en SPEC posteriores.

---

## 11. Riesgos

### RISK-001 — Overengineering

Crear demasiadas capas o abstracciones desde Foundation puede complicar innecesariamente el plugin.

Mitigación:

Implementar solo infraestructura utilizada o claramente requerida por SPEC próximas.

### RISK-002 — Lifecycle incorrecto

Una activación o migración mal diseñada puede generar inconsistencias futuras.

Mitigación:

Idempotencia y pruebas de activación/reactivación.

### RISK-003 — Acoplamiento temprano con WooCommerce

Introducir clases de WooCommerce directamente en la infraestructura base puede dificultar testing y mantenimiento.

Mitigación:

SPEC-001 no implementará lógica comercial.

### RISK-004 — Tooling incompatible

Las versiones de PHPUnit, PHPCS o PHPStan pueden depender de la versión real de PHP.

Mitigación:

Inspeccionar entorno antes de fijar versiones.

---

## 12. Decisiones Pendientes

Las siguientes decisiones deben resolverse durante inspección/planificación y no deben inventarse:

- versión mínima de PHP
- versión mínima de WordPress
- versión mínima futura de WooCommerce
- versión compatible de PHPUnit
- configuración inicial de PHPCS
- configuración inicial de PHPStan
- estrategia concreta de migrations/schema

Estas decisiones no bloquean la definición de Foundation, pero sí deben resolverse antes de implementar la parte afectada.

---

## 13. Referencias

- `docs/project-context.md`
- `docs/architecture.md`
- `docs/data-model.md`
- `docs/testing-strategy.md`
- `docs/definition-of-done.md`
- `docs/audit-standard.md`