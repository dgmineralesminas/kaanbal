# Plan — SPEC-009 Video Providers

## 1. Resumen Técnico

SPEC-009 generalizará la integración existente de YouTube para utilizar una abstracción de provider.

Arquitectura conceptual:

```text
Lesson
  ↓
VideoProviderRegistry
  ↓
VideoProvider
  ├── YouTubeVideoProvider
  └── VimeoVideoProvider
```

Presentation utilizará:

```text
provider + normalized source
→ embed renderer
```

---

# 2. Componentes previstos

Posible estructura:

```text
src/
└── Courses/
    └── Video/
        ├── VideoProvider.php
        ├── VideoProviderRegistry.php
        ├── YouTubeVideoProvider.php
        ├── VimeoVideoProvider.php
        └── VideoEmbed.php
```

La ubicación puede adaptarse a la estructura real creada durante SPEC anteriores.

---

# 3. VideoProvider

Contrato conceptual:

```php
interface VideoProvider
{
    public function getName(): string;

    public function normalize(string $source): ?string;

    public function getEmbedUrl(string $videoId): ?string;
}
```

No es obligatorio utilizar exactamente estas firmas.

La interfaz debe mantenerse pequeña.

---

# 4. Responsabilidad de Normalize

Input:

```text
URL / source
```

Output:

```text
normalized video ID
```

o:

```text
null
```

si no es válida.

---

# 5. YouTube Provider

Debe conservar compatibilidad con las fuentes soportadas actualmente.

Ejemplos:

```text
youtube.com/watch?v=abc123
youtu.be/abc123
abc123
```

Solo soportar ID directo si ya forma parte del contrato existente o se aprueba explícitamente.

---

# 6. Vimeo Provider

Debe soportar inicialmente URLs públicas estándar:

```text
https://vimeo.com/123456789
https://www.vimeo.com/123456789
```

Normalización:

```text
123456789
```

---

# 7. Validación de Host

YouTube:

hosts permitidos conceptualmente:

```text
youtube.com
www.youtube.com
youtu.be
```

Vimeo:

```text
vimeo.com
www.vimeo.com
```

No utilizar únicamente búsqueda textual del dominio.

---

# 8. Embed YouTube

Construir internamente:

```text
https://www.youtube.com/embed/{videoId}
```

La URL real debe escaparse al renderizar.

---

# 9. Embed Vimeo

Construir internamente:

```text
https://player.vimeo.com/video/{videoId}
```

No permitir que la metadata determine libremente el dominio embed.

---

# 10. Provider Registry

Responsabilidad:

```text
get("youtube")
get("vimeo")
supports("...")
```

No utilizar `switch` de providers por toda la aplicación.

---

# 11. Registration

Los providers pueden registrarse durante bootstrap/service registration.

Ejemplo conceptual:

```text
VideoProviderRegistry
    ↓
YouTubeVideoProvider
VimeoVideoProvider
```

---

# 12. Lesson Metadata

Se conservan los campos conceptuales:

```text
_kaanbal_video_provider
_kaanbal_video_source
```

Provider:

```text
youtube
vimeo
empty
```

---

# 13. Persistencia de Source

Preferencia:

almacenar la source normalizada cuando sea coherente con la implementación existente.

Ejemplo:

```text
youtube / abc123
vimeo   / 123456789
```

Esto evita tener que volver a parsear URLs en cada request.

Si la versión actual almacena URL original y cambiarlo implica migración innecesaria, puede mantenerse el formato existente y normalizarse en boundary.

La decisión debe documentarse.

---

# 14. Compatibilidad

Antes de modificar almacenamiento, inspeccionar cómo SPEC-002 implementó realmente YouTube.

No romper contenido existente.

Si se requiere migración:

debe ser explícita, idempotente y probada.

---

# 15. Admin UI

Lesson Admin debe permitir seleccionar:

```text
Sin video
YouTube
Vimeo
```

Después:

```text
URL / fuente del video
```

---

# 16. Validación Admin

Al guardar:

```text
provider = vimeo
source = youtube URL
```

debe rechazarse como combinación inválida.

---

# 17. Presentation

Lesson View no debe conocer reglas de parsing.

