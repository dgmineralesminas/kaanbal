# Plan — SPEC-002 Courses and Curriculum

## 1. Resumen Técnico

SPEC-002 implementará el modelo editorial base de Kaanbal utilizando WordPress para:

- Course
- Module
- Lesson

La estrategia preferida será utilizar Custom Post Types y primitivas nativas de WordPress para relaciones y orden cuando sean suficientes.

No se crearán tablas propias para estas tres entidades.

---

## 2. Componentes Afectados

Estructura conceptual:

```text
src/
└── Courses/
    ├── Domain/
    ├── Application/
    ├── Infrastructure/
    └── Presentation/
```

No es obligatorio crear todas estas carpetas si no existe una responsabilidad concreta.

Componentes previstos:

```text
CoursePostType
ModulePostType
LessonPostType

CourseRepository
ModuleRepository
LessonRepository

CurriculumService

VideoProvider
YouTubeVideoProvider

Admin/
```

Los nombres definitivos pueden simplificarse.

---

## 3. Persistencia

### Course

CPT:

```text
kaanbal_course
```

Campos nativos:

- title
- content
- status
- featured image
- menu order si se requiere

Metadata:

```text
_kaanbal_duration
_kaanbal_instructor_name
```

---

### Module

CPT:

```text
kaanbal_module
```

Relación:

```text
Module → Course
```

Preferencia inicial:

usar metadata explícita:

```text
_kaanbal_course_id
```

y `menu_order` para orden.

Razón:

evitar utilizar `post_parent` para relaciones semánticas entre tipos distintos si la metadata deja más claro el contrato.

Esta decisión puede ajustarse si la implementación demuestra una ventaja clara de `post_parent`.

---

### Lesson

CPT:

```text
kaanbal_lesson
```

Relación:

```text
Lesson → Module
```

Metadata:

```text
_kaanbal_module_id
_kaanbal_video_provider
_kaanbal_video_source
```

Orden:

```text
menu_order
```

---

## 4. Course Metadata

Campos iniciales:

```text
duration
instructor_name
```

Duración será texto administrativo.

Ejemplos:

```text
8 horas
12 horas
6.5 horas
```

No se intentará calcular automáticamente desde YouTube.

---

## 5. CurriculumService

Responsabilidad:

resolver el árbol:

```text
Course
→ ordered Modules
→ ordered Lessons
```

Debe evitar lógica de UI.

Debe proporcionar una estructura suficientemente limpia para futuras:

- vistas admin
- frontend
- progreso
- reportes

---

## 6. Repositories

Repositories encapsularán consultas relacionadas con CPTs cuando la complejidad lo justifique.

Ejemplos:

```text
findModulesByCourse(courseId)
findLessonsByModule(moduleId)
findCourseByLesson(lessonId)
```

No crear repositorios genéricos innecesariamente.

---

## 7. Orden

Se utilizará:

```text
menu_order ASC
```

con orden secundario determinista.

Ejemplo:

```text
ID ASC
```

o:

```text
post_date ASC
```

La implementación debe documentar la decisión.

---

## 8. Video

Se implementará una abstracción mínima de proveedor.

Contrato conceptual:

```php
interface VideoProvider
{
    public function supports(string $source): bool;

    public function normalize(string $source): ?string;
}
```

Implementación:

```text
YouTubeVideoProvider
```

Responsabilidad:

- aceptar formatos YouTube soportados
- obtener/normalizar video ID
- rechazar source inválido

No debe:

- consultar YouTube API
- usar OAuth
- hacer tracking
- validar disponibilidad remota del video

---

## 9. Formatos YouTube

La implementación puede soportar formatos comunes como:

```text
https://www.youtube.com/watch?v=VIDEO_ID
https://youtu.be/VIDEO_ID
VIDEO_ID
```

La lista exacta debe ser cubierta por tests.

---

## 10. Administración

Debe existir una UI mínima dentro de WordPress Admin.

Debe permitir:

### Course

- título
- contenido
- imagen destacada
- duración
- instructor

### Module

- título
- curso
- orden

### Lesson

