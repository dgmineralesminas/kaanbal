# Architecture Audit — SPEC-009 Video Providers

## Audit Information

- **Auditor:** Qwen (Architecture & Maintainability)
- **Round:** 1
- **SPEC:** SPEC-009 — Video Providers
- **Commit audited:** `b3d315f`
- **Branch:** `feature/spec-009-video-providers`
- **Date:** 2026-09-30

---

## Verdict

**PASS**

La implementación respeta la arquitectura aprobada y mantiene excelentes características de mantenibilidad. La separación de responsabilidades es clara, el diseño sigue el principio de extensibilidad proporcional (RNF-005, RNF-006), y la integración con módulos existentes es limpia. No hay findings bloqueantes.

---

## Summary

SPEC-009 extiende la infraestructura de video para soportar múltiples proveedores mediante una abstracción común. La arquitectura implementada sigue el patrón Strategy/Registry establecido en el plan:

1. **Contract** (`VideoProvider`): Interfaz mínima de 3 métodos (`key`, `normalize`, `embedUrl`)
2. **Registry** (`VideoProviders`): Resolución de proveedores por key
3. **Implementations** (`YouTubeVideoProvider`, `VimeoVideoProvider`): Proveedores concretos
4. **Renderer** (`VideoEmbedRenderer`): Generación de iframe HTML segura

La implementación es proporcional al alcance (RNF-006): no introduce complejidad innecesaria, no requiere migración, y mantiene compatibilidad total con el contenido existente de YouTube.

---

## Architecture Compliance

### 1. Separation of Concerns — PASS

**Observation:**

El código está organizado en capas claras con responsabilidades bien definidas:

```
Courses/Video/
├── VideoProvider.php (interface)
├── VideoProviders.php (registry)
├── YouTubeVideoProvider.php (implementation)
└── VimeoVideoProvider.php (implementation)

Access/Application/
└── VideoEmbedRenderer.php (renderer)

Courses/Presentation/Admin/
└── CurriculumMetaBoxes.php (admin UI)
```

**Analysis:**

- `VideoProvider` define el contrato (qué puede hacer un proveedor)
- `VideoProviders` resuelve proveedores por key (dónde encontrarlos)
- Implementaciones concretas encapsulan lógica específica de cada proveedor
- `VideoEmbedRenderer` genera HTML (cómo presentarlo)
- `CurriculumMetaBoxes` maneja la UI administrativa (cómo configurarlo)

Cada componente tiene una responsabilidad única. No hay mezcla de lógica de parsing con lógica de renderizado, ni de validación con presentación.

**Verdict:** PASS

---

### 2. Dependency Direction — PASS

**Observation:**

Las dependencias fluyen correctamente:

```
Presentation → Application → Domain (Video/)
                    ↓
              Courses (SPEC-002)
```

**Analysis:**

- `VideoEmbedRenderer` (Access/Application) depende de `VideoProviders` (Courses/Video)
- `CurriculumMetaBoxes` (Courses/Presentation/Admin) depende de `VideoProviders` (Courses/Video)
- Los proveedores concretos no dependen de nada externo (solo WordPress `wp_parse_url`)
- No hay dependencias circulares
- Video/ no depende de Access/, Progress/, Enrollment/, etc. (cumple RB-006)

**Verdict:** PASS

---

### 3. Interface Design — PASS

**Observation:**

La interfaz `VideoProvider` es mínima y cohesiva:

```php
interface VideoProvider
{
    public function key(): string;
    public function normalize(string $source): ?string;
    public function embedUrl(string $video_id): string;
}
```

**Analysis:**

- `key()`: Identificador único del proveedor (youtube, vimeo)
- `normalize()`: Convierte URL/source a ID normalizado (o null si inválido)
- `embedUrl()`: Construye URL de embed desde ID validado

La interfaz sigue el principio de mínima superficie (RNF-006). No hay métodos innecesarios. Es fácil de implementar para nuevos proveedores.

**Verdict:** PASS

---

### 4. Registry Pattern — PASS

**Observation:**

`VideoProviders` implementa un registry simple y explícito:

```php
final class VideoProviders
{
    public function forKey(string $key): ?VideoProvider
    {
        foreach (array(new YouTubeVideoProvider(), new VimeoVideoProvider()) as $provider) {
            if ($provider->key() === $key) {
                return $provider;
            }
        }
        return null;
    }

    public function supports(string $key): bool
    {
        return $this->forKey($key) instanceof VideoProvider;
    }
}
```

**Analysis:**

- Registry explícito (no usa reflection, config files, o magic)
- Retorna `null` para providers desconocidos (fail-safe)
- `supports()` permite verificar antes de usar
- Agregar un nuevo proveedor requiere solo: (1) implementar interfaz, (2) agregar al array

**Consideration:**

Instancia proveedores en cada llamada a `forKey()`. Esto es inocuo a esta escala (2 proveedores, llamadas poco frecuentes). Si el rendimiento se volviera crítico, podría cachearse, pero no es necesario ahora.

**Verdict:** PASS

---

### 5. Provider Implementation — PASS

**Observation:**

Ambos proveedores siguen el mismo patrón:

```php
final class YouTubeVideoProvider implements VideoProvider
{
    private const VIDEO_ID_PATTERN = '/^[A-Za-z0-9_-]{11}$/';

    public function key(): string { return 'youtube'; }

    public function normalize(string $source): ?string
    {
        // 1. Trim
        // 2. Check if already normalized ID
        // 3. Parse URL with wp_parse_url
        // 4. Validate scheme (http/https)
        // 5. Validate host (allowlist)
        // 6. Extract ID from path/query
        // 7. Validate ID format
        // 8. Return ID or null
    }

    public function embedUrl(string $video_id): string
    {
        return 'https://www.youtube-nocookie.com/embed/' . rawurlencode($video_id) . '...';
    }
}
```

**Analysis:**

- Validación estricta de hosts (allowlist)
- Validación de scheme (solo http/https)
- Normalización robusta (múltiples formatos de URL → ID)
- Embed URL construida internamente (no acepta URLs arbitrarias)
- `rawurlencode()` previene inyección en URL
- Patrones regex específicos para cada proveedor

**Security:**

- YouTube: Valida host, scheme, formato de ID (11 chars alfanuméricos + _ -)
- Vimeo: Valida host, scheme, formato de ID (numérico)
- Ambos rechazan hosts maliciosos, schemes no seguros, IDs inválidos

**Verdict:** PASS

---

### 6. Renderer Design — PASS

**Observation:**

`VideoEmbedRenderer` genera HTML de forma segura:

```php
final class VideoEmbedRenderer
{
    public function render(string $provider_key, string $source, string $title): string
    {
        $provider = $this->providers->forKey($provider_key);
        $video_id = $provider?->normalize($source);

        if (! is_string($video_id)) {
            return '';
        }

        return sprintf(
            '<iframe class="kaanbal-player__video" src="%1$s" title="%2$s" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>',
            esc_url($provider->embedUrl($video_id)),
            esc_attr($title)
        );
    }
}
```

**Analysis:**

- Usa el registry para resolver proveedor
- Normaliza la source antes de usar
- Retorna string vacío si provider/source inválidos (fail-safe)
- `esc_url()` en src del iframe
- `esc_attr()` en title
- Atributos de seguridad: `loading="lazy"`, `referrerpolicy`, `allow` restringido
- No acepta HTML arbitrario en ningún parámetro

**Verdict:** PASS

---

### 7. Admin Integration — PASS

**Observation:**

`CurriculumMetaBoxes` integra el sistema de video en el admin:

```php
private function saveVideo(int $post_id): void
{
    $provider_key = sanitize_key(wp_unslash($_POST['kaanbal_video_provider']));
    $source = sanitize_text_field(wp_unslash($_POST['kaanbal_video_source']));

    if ('' === $provider_key || '' === $source) {
        delete_post_meta($post_id, self::PROVIDER_META);
        delete_post_meta($post_id, self::SOURCE_META);
        return;
    }

    $provider = (new VideoProviders())->forKey($provider_key);
    $video_id = $provider instanceof VideoProvider ? $provider->normalize($source) : null;

    if (null !== $video_id) {
        update_post_meta($post_id, self::PROVIDER_META, $provider->key());
        update_post_meta($post_id, self::SOURCE_META, $video_id);
    }
}
```

**Analysis:**

- Sanitización de inputs (`sanitize_key`, `sanitize_text_field`, `wp_unslash`)
- Validación de provider/source antes de guardar
- Guarda ID normalizado (no URL original)
- Limpia metadatos si provider/source vacíos
- No guarda si source es inválida para el provider elegido

**Verdict:** PASS

---

### 8. Backward Compatibility — PASS

**Observation:**

La implementación mantiene compatibilidad total con SPEC-002/004:

- Metadata keys no cambiaron: `_kaanbal_video_provider`, `_kaanbal_video_source`
- YouTube existente ya guardaba ID normalizado
- `VideoEmbedRenderer` re-normaliza en cada render (tolerante a formatos antiguos)
- No requiere migración de datos

**Analysis:**

- Lecciones YouTube existentes continúan funcionando sin cambios
- El renderer acepta tanto URLs completas como IDs normalizados
- No hay breaking changes en la API de video

**Verdict:** PASS

---

### 9. Extensibility — PASS

**Observation:**

Agregar un nuevo proveedor requiere:

1. Crear clase que implemente `VideoProvider`
2. Agregar instancia al array en `VideoProviders::forKey()`

**Analysis:**

- No requiere modificar Course, Lesson, Player, ni ningún otro módulo
- No requiere migración de datos
- No requiere cambios en templates
- El renderer funciona con cualquier proveedor que implemente la interfaz

Esto cumple RNF-005 (extensibilidad proporcional) y RNF-006 (no overengineering).

**Verdict:** PASS

---

### 10. Security Design — PASS

**Observation:**

La implementación sigue principios de seguridad defense-in-depth:

1. **Input validation**: Sanitización en admin (`sanitize_key`, `sanitize_text_field`)
2. **Host allowlist**: Solo hosts específicos aceptados por proveedor
3. **Scheme validation**: Solo http/https
4. **ID format validation**: Regex estricto por proveedor
5. **URL encoding**: `rawurlencode()` en embed URLs
6. **Output escaping**: `esc_url()`, `esc_attr()` en HTML
7. **No external APIs**: No hay llamadas HTTP a servicios externos
8. **Fail-safe**: Retorna vacío/null en lugar de error o HTML inválido

**Analysis:**

- No es posible inyectar HTML/JavaScript arbitrario
- No es posible apuntar el iframe a dominios no autorizados
- No es posible bypass de validación con URLs malformadas
- No hay exposición de datos sensibles
- No hay tracking o analytics no autorizados

**Verdict:** PASS

---

### 11. No Side Effects — PASS

**Observation:**

El sistema de video no afecta otros módulos:

- No modifica Enrollment
- No modifica Progress
- No modifica Quiz
- No modifica Course Completion
- No escribe en base de datos (excepto metadata de lesson)
- No hace llamadas HTTP externas

**Analysis:**

Esto cumple RB-006 (provider no influye en lógica académica) y RB-007 (ver video no completa lección).

**Verdict:** PASS

---

## Detailed Findings

### ARCH-001 — VideoProviders instantiates providers on every call (Info)

**Severity:** Info  
**Blocking:** No  
**Category:** Performance

**Description:**

`VideoProviders::forKey()` crea nuevas instancias de proveedores en cada llamada:

```php
foreach (array(new YouTubeVideoProvider(), new VimeoVideoProvider()) as $provider) {
    // ...
}
```

**Analysis:**

Esto es inocuo a la escala actual:

- 2 proveedores (objetos ligeros sin estado)
- Llamadas poco frecuentes (admin save, lesson render)
- No hay I/O ni operaciones costosas en constructores

Si el número de proveedores creciera significativamente (10+), o si las llamadas fueran muy frecuentes (miles por request), podría considerarse cachear instancias. Pero no es el caso actual.

**Recommendation:**

No action needed. La simplicidad actual es preferible a la optimización prematura.

**Impact:** Info. Sin impacto en rendimiento real.

---

### ARCH-002 — Vimeo embed without privacy parameter (Info)

**Severity:** Info  
**Blocking:** No  
**Category:** Consistency