Debe recibir una representación válida desde la capa correspondiente.

---

# 18. Embed Renderer

Puede existir un renderer común encargado de producir:

```html
<iframe ...></iframe>
```

a partir de una URL segura ya construida.

La capa provider no necesita necesariamente producir HTML.

Preferencia:

```text
Provider
→ embed URL

Renderer
→ HTML
```

para separar validación de presentation.

---

# 19. Atributos del iframe

Configurar de forma consistente atributos razonables como:

```text
loading="lazy"
allowfullscreen
```

y permisos necesarios para reproducción.

No añadir permisos excesivos sin justificación.

---

# 20. Privacy-enhanced YouTube

Puede considerarse:

```text
youtube-nocookie.com
```

como variante futura/configurable.

No es requisito obligatorio de esta SPEC.

---

# 21. Vimeo Privacy

Esta SPEC soporta embeds estándar públicos.

Videos con restricciones avanzadas, private links o autenticación están fuera de alcance.

---

# 22. Testing Unitario

Candidatos:

```text
VideoProviderRegistryTest
YouTubeVideoProviderTest
VimeoVideoProviderTest
VideoEmbedRendererTest
```

---

# 23. YouTube Tests

Casos mínimos:

```text
youtube watch URL
youtu.be URL
supported raw ID if applicable
invalid URL
foreign domain
HTML payload
```

---

# 24. Vimeo Tests

Casos mínimos:

```text
https://vimeo.com/123456789
https://www.vimeo.com/123456789
trailing slash
query string when supported
non-numeric ID
foreign domain
YouTube URL
HTML payload
```

---

# 25. Registry Tests

```text
youtube → provider
vimeo → provider
unknown → unsupported
```

---

# 26. Integration Tests

Candidatos:

```text
YouTubeLessonRenderTest
VimeoLessonRenderTest
LessonWithoutVideoTest
UnsupportedVideoProviderTest
ExistingYouTubeCompatibilityTest
```

---

# 27. Security Tests

Obligatorios:

```text
javascript payload as source
iframe payload as source
malicious URL
foreign host pretending to be Vimeo
foreign host pretending to be YouTube
```

Ninguno debe provocar render arbitrario.

---

# 28. Progress Regression

Abrir:

- YouTube lesson
- Vimeo lesson

no debe generar registros de progress.

---

# 29. Course Completion Regression

El provider no debe cambiar la política de Course Completion.

---

# 30. Scope Protection

No introducir:

- API requests
- OAuth
- Vimeo tokens
- YouTube tokens
- playback tracking
- player analytics
- video uploads

---

# 31. Secuencia de Implementación

```text
1. inspect current YouTube implementation
2. define VideoProvider contract
3. implement Provider Registry
4. adapt YouTube provider
5. implement Vimeo provider
6. implement common renderer
7. update Lesson Admin
8. validate provider/source
9. update Lesson View
10. migration if required
11. compatibility tests
12. security tests
13. regression tests
14. quality gate
```

---

# 32. Riesgos Técnicos

## TECH-001

Romper las lecciones YouTube existentes.

Mitigación:

compatibilidad/migración probada.

## TECH-002

Parsers de URLs demasiado permisivos.

Mitigación:

URI parsing y allowlist de hosts.

## TECH-003

Registry excesivamente complejo.

Mitigación:

registro explícito pequeño.

---

# 33. Decisiones

## DEC-001

Providers iniciales:

```text
youtube
vimeo
```

## DEC-002

No se utilizarán APIs externas.

## DEC-003

No existe tracking de reproducción.

## DEC-004

No se alojarán videos en WordPress.

## DEC-005

El provider solo afecta presentación del video.

## DEC-006

La lógica académica permanece independiente del provider.

---

# 34. Condición de Finalización

SPEC-009 estará lista para auditoría cuando:

- YouTube siga funcionando
- Vimeo funcione
- ambos utilicen el mismo contrato
- registry funcione
- provider desconocido sea rechazado
- sources inválidas sean rechazadas
- embeds sean seguros
- admin permita YouTube/Vimeo
- lección sin video siga funcionando
- no se rompa progreso
- no se introduzca tracking
- tests pasen
- Quality Gate pase
- `Ready for audit: Yes`