- título
- contenido
- módulo
- orden
- proveedor de video
- fuente de video

No se requiere Course Builder visual.

---

## 11. Capabilities

Preferencia:

utilizar capabilities específicas o mapear capacidades del CPT de manera coherente.

Debe evitarse depender innecesariamente de:

```text
manage_options
```

para toda operación.

El modelo exacto debe integrarse con la infraestructura de SPEC-001.

---

## 12. Validación de Relaciones

Al guardar Module:

```text
course_id
```

debe:

- existir
- corresponder a `kaanbal_course`

Al guardar Lesson:

```text
module_id
```

debe:

- existir
- corresponder a `kaanbal_module`

La validación debe ocurrir server-side.

---

## 13. Sanitización

Debe sanitizarse:

- IDs
- duration
- instructor name
- provider
- video source

Contenido de WordPress debe seguir las APIs nativas correspondientes.

---

## 14. Presentation

La UI administrativa puede utilizar:

- metaboxes
- campos del editor
- selectores

No introducir dependencias JS grandes.

---

## 15. Testing Unitario

Candidatos:

```text
YouTubeVideoProviderTest
CurriculumOrderingTest
CurriculumRelationTest
```

---

## 16. Testing de Integración

Candidatos:

```text
CoursePostTypeTest
ModulePostTypeTest
LessonPostTypeTest
ModuleCourseRelationshipTest
LessonModuleRelationshipTest
CurriculumQueryTest
AdminCapabilityTest
```

---

## 17. Performance

Para obtener temario:

evitar diseño:

```text
Course
→ query modules
→ por cada module query lessons individualmente
```

cuando pueda resolverse razonablemente con:

- queries agrupadas
- cache nativo de WP
- carga eficiente

No se requiere optimización extrema.

---

## 18. Seguridad

Revisar:

- capability checks
- nonce
- autosaves
- revisions
- bulk actions cuando aplique
- sanitización
- escaping

Guardar metadata no debe confiar en campos enviados desde frontend/admin sin validar.

---

## 19. Flujo de Datos

Administración:

```text
Admin form
    ↓
WordPress boundary
    ↓
Validation / Sanitization
    ↓
Application / repository
    ↓
CPT + metadata
```

Consulta:

```text
Course
    ↓
CurriculumService
    ↓
Modules ordered
    ↓
Lessons ordered
```

---

## 20. Secuencia de Implementación

```text
1. registrar Course CPT
2. metadata Course
3. registrar Module CPT
4. relación Module-Course
5. ordering Module
6. registrar Lesson CPT
7. relación Lesson-Module
8. ordering Lesson
9. video provider abstraction
10. YouTube provider
11. curriculum queries
12. admin UI
13. authorization/security
14. tests
15. quality gate
```

---

## 21. Riesgos Técnicos

### TECH-001

Crear demasiada lógica alrededor de CPTs puede convertir una solución sencilla en una arquitectura pesada.

### TECH-002

WordPress `save_post` puede ejecutarse durante autosave/revisions.

Los handlers deben contemplarlo.

### TECH-003

Queries mal estructuradas pueden generar N+1.

### TECH-004

Parser YouTube excesivamente permisivo puede guardar fuentes inválidas.

---

## 22. Decisiones

### DEC-001

Course, Module y Lesson se modelarán como CPT.

### DEC-002

Ordering utilizará primitivas WordPress, preferiblemente `menu_order`.

### DEC-003

Proveedor inicial de video:

`youtube`.

### DEC-004

No existe tracking de reproducción.

### DEC-005

La lección puede existir sin video.

### DEC-006

No se implementa progreso.

### DEC-007

No se implementa Course Builder avanzado.

---

## 23. Condición de Finalización

SPEC-002 estará lista para auditoría cuando:

- los tres CPT estén disponibles
- relaciones sean válidas
- ordering sea determinista
- metadata se guarde de forma segura
- YouTube source funcione
- temario pueda recuperarse ordenado
- tests obligatorios pasen
- quality gate pase
- no exista funcionalidad fuera de alcance
- `Ready for audit: Yes`