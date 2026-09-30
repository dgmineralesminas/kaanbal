# SPEC-009 — Video Providers

Status: Ready for implementation

## 1. Objetivo

Extender la infraestructura de video de Kaanbal para soportar múltiples proveedores de video mediante una interfaz común.

Los proveedores soportados por esta SPEC serán:

- YouTube
- Vimeo

La implementación debe permitir que una lección utilice cualquiera de estos proveedores sin acoplar la lógica curricular o el player directamente a uno de ellos.

---

## 2. Contexto

SPEC-002 introdujo inicialmente:

`YouTubeVideoProvider`

SPEC-004 utiliza esa información para renderizar el video dentro de una lección.

La arquitectura debe evolucionar de:

```text
Lesson
→ YouTube
```

a:

```text
Lesson
      ↓
Video Provider
      ↓
┌─────────┬─────────┐
│ YouTube │ Vimeo   │
└─────────┴─────────┘
```

Esto permitirá agregar proveedores futuros sin modificar la lógica central de Course/Lesson.

---

## 3. Alcance

Esta SPEC incluye:

- contrato común para proveedores de video
- registro/resolución de proveedores
- YouTube
- Vimeo
- detección/validación de fuentes
- normalización de identificadores
- generación segura de embed
- selección administrativa del proveedor
- actualización de Lesson View
- compatibilidad con las lecciones YouTube existentes
- manejo de proveedor no soportado
- manejo de source inválida
- tests para ambos proveedores

---

## 4. Fuera de Alcance

Esta SPEC NO incluye:

- subir videos a WordPress
- almacenar archivos MP4
- Vimeo API
- YouTube API
- OAuth
- API keys
- videos privados mediante tokens
- estadísticas de reproducción
- porcentaje visto
- tiempo reproducido
- posición del video
- resume playback
- autoplay obligatorio
- DRM
- Bunny Stream
- Mux
- Cloudflare Stream
- Wistia
- AWS MediaConvert
- streaming propio
- transcodificación
- subtítulos administrados por Kaanbal
- control de calidad de video
- descarga de videos

---

# 5. Requisitos Funcionales

## RF-001 — Contrato de proveedor

Kaanbal debe disponer de una abstracción común para trabajar con proveedores de video.

---

## RF-002 — YouTube

Kaanbal debe continuar soportando YouTube.

---

## RF-003 — Vimeo

Kaanbal debe soportar Vimeo.

---

## RF-004 — Selección del proveedor

Una lección debe poder seleccionar:

- YouTube
- Vimeo
- Sin video

---

## RF-005 — Fuente YouTube

La implementación debe reconocer formatos de YouTube soportados.

Como mínimo:

```text
https://www.youtube.com/watch?v=VIDEO_ID
https://youtu.be/VIDEO_ID
VIDEO_ID
```

si el soporte actual ya permite ID directo.

---

## RF-006 — Fuente Vimeo

La implementación debe reconocer URLs Vimeo públicas estándar.

Como mínimo:

```text
https://vimeo.com/123456789
https://www.vimeo.com/123456789
```

Puede soportar formatos adicionales si se documentan y prueban.

---

## RF-007 — Normalización YouTube

Una fuente válida de YouTube debe normalizarse a un identificador estable.

---

## RF-008 — Normalización Vimeo

Una fuente válida de Vimeo debe normalizarse a un identificador estable.

Ejemplo:

```text
123456789
```

---

## RF-009 — Fuente inválida

Una source inválida no debe aceptarse silenciosamente como video válido.

---

## RF-010 — Proveedor desconocido

Un provider no registrado debe considerarse no soportado.

---

## RF-011 — Embed YouTube

Kaanbal debe generar un embed seguro de YouTube usando información normalizada.

---

## RF-012 — Embed Vimeo

Kaanbal debe generar un embed seguro de Vimeo usando información normalizada.

---

## RF-013 — Lección sin video

Una lección puede existir sin proveedor ni source.

---

## RF-014 — Cambio de proveedor

Un administrador puede cambiar una lección de:

```text
YouTube
→ Vimeo
```

o viceversa.

La source debe validarse contra el nuevo proveedor.

---

## RF-015 — Compatibilidad existente

Las lecciones YouTube existentes deben continuar funcionando después de implementar SPEC-009.

---

## RF-016 — Provider Registry

Kaanbal debe poder resolver una implementación de video por identificador de provider.

Ejemplo conceptual:

```text
youtube → YouTubeVideoProvider
vimeo   → VimeoVideoProvider
```

---

# 6. Requisitos No Funcionales

## RNF-001 — Desacoplamiento

Course, Module y Curriculum no deben conocer detalles específicos de YouTube o Vimeo.

---

## RNF-002 — Sin APIs externas

Validar y renderizar una fuente no debe depender de realizar requests HTTP a YouTube o Vimeo.

---

## RNF-003 — Seguridad

No debe renderizarse HTML arbitrario almacenado como source.

---

## RNF-004 — URL controlada

El embed debe construirse internamente a partir del identificador normalizado.

---

## RNF-005 — Extensibilidad proporcional

Agregar un proveedor futuro debe requerir una implementación concreta y registro, pero no reescribir Course/Lesson.

---

## RNF-006 — No overengineering

No crear una plataforma multimedia compleja.

La abstracción debe ser mínima.

---