**Description:**

YouTube usa `youtube-nocookie.com` (privacidad mejorada), pero Vimeo usa `player.vimeo.com` sin `?dnt=1` (do not track).

**Analysis:**

Esto es consistente con CODE-002 del code audit. La política de privacidad difiere entre proveedores:

- YouTube: Privacidad mejorada (nocookie)
- Vimeo: Estándar (sin parámetro dnt)

No es un problema arquitectónico, sino de política de privacidad. Vimeo soporta `?dnt=1` para no establecer cookies de rastreo.

**Recommendation:**

Decisión humana: Agregar `?dnt=1` al embed de Vimeo para igualar la postura de privacidad de YouTube. Esto es una change de una línea en `VimeoVideoProvider::embedUrl()`.

**Impact:** Info. Sin impacto en funcionalidad o seguridad.

---

### ARCH-003 — YouTube regex accepts trailing newline (Info)

**Severity:** Info  
**Blocking:** No  
**Category:** Security Hardening

**Description:**

El patrón de YouTube usa `$` en lugar de `\z`:

```php
private const VIDEO_ID_PATTERN = '/^[A-Za-z0-9_-]{11}$/';
```

Esto permite un salto de línea final (`\n`) en el ID.

**Analysis:**

Esto es consistente con CODE-003 del code audit y SPEC-004 CODE-007. El impacto es mínimo:

- Admin: `sanitize_text_field` elimina octetos `%xx` antes de normalizar
- Render: Re-normaliza, y el ID con `\n` resulta en URL rota (no inyección)
- Dominio: Fijo (youtube-nocookie.com), no controlable por el ID
- Escaping: `esc_url()` escapa cualquier caracter especial

No es explotable, pero es una mejora de hardening menor.

**Recommendation:**

Usar `\z` en lugar de `$` para anclar al final absoluto del string:

```php
private const VIDEO_ID_PATTERN = '/^[A-Za-z0-9_-]{11}\z/';
```

Esto es una change de una línea y previene el edge case.

**Impact:** Info. Sin impacto en seguridad real, pero mejora el hardening.

---

### ARCH-004 — Admin silently discards invalid sources (Info)

**Severity:** Info  
**Blocking:** No  
**Category:** UX

**Description:**

`CurriculumMetaBoxes::saveVideo()` no guarda nada si la source es inválida para el provider elegido, sin notificar al administrador.

**Analysis:**

Esto es consistente con CODE-001 del code audit. El comportamiento es:

- Correcto desde el punto de vista de seguridad (no acepta sources inválidas)
- Confuso desde el punto de vista de UX (admin no sabe por qué no se guardó)

Ejemplo: Admin elige Vimeo, pega URL de YouTube, guarda. Al recargar, ve que sigue el video anterior sin explicación.

No es un problema arquitectónico, sino de UX. La corrección requeriría:

- Agregar admin notices
- Validar en JavaScript antes de submit
- O aceptar formatos adicionales (player.vimeo.com, shorts, etc.)

**Recommendation:**

Decisión humana: Agregar feedback al administrador cuando la source sea inválida. Esto es una mejora de UX, no un cambio arquitectónico.

**Impact:** Info. Sin impacto en funcionalidad o seguridad.

---

## Compliance with Approved Architecture

| Principle | Status | Notes |
|-----------|--------|-------|
| Modular structure | PASS | Clear separation of Video module |
| Interface design | PASS | Minimal, cohesive VideoProvider interface |
| Registry pattern | PASS | Simple, explicit provider resolution |
| Provider implementation | PASS | Strict validation, secure embed URLs |
| Renderer design | PASS | Safe HTML generation with escaping |
| Admin integration | PASS | Proper sanitization and validation |
| Backward compatibility | PASS | No breaking changes, no migration needed |
| Extensibility | PASS | Easy to add new providers |
| Security design | PASS | Defense-in-depth, fail-safe |
| No side effects | PASS | Does not affect academic logic |
| Dependency direction | PASS | Correct flow, no circular dependencies |

---

## Comparison with Plan

La implementación sigue closely el plan definido en `plan.md`:

| Plan Component | Implementation | Status |
|----------------|----------------|--------|
| VideoProvider interface | `VideoProvider.php` (3 methods) | PASS |
| VideoProviderRegistry | `VideoProviders.php` | PASS |
| YouTube provider | `YouTubeVideoProvider.php` | PASS |
| Vimeo provider | `VimeoVideoProvider.php` | PASS |
| Embed renderer | `VideoEmbedRenderer.php` | PASS |
| Admin UI | `CurriculumMetaBoxes.php` (updated) | PASS |
| Metadata storage | `_kaanbal_video_provider`, `_kaanbal_video_source` | PASS |
| Normalization | Store normalized ID | PASS |
| Host validation | Allowlist per provider | PASS |
| Embed URL construction | Internal, from normalized ID | PASS |

La implementación añade detalles apropiados no explícitos en el plan:

- `supports()` method en registry para verificar antes de usar
- `rawurlencode()` en embed URLs para prevenir inyección
- Atributos de seguridad en iframe (`loading`, `referrerpolicy`, `allow`)
- YouTube usa `youtube-nocookie.com` (privacidad mejorada)

---

## Integration with Previous SPECs

### SPEC-001 (Foundation)

- Video module no requiere hooks especiales de foundation
- Usa autoloading PSR-4 estándar

**Status:** PASS

### SPEC-002 (Courses and Curriculum)

- Video module vive en `Courses/Video/` (parte de Courses)
- Metadata keys no cambiaron
- Lecciones existentes continúan funcionando

**Status:** PASS

### SPEC-004 (Course Access and Player)

- `VideoEmbedRenderer` reemplaza `YouTubeEmbedRenderer` (renombrado)
- Lesson View usa el nuevo renderer
- Player no conoce detalles de proveedores específicos

**Status:** PASS

### SPEC-005+ (Progress, Quiz, Completion, Dashboard, Reporting)

- Video module no interactúa con estos módulos
- Ver video no afecta progreso, quiz, ni completion
- Dashboard y reporting no muestran información de video

**Status:** PASS

---

## Conclusion

La implementación de SPEC-009 demuestra excelente disciplina arquitectónica:

**Strengths:**

1. Separación clara de concerns (contrato, registry, implementaciones, renderer)
2. Interfaz mínima y cohesiva (3 métodos)
3. Registry simple y explícito (no over-engineered)
4. Validación estricta de hosts, schemes, y formatos de ID
5. Embed URLs construidas internamente (no acepta URLs arbitrarias)
6. Output escaping correcto en todo el HTML generado
7. Compatibilidad total con contenido existente (no requiere migración)
8. Extensibilidad proporcional (fácil agregar nuevos proveedores)
9. Sin efectos secundarios en lógica académica
10. Seguridad defense-in-depth (múltiples capas de validación)

**Non-blocking findings:**

- ARCH-001 (Info): `VideoProviders` instancia proveedores en cada llamada - inocuo a esta escala
- ARCH-002 (Info): Vimeo embed sin parámetro de privacidad - decisión de política
- ARCH-003 (Info): YouTube regex acepta newline final - hardening menor
- ARCH-004 (Info): Admin descarta sources inválidas silenciosamente - decisión de UX

La implementación es mantenible, extensible, y lista para soportar futuros proveedores (Bunny Stream, Cloudflare Stream, etc.) sin modificar la lógica central de Course/Lesson.

Los findings no bloqueantes (ARCH-001 a ARCH-004) son menores y no afectan la correctness o seguridad del código. Representan oportunidades para refinamiento futuro si los requisitos cambian.

---

## Audit Result

```
Verdict: PASS
Blocking findings: 0
Recommendations: 0
Informational notes: 4
```

**La implementación respeta la arquitectura aprobada y es mantenible.**

---

## Findings from Previous Rounds

### Round 1

- Code audit: PASS WITH RECOMMENDATIONS (CODE-001 a CODE-004)
- Security audit: Pending
- Architecture audit: PASS (this report)

Los findings de code audit (CODE-001 a CODE-004) son principalmente de UX y hardening menor. No hay findings bloqueantes. La arquitectura es sólida y sigue los principios de simplicidad y extensibilidad proporcional.
