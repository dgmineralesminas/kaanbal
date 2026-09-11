# Arquitectura del Plugin

## 1. Propósito

Este documento define la arquitectura técnica global de Kaanbal.

Su objetivo es establecer una base común para todas las SPEC futuras y evitar decisiones contradictorias entre módulos.

La arquitectura debe favorecer:

- mantenibilidad
- separación de responsabilidades
- bajo acoplamiento
- integración segura con WordPress
- integración desacoplada con WooCommerce
- extensibilidad razonable
- pruebas automatizadas
- evolución incremental

Este documento complementa:

- `docs/project-context.md`
- `docs/agents.md`
- `docs/development-workflow.md`
- `docs/definition-of-done.md`

---

## 2. Principio Arquitectónico General

Kaanbal será un plugin WordPress modular.

No se pretende implementar una arquitectura enterprise excesivamente compleja.

La prioridad será:

```text
simple
modular
testable
maintainable
secure
```

La arquitectura debe separar claramente:

```text
WordPress integration
Domain / application logic
Persistence
Presentation
External integrations
```

---

## 3. Repositorio

Kaanbal vive en un repositorio independiente.

Ubicación esperada en WordPress:

```text
wp-content/plugins/kaanbal/
```

El repositorio contiene únicamente:

- código del plugin
- pruebas
- documentación
- tooling
- migraciones o instaladores propios
- assets propios

No contiene:

- WordPress Core
- WooCommerce Core
- uploads
- plugins de terceros
- themes
- secretos

---

## 4. Archivo Bootstrap

El plugin debe tener un archivo principal mínimo.

Ejemplo:

```text
kaanbal.php
```

Responsabilidades del archivo principal:

- metadata del plugin
- protección contra acceso directo
- carga del autoloader
- bootstrap inicial
- registro de hooks de activación/desactivación
- arranque de la aplicación

No debe contener lógica de negocio.

Ejemplo conceptual:

```php
<?php

/**
 * Plugin Name: Kaanbal
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/vendor/autoload.php';

Kaanbal\Bootstrap\Plugin::boot();
```

La implementación definitiva puede variar, pero el archivo principal debe permanecer pequeño.

---

## 5. Namespace

Namespace raíz:

```php
Kaanbal\
```

Ejemplos:

```php
Kaanbal\Bootstrap
Kaanbal\Courses
Kaanbal\Enrollment
Kaanbal\Progress
Kaanbal\Quiz
Kaanbal\Certificates
Kaanbal\WooCommerce
Kaanbal\Shared
```

Se debe evitar colocar todas las clases directamente bajo:

```php
Kaanbal\
```

---

## 6. Autoloading

Preferencia:

**Composer PSR-4**

Ejemplo conceptual:

```json
{
  "autoload": {
    "psr-4": {
      "Kaanbal\\": "src/"
    }
  }
}
```

Beneficios:

- namespace consistente
- carga automática
- testing sencillo
- tooling estándar
- menor necesidad de `require_once`

Composer será una dependencia de desarrollo/instalación del plugin, no una dependencia de runtime externa remota.

---

## 7. Estructura Base Propuesta

```text
kaanbal/
├── kaanbal.php
├── composer.json
├── composer.lock
├── README.md
├── uninstall.php
│
├── src/
│   ├── Bootstrap/
│   ├── Shared/
│   ├── Courses/
│   ├── Enrollment/
│   ├── Progress/
│   ├── Quiz/
│   ├── Certificates/
│   └── WooCommerce/
│
├── templates/
│   ├── admin/
│   └── frontend/
│
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
│
├── tests/
│   ├── Unit/
│   ├── Integration/
│   └── Support/
│
└── docs/
    ├── agents.md
    ├── development-workflow.md
    ├── definition-of-done.md
    ├── audit-standard.md
    ├── project-context.md
    ├── architecture.md
    ├── data-model.md
    ├── testing-strategy.md
    ├── templates/
    └── specs/
```