## RNF-007 — Compatibilidad

La implementación debe respetar las interfaces y datos existentes cuando sea razonablemente posible.

---

# 7. Reglas de Negocio

## RB-001

Los providers válidos para esta SPEC son:

```text
youtube
vimeo
```

---

## RB-002

Una lección puede no tener video.

---

## RB-003

Si existe provider, su source debe ser válida para ese provider.

---

## RB-004

Una URL YouTube no debe considerarse válida para Vimeo.

---

## RB-005

Una URL Vimeo no debe considerarse válida para YouTube.

---

## RB-006

El proveedor de video no influye en:

- Enrollment
- Progress
- Quiz
- Course Completion

---

## RB-007

Ver o reproducir un video no completa automáticamente la lección.

---

# 8. Criterios de Aceptación

## AC-001 — Resolver YouTube Provider

El registry puede resolver el provider:

`youtube`

---

## AC-002 — Resolver Vimeo Provider

El registry puede resolver:

`vimeo`

---

## AC-003 — Rechazar provider desconocido

Un identificador no registrado no produce un provider válido.

---

## AC-004 — Normalizar YouTube URL estándar

Una URL válida `youtube.com/watch` produce el identificador correcto.

---

## AC-005 — Normalizar YouTube short URL

Una URL válida `youtu.be` produce el identificador correcto.

---

## AC-006 — Normalizar Vimeo

Una URL Vimeo soportada produce el video ID correcto.

---

## AC-007 — Rechazar YouTube inválido

Una source inválida no genera embed.

---

## AC-008 — Rechazar Vimeo inválido

Una source inválida no genera embed.

---

## AC-009 — Renderizar YouTube

Una lección YouTube válida genera un iframe seguro.

---

## AC-010 — Renderizar Vimeo

Una lección Vimeo válida genera un iframe seguro.

---

## AC-011 — Sin HTML arbitrario

Una source que contenga HTML/JavaScript no debe renderizarse directamente.

---

## AC-012 — Seleccionar Vimeo en Admin

Un administrador puede configurar Vimeo como provider de una lección.

---

## AC-013 — Seleccionar YouTube en Admin

Un administrador puede configurar YouTube como provider.

---

## AC-014 — Lección sin video

Seleccionar "Sin video" continúa siendo válido.

---

## AC-015 — Cambiar provider

Al cambiar de proveedor, la nueva source debe validarse usando el provider seleccionado.

---

## AC-016 — Compatibilidad YouTube

Las lecciones YouTube existentes continúan renderizándose correctamente.

---

## AC-017 — Player independiente

Lesson View utiliza el sistema de providers y no contiene branches específicos dispersos de YouTube/Vimeo.

---

## AC-018 — No modificar progreso

Cargar cualquiera de los dos players no registra completion ni progreso.

---

# 9. Casos Límite

## EC-001 — Vimeo URL con slash final

Ejemplo:

```text
https://vimeo.com/123456789/
```

Puede aceptarse si el parser la reconoce correctamente.

---

## EC-002 — Vimeo URL con query string

La implementación puede ignorar parámetros irrelevantes cuando pueda obtener con seguridad el ID.

---

## EC-003 — URL de otro dominio con número Vimeo

No debe aceptarse únicamente porque contiene un número.

---

## EC-004 — YouTube URL maliciosa

No debe generar HTML arbitrario.

---

## EC-005 — Provider vacío con source antigua

Debe manejarse de forma segura.

---

## EC-006 — Provider Vimeo con YouTube URL

Debe rechazarse.

---

## EC-007 — Provider YouTube con Vimeo URL

Debe rechazarse.

---

## EC-008 — Video ID Vimeo no numérico

Debe rechazarse para formatos soportados que requieran ID numérico.

---

# 10. Dependencias

## SPEC-001

Required.

## SPEC-002

Required.

Introduce el modelo de video en Lesson.

## SPEC-004

Required.

Contiene Lesson View/player.

## SPEC-005+

No son dependencias funcionales directas.

Video Providers no debe alterar progreso ni completion.

---

# 11. Riesgos

## RISK-001 — Parsing permisivo

Una regex excesivamente amplia podría aceptar URLs maliciosas.

Mitigación:

validar host + estructura.

---

## RISK-002 — HTML injection

Guardar/embedear markup proporcionado directamente podría introducir XSS.

Mitigación:

normalizar ID y construir embed internamente.

---

## RISK-003 — Acoplamiento en template

Añadir:

```text
if youtube...
else if vimeo...
```

por múltiples lugares degradaría arquitectura.

Mitigación:

Provider Registry + Renderer.

---

## RISK-004 — Scope creep

Integrar Vimeo API o videos privados convertiría esta SPEC en una integración externa mucho mayor.

Mitigación:

solo URLs/embed público estándar.

---

# 12. Decisiones Pendientes

El plan puede concretar:

- nombre exacto de la interfaz
- ubicación del Provider Registry
- estructura del resultado de normalize()
- atributos comunes del iframe
- dominio Vimeo embed utilizado
- soporte o no para URLs Vimeo adicionales

No debe introducir llamadas a APIs externas.

---

# 13. Referencias

- `docs/project-context.md`
- `docs/architecture.md`
- `docs/testing-strategy.md`
- `docs/specs/002-courses-and-curriculum/`
- `docs/specs/004-course-access-and-player/`