---

## 8. Organización por Módulo

Cada módulo funcional puede contener sus propias capas.

Ejemplo:

```text
src/Enrollment/
├── Application/
├── Domain/
├── Infrastructure/
└── Presentation/
```

No es obligatorio crear las cuatro carpetas si una SPEC no lo necesita.

Debe evitarse generar capas vacías únicamente por seguir un patrón.

Principio:

> Crear una abstracción cuando exista una responsabilidad real, no por estética arquitectónica.

---

## 9. Bootstrap

`Kaanbal\Bootstrap` será responsable de:

- inicialización del plugin
- registro de módulos
- activación
- desactivación
- versionado interno
- compatibilidad
- carga de servicios

Ejemplo conceptual:

```text
Bootstrap/
├── Plugin.php
├── Activator.php
├── Deactivator.php
├── Requirements.php
└── ServiceRegistry.php
```

---

## 10. Shared

`Kaanbal\Shared` contendrá únicamente piezas genuinamente compartidas.

Ejemplos posibles:

- Clock
- UUID/identifier helpers
- database abstractions
- result objects
- exceptions base
- validation helpers
- pagination
- shared WordPress adapters

Debe evitar convertirse en una carpeta genérica tipo:

```text
Helpers/
Utils/
Misc/
```

donde termine acumulándose lógica sin dueño.

---

## 11. Courses

Responsabilidad:

- cursos
- módulos
- lecciones
- temario
- orden
- metadata educativa
- navegación

No debe gestionar:

- pagos
- pedidos
- matrículas
- certificados
- lógica de progreso transaccional

---

## 12. Enrollment

Responsabilidad:

- matrícula de alumno a curso
- acceso académico
- estado de matrícula
- origen del acceso
- idempotencia
- revocación cuando corresponda

La matrícula representa:

```text
User
↔
Course
```

No:

```text
User
↔
WooCommerce Product
```

---

## 13. Progress

Responsabilidad:

- marcar lecciones completadas
- consultar progreso
- calcular porcentaje
- determinar contenido completado

No debe decidir por sí mismo si un curso está académicamente finalizado cuando exista un quiz pendiente.

La finalización global debe coordinarse mediante un servicio de aplicación.

---

## 14. Quiz

Responsabilidad:

- definición de quiz
- preguntas
- opciones
- intentos
- puntuación
- aprobación/reprobación

No debe confiar en puntuaciones enviadas desde frontend.

La evaluación debe realizarse en backend.

---

## 15. Certificates

Responsabilidad:

- emisión de certificados
- folios
- metadata de certificado
- generación de documento
- validación pública futura

Un certificado solo debe emitirse cuando la aplicación confirme que se cumplen las condiciones de finalización.

---

## 16. WooCommerce

`Kaanbal\WooCommerce` será una capa de integración.

Responsabilidad:

- escuchar eventos de WooCommerce
- interpretar productos comprados
- resolver cursos asociados
- traducir eventos comerciales a operaciones de Kaanbal

WooCommerce no debe filtrarse innecesariamente hacia el dominio.

Ejemplo conceptual:

```text
WooCommerce Hook
    ↓
WooCommerce Adapter / Listener
    ↓
Enrollment Application Service
    ↓
Domain / Persistence
```

Evitar:

```text
EnrollmentService
    ↓
WC_Order
    ↓
WC_Product
    ↓
global WooCommerce state
```

en todo el dominio.

---

## 17. Adapter Boundary

La integración con WooCommerce debe encapsularse.

Ejemplo conceptual:

```php
interface CommerceOrder
{
    public function getCustomerId(): int;

    public function getPurchasedProductIds(): array;
}
```

Adapter:

```php
final class WooCommerceOrderAdapter implements CommerceOrder
{
    // traduce WC_Order al contrato interno
}
```

No es obligatorio usar exactamente esta interfaz.

La regla es:

> La lógica educativa no debe depender innecesariamente de clases concretas de WooCommerce.

---

## 18. Application Services

Los casos de uso deben vivir en servicios de aplicación cuando exista lógica coordinada.

Ejemplos:

```text
EnrollStudentInCourse
CompleteLesson
SubmitQuizAttempt
CompleteCourse
IssueCertificate
```

Ejemplo conceptual:

```php
final class CompleteLesson
{
    public function execute(
        int $userId,
        int $lessonId
    ): void {
        // coordina validación y persistencia
    }
}
```

---

## 19. Domain Services

Usar domain services solo cuando exista lógica de dominio que no pertenezca naturalmente a una entidad.

Ejemplo futuro:

```text
CourseCompletionPolicy
```

Podría responder:

```text
¿Todas las lecciones están completas?
¿El quiz está aprobado?
```

No debe crearse un servicio de dominio para cada operación trivial.

---

## 20. Repositories

Los repositories son apropiados para datos transaccionales propios.

Ejemplos:

```php
EnrollmentRepository
ProgressRepository
QuizAttemptRepository
CertificateRepository
```

Responsabilidad:

- lectura
- escritura
- queries específicas
- encapsulación de persistencia

No deben contener reglas de negocio complejas.

---

## 21. Persistencia WordPress

Para contenido editorial se pueden usar APIs nativas.

Ejemplos:

- Custom Post Types
- post meta
- taxonomías

Estas decisiones se detallarán en:

`docs/data-model.md`

---

## 22. Tablas Propias

Los datos transaccionales deben preferir tablas propias cuando:

- crecen rápidamente
- requieren índices específicos
- necesitan uniqueness
- requieren queries frecuentes
- no representan contenido editorial

Candidatos:

```text
enrollments
progress
quiz attempts
certificates
```

---

## 23. Acceso a Base de Datos

Cuando se utilicen tablas propias:

- usar `$wpdb`
- usar prepared statements cuando exista input variable
- evitar concatenación SQL insegura
- centralizar queries en repositories
- definir índices explícitos
- evitar N+1

No es necesario introducir un ORM externo.

---

## 24. Schema Management

La creación y actualización de tablas debe estar centralizada.

Ejemplo conceptual:

```text
src/Bootstrap/Database/
├── SchemaManager.php
└── Migrations/
```

Opciones válidas:

- `dbDelta`
- migraciones internas versionadas

La estrategia definitiva debe documentarse antes de implementar tablas complejas.

---

## 25. Versionado del Schema

Kaanbal debe mantener una versión interna de schema.

Ejemplo conceptual:

```text
kaanbal_db_version
```

Esto permite:

```text
plugin update
    ↓
compare version
    ↓
run required migrations
```

No debe recrearse todo el schema destructivamente en cada request.

---

## 26. Activación

Durante activación pueden realizarse tareas como:

- validar requisitos mínimos
- crear tablas requeridas
- registrar capabilities base
- guardar versión
- flush rewrite rules cuando aplique

Debe evitarse hacer operaciones pesadas innecesarias.

---

## 27. Desactivación

Desactivar el plugin no debe eliminar datos.

Puede:

- limpiar cron hooks
- liberar recursos temporales
- flush rewrite rules cuando corresponda

Pero no debe eliminar:

- cursos
- matrículas
- progreso
- quizzes
- certificados

---

## 28. Uninstall

La eliminación permanente debe tratarse separadamente.

`uninstall.php` puede encargarse de borrar datos si existe una política explícita.

Por defecto, la decisión de eliminar datos al desinstalar debe ser conservadora.

No debe implementarse destrucción automática sin una decisión funcional explícita.

---

## 29. Presentation Layer

La presentación puede incluir:

```text
Admin
Frontend
REST
AJAX
Shortcodes
Blocks
```

solo cuando una SPEC los necesite.

No deben implementarse todos desde el inicio.

---

## 30. Admin

La UI de administración será responsable de:

- formularios
- listados
- selección de relaciones
- configuración

No debe contener reglas de negocio.

Ejemplo:

```text
Admin Controller
    ↓
Application Service
    ↓
Repository
```

---

## 31. Frontend

El frontend será responsable de:

- mostrar cursos
- mostrar temario
- mostrar video
- permitir marcar lección completa
- mostrar progreso
- quizzes
- certificados

La autorización siempre se valida en backend.

---

## 32. Templates

Los templates deben evitar lógica compleja.

Idealmente reciben View Models o estructuras preparadas.

Evitar:

```php
<?php
// query directa
// lógica de permisos
// cálculo de progreso
// cambios de estado
?>
```

dentro del template.

---

## 33. REST / AJAX

Cuando se requieran operaciones interactivas:

Ejemplos:

```text
Marcar lección completada
Enviar quiz
Consultar progreso
```

pueden implementarse con:

- WordPress REST API
- AJAX tradicional

La elección se hará por SPEC.

Toda operación mutante debe validar:

- autenticación
- autorización
- ownership
- nonce/token cuando corresponda
- datos de entrada

---

## 34. Authorization

La autorización debe estar separada de la UI.

Ejemplo:

```text
CanViewCourse
CanCompleteLesson
CanTakeQuiz
CanManageCourse
```

No necesariamente como clases separadas desde el inicio.

Pero las reglas deben ser verificables en backend.

---

## 35. Capabilities

Kaanbal debe preferir capabilities sobre checks rígidos de roles.

Ejemplo conceptual:

```text
kaanbal_manage_courses
kaanbal_manage_students
kaanbal_manage_quizzes
kaanbal_view_reports
```

Esto permite integrar mejor con WordPress.

Las capabilities definitivas se definirán en SPEC correspondiente.

---

## 36. Usuario Alumno

No es obligatorio crear un rol WordPress exclusivo `student` en la primera versión.

Puede utilizarse el usuario WordPress existente.

El acceso académico se determina principalmente por matrícula.

Si posteriormente se requiere un rol específico, deberá definirse explícitamente.

---

## 37. Hooks Internos

Kaanbal puede exponer actions/filters propios.

Ejemplos conceptuales:

```php
do_action( 'kaanbal_student_enrolled', $userId, $courseId );

do_action( 'kaanbal_lesson_completed', $userId, $lessonId );

do_action( 'kaanbal_course_completed', $userId, $courseId );
```

No deben considerarse API pública estable hasta formalización.

---

## 38. Eventos Internos

Cuando un caso de uso tenga efectos secundarios, se puede utilizar un evento interno o action de WordPress.

Ejemplo:

```text
LessonCompleted
    ↓
recalculate progress
    ↓
evaluate course completion
```

Debe evitarse crear cadenas de hooks difíciles de seguir.

La lógica crítica debe seguir siendo explícita.

---

## 39. Finalización del Curso

La finalización del curso debe coordinarse en una capa de aplicación.

Conceptualmente:

```text
Complete lesson
    ↓
Progress updated
    ↓
Check course completion
        ↓
all lessons complete?
        ↓
quiz passed?
        ↓
mark course completed
        ↓
issue certificate
```

Debe ser idempotente.

Ejecutar el proceso varias veces no debe generar múltiples certificados válidos por accidente.

---

## 40. Idempotencia

Debe considerarse especialmente en:

- matrícula
- completar lección
- completar curso
- emitir certificado
- procesar eventos WooCommerce

Ejemplo:

```text
mismo evento WooCommerce
procesado dos veces
```

no debe producir:

```text
dos matrículas activas
```

---

## 41. Transacciones

Cuando una operación afecte múltiples tablas relacionadas y exista riesgo de inconsistencia, evaluar uso de transacciones.

Ejemplos futuros:

```text
quiz attempt
→ final score
→ completion
→ certificate
```

No usar transacciones indiscriminadamente.

Aplicarlas cuando exista una unidad lógica que requiera atomicidad.

---

## 42. Concurrencia

La arquitectura debe evitar asumir que una request es la única operación activa.

Ejemplos:

- doble click
- reintento HTTP
- webhook repetido
- hook disparado múltiples veces
- procesamiento concurrente

Unique constraints e idempotencia deben apoyar estas situaciones.

---

## 43. Caching

No se implementará caching complejo inicialmente.

Puede añadirse para:

- catálogo
- estadísticas
- queries costosas
- conteos agregados

solo cuando exista necesidad demostrada.

No debe cachearse estado sensible sin estrategia clara de invalidación.

---

## 44. Logging

La arquitectura debe permitir logging técnico.

Ejemplos útiles:

- error de matrícula
- evento WooCommerce no procesable
- fallo de emisión de certificado
- migración fallida

No deben registrarse secretos ni información innecesariamente sensible.

---

## 45. Error Handling

Evitar:

```php
die();
exit();
wp_die();
```

en lógica interna salvo capa de presentación apropiada.

Preferir:

- exceptions de dominio/aplicación
- Result objects cuando sea útil
- errores WordPress en boundaries

Ejemplo:

```text
Domain Exception
    ↓
Controller catches
    ↓
WP_Error / response
```

---

## 46. Excepciones

Ejemplos conceptuales:

```text
EnrollmentNotFound
CourseAccessDenied
LessonNotFound
QuizAttemptNotAllowed
CertificateAlreadyIssued
```

No es obligatorio crear jerarquías excesivas.

---

## 47. Testing

La arquitectura debe facilitar:

- unit tests
- integration tests
- WordPress tests
- WooCommerce integration tests

La lógica importante debe poder probarse sin depender exclusivamente de navegar manualmente WordPress.

---

## 48. Dependencias Externas

Evitar dependencias innecesarias.

Una librería externa se justifica cuando:

- resuelve un problema real
- reduce riesgo
- tiene mantenimiento razonable
- evita reinventar funcionalidad compleja

Ejemplo posible:

```text
PDF generation
```

puede justificar una librería.

No debe añadirse una dependencia para resolver algo trivial.

---

## 49. Frontend JavaScript

JavaScript se utilizará donde aporte valor.

Ejemplos:

- marcar lección completa
- actualizar progreso sin reload
- quiz interactivo
- drag & drop de temario

No se requiere SPA.

WordPress + server rendering + JavaScript progresivo es suficiente inicialmente.

---

## 50. YouTube

La integración inicial con YouTube debe mantenerse simple.

Una lección podrá almacenar:

```text
provider = youtube
video_id / URL
```

No se requiere:

- OAuth de YouTube
- tracking exacto del player
- porcentaje reproducido
- reanudación de segundo exacto

---

## 51. Video Provider Interface

Puede existir una abstracción ligera.

Ejemplo conceptual:

```php
interface VideoProvider
{
    public function supports( string $source ): bool;

    public function getEmbedUrl( string $source ): string;
}
```

Implementación inicial:

```text
YouTubeVideoProvider
```

No deben implementarse proveedores futuros hasta que exista requisito.

---

## 52. Protección de Video

Kaanbal controla acceso a la página y experiencia educativa.

No ofrece DRM.

No debe prometer:

- imposibilidad de descargar
- imposibilidad de copiar
- protección absoluta del video

---

## 53. Dependencia del Theme

Kaanbal no debe depender del theme activo para funcionar.

El theme puede sobrescribir templates si en el futuro se diseña un mecanismo explícito.

Pero la funcionalidad base debe existir independientemente.

---

## 54. Extensibilidad de Templates

Puede considerarse posteriormente un sistema tipo:

```text
theme/
└── kaanbal/
    └── course.php
```

para overrides.

Esto es una consideración futura.

No forma parte obligatoria de la infraestructura inicial salvo que una SPEC lo apruebe.

---

## 55. Compatibilidad

La versión mínima de:

- PHP
- WordPress
- WooCommerce

debe definirse tras inspeccionar el entorno real.

No debe suponerse arbitrariamente.

La SPEC-001 deberá registrar estas versiones.

---

## 56. WordPress Coding Standards

Kaanbal debe seguir WordPress Coding Standards cuando sea razonable.

Esto incluye:

- escaping
- sanitization
- naming compatible
- internacionalización
- prepared queries
- APIs nativas

Sin embargo, el namespace y organización interna pueden seguir prácticas PHP modernas compatibles con WordPress.

---

## 57. Internacionalización

Todos los textos visibles deben poder internacionalizarse.

Text domain:

```text
kaanbal
```

No hardcodear textos visibles sin mecanismos de traducción cuando corresponda.

---

## 58. Seguridad por Diseño

La seguridad debe formar parte de la arquitectura.

Principios:

```text
frontend is untrusted
authorization is server-side
input is validated
output is escaped
queries are prepared
ownership is verified
```

---

## 59. Principle of Least Privilege

Cada operación debe requerir únicamente el permiso necesario.

Ejemplo:

```text
editar un curso
```

no debe requerir necesariamente:

```text
manage_options
```

si existe una capability específica más apropiada.

---

## 60. Datos Derivados

Evitar persistir datos que puedan derivarse fácilmente si esto crea inconsistencias.

Ejemplo:

```text
course progress %
```

puede calcularse desde:

```text
completed lessons / total lessons
```

Se podrá cachear posteriormente si existe necesidad.

---

## 61. Datos Históricos

No todos los datos deben derivarse.

Ejemplos que sí requieren persistencia histórica:

- intentos de quiz
- fecha de matrícula
- fecha de finalización
- certificado emitido
- origen comercial

---

## 62. Estados

Los estados deben modelarse explícitamente cuando aporten valor.

Ejemplo:

```text
Enrollment:
active
completed
revoked
```

Pero no se debe crear una state machine compleja sin necesidad.

---

## 63. Compatibilidad con Futuras Integraciones

La arquitectura debe permitir, sin implementarlas ahora:

- Vimeo
- Bunny Stream
- Cloudflare Stream
- Mux
- otros gateways comerciales
- API externa
- integraciones empresariales

Esto es una restricción de no-acoplamiento, no un requisito de implementación.

---

## 64. Anti-Patterns a Evitar

Evitar:

```text
God classes
Static global state everywhere
Business logic in templates
SQL scattered across controllers
Direct WooCommerce dependency everywhere
Duplicate authorization logic
Post meta for high-volume transactional history
Frontend-only security
Silent failures
Unbounded N+1 queries
```

---

## 65. Regla de Simplicidad

No implementar patrones por anticipación.

Ejemplo:

No crear:

```text
AbstractCourseRepositoryFactoryInterfaceManager
```

cuando:

```text
CourseRepository
```

es suficiente.

La arquitectura debe crecer junto con los requisitos.

---

## 66. Decisiones Protegidas

Las decisiones globales de este documento se consideran arquitectura aprobada.

Una SPEC puede concretarlas.

No debe contradecirlas silenciosamente.

Si una SPEC requiere modificar una decisión global, debe generar un `Change Request`.

---

## 67. Principio Final

La arquitectura de Kaanbal debe mantener este flujo comprensible:

```text
Request / WordPress Hook
        ↓
Presentation / Integration Boundary
        ↓
Application Service
        ↓
Domain Rule
        ↓
Repository
        ↓
Persistence
```

y para integraciones externas:

```text
External System
        ↓
Adapter
        ↓
Kaanbal Application
```

La regla final es:

> WordPress hospeda el plugin, WooCommerce vende, Kaanbal administra el aprendizaje y cada capa debe conocer únicamente lo necesario para cumplir su responsabilidad.