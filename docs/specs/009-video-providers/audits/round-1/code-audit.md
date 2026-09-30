# Auditoría de Código y Cumplimiento

SPEC: `SPEC-009 — Video Providers`
Ronda: `1`
Auditor: Claude Code
Commit auditado: `b3d315f` (candidato registrado en `implementation-status.md`), revisado en el HEAD `94605b6` (branch `feature/spec-009-video-providers`)
Fecha: `2026-09-30`

Verdict: `PASS WITH RECOMMENDATIONS`

Audit completeness: `Complete` para revisión estática, PHPUnit, PHPCS, PHPStan y arnés propio. Pruebas de integración contra WordPress/MySQL: `BLOCKED BY ENVIRONMENT` (ver §8).

## 1. Resumen

SPEC-009 cumple los 18 criterios de aceptación con un diseño proporcional (RNF-006):

- **Contrato:** interfaz `VideoProvider` de tres métodos (`key`, `normalize`, `embedUrl`).
- **Registro:** `VideoProviders` explícito y pequeño.
- **Proveedores:** `YouTubeVideoProvider` adaptado y `VimeoVideoProvider` nuevo.
- **Render:** un único `VideoEmbedRenderer` que construye el iframe a partir de la URL que genera el proveedor con el ID normalizado.

Seguridad y alcance:

- El dominio del embed es fijo por proveedor.
- Los hosts se validan por igualdad exacta tras `wp_parse_url` y se exige `http`/`https`.
- Ninguna fuente llega al HTML sin pasar por la normalización.
- No hay llamadas HTTP externas ni tracking.
- No hace falta migración: los datos YouTube existentes ya guardan proveedor + ID normalizado, así que TASK-018 queda `Not Applicable`, justificado.

Trazabilidad: `spec.md`, `plan.md` y `feature.feature` se versionaron en `4532133`, antes de la implementación, y no cambiaron después.

Las pruebas obligatorias del plan están completas: seguridad (§27) y regresión de progreso con ambos proveedores (§28).

No hay findings bloqueantes. Los hallazgos son de experiencia del administrador (`CODE-001`) y observaciones menores (`CODE-002` a `CODE-004`).

Tasks revisadas: `34` (33 Done, 1 Not Applicable)
Acceptance Criteria revisados: `18`
Escenarios Gherkin revisados: todos los de `feature.feature`

Blocking findings: `0`
Non-blocking findings: `4` (CODE-001 a CODE-004)

---

## 2. Alcance Auditado

Git:

- Rama desde `4532133` (SPEC-008 cerrada; contrato de SPEC-009 versionado en ese commit).
- `git diff --stat 4532133 b3d315f -- . ':!docs'`: 15 archivos.
- `git diff --stat 4532133 94605b6 -- docs/specs/009-video-providers/`: solo `implementation-status.md` y `tasks.md`; los archivos protegidos no cambiaron.
- `git diff --quiet b3d315f 94605b6 -- src tests templates assets kaanbal.php composer.json composer.lock` → sin diferencias.
- Working tree limpio.

Código revisado íntegramente:

- `src/Courses/Video/{VideoProvider,VideoProviders,YouTubeVideoProvider,VimeoVideoProvider}.php`
- `src/Access/Application/VideoEmbedRenderer.php` (reemplaza a `YouTubeEmbedRenderer`, eliminado sin referencias remanentes)
- `src/Courses/Presentation/Admin/CurriculumMetaBoxes.php` (`renderLesson`, `saveVideo`)
- `templates/frontend/lesson.php`

Pruebas revisadas:

- `tests/Unit/{VideoProvidersTest,YouTubeVideoProviderTest,VimeoVideoProviderTest,VideoEmbedRendererTest}.php`
- Cambios en `tests/Integration/{course-access-player,courses-curriculum}.php`

---

## 3. Trazabilidad

Leyenda: **Yes** = prueba del proyecto con aserción que lo comprueba (las de integración, evaluadas por lectura; §8); **Static** = verificado por lectura y/o arnés del auditor.

| AC | Implemented | Verified | Evidence |
|---|---|---|---|
| AC-001 | Yes | Yes | `VideoProvidersTest`: `forKey('youtube')` |
| AC-002 | Yes | Yes | `VideoProvidersTest`: `forKey('vimeo')` |
| AC-003 | Yes | Yes | `VideoProvidersTest`: `unknown` → `null`/`false`; `VideoEmbedRendererTest` "unsupported provider" → `''` |
| AC-004 | Yes | Yes | `YouTubeVideoProviderTest` (URL `watch`), sin cambios desde SPEC-002 |
| AC-005 | Yes | Yes | `YouTubeVideoProviderTest` (`youtu.be`) |
| AC-006 | Yes | Yes | `VimeoVideoProviderTest`: estándar, `www`, slash final, query string e ID normalizado |
| AC-007 | Yes | Yes | `YouTubeVideoProviderTest`: host ajeno, URL Vimeo, sin ID, tamaño inválido, esquema `ftp` |
| AC-008 | Yes | Yes | `VimeoVideoProviderTest`: vacío, URL YouTube, host ajeno con número, no numérico, ruta anidada, HTML, `javascript:` |
| AC-009 | Yes | Yes | `course-access-player.php`: `youtube-nocookie.com/embed/dQw4w9WgXcQ` |
| AC-010 | Yes | Yes | `course-access-player.php`: `player.vimeo.com/video/123456789` |
| AC-011 | Yes | Yes | `VideoEmbedRendererTest`: payloads iframe y `javascript:` → `''`; sin `<script>` en salidas válidas; título escapado |
| AC-012 | Yes | Yes | `courses-curriculum.php`: la opción Vimeo se renderiza; guardar `https://vimeo.com/123456789` → `vimeo`/`123456789` |
| AC-013 | Yes | Yes | `courses-curriculum.php` (flujo YouTube existente) |
| AC-014 | Yes | Yes | `courses-curriculum.php:212-217` proveedor vacío limpia ambos metadatos; `course-access-player.php:241` lección sin video sin iframe |
| AC-015 | Yes | Yes | `courses-curriculum.php`: Vimeo + URL YouTube no sobrescribe los metadatos guardados |
| AC-016 | Yes | Yes | Lección YouTube existente (`youtube`/`dQw4w9WgXcQ`) renderiza igual que antes |
| AC-017 | Yes | Static | `lesson.php` usa `VideoEmbedRenderer`; `grep -rni "youtube\|vimeo" src templates` fuera de `src/Courses/Video/` y del admin → sin resultados |
| AC-018 | Yes | Yes | `course-access-player.php`: conteo de progreso igual antes y después de renderizar YouTube y Vimeo |

Casos límite:

- EC-001/EC-002: probados.
- EC-003: probado, y además verificado con el arnés en subdominios y rutas engañosas.
- EC-004: probado.
- EC-005: al guardar se limpian los metadatos; al renderizar, `forKey('')` → sin iframe.
- EC-006/EC-007: probados.
- EC-008: probado.

Pruebas obligatorias del plan:

- §27 (payload `javascript`, payload `iframe`, URL maliciosa, host ajeno que se hace pasar por Vimeo, host ajeno que se hace pasar por YouTube): **todas presentes**.
- §28 (progreso con YouTube y Vimeo): **presentes**.

---

## 4. Verificación de Tasks

Las 33 tasks `Done` son conformes. TASK-018 (migración) está `Not Applicable` con justificación verificable: `_kaanbal_video_provider` + `_kaanbal_video_source` ya guardaban proveedor e ID normalizado desde SPEC-002, y el renderer re-normaliza en cada render, así que un ID normalizado sigue siendo válido.

---

## 5. Findings

### CODE-001 — El admin descarta en silencio una fuente inválida o no soportada y conserva el video anterior

Severity: Low
Blocking: No
Classification: Recommendation (UX)
Status: NEW

Affected requirement:

`RF-009` ("no debe aceptarse silenciosamente"), `RF-014`, `AC-015`

Affected files:

- `src/Courses/Presentation/Admin/CurriculumMetaBoxes.php` (`saveVideo`)

Description:

Si el proveedor y la fuente no son compatibles (p. ej. Vimeo + URL de YouTube), `saveVideo()` no guarda nada y sale sin aviso. La fuente inválida no se acepta, así que RF-009 y AC-015 se cumplen. Pero el administrador no recibe retroalimentación y la lección conserva el video anterior. Si intentaba cambiar de YouTube a Vimeo con una URL que el parser no reconoce, al recargar ve otra vez YouTube sin saber por qué.

Varios formatos que un administrador copia con frecuencia se rechazan:

- `https://player.vimeo.com/video/123456789`, la URL que aparece en el código de inserción de Vimeo;
- `https://vimeo.com/channels/…/123456789`;
- `https://www.youtube.com/shorts/…`.

Ninguno es requerido por la SPEC, que exige como mínimo los formatos estándar.

Evidence:

Arnés del auditor con las clases reales (§8).

Impact:

Confusión del administrador al configurar lecciones. Es el mismo patrón de errores silenciosos que SPEC-006 CODE-003.

Required correction (sugerida):

Mostrar un aviso de administración cuando la fuente no sea válida para el proveedor elegido, p. ej. un `redirect_post_location` con parámetro, más un `admin_notices` que lo lea. Opcional: aceptar `player.vimeo.com/video/{id}` como formato documentado y probado (RF-006 lo permite).

---

### CODE-002 — Vimeo embebido sin el parámetro de no-rastreo, a diferencia de YouTube

Severity: Info
Blocking: No
Classification: Recommendation
Status: NEW

Affected files:

- `src/Courses/Video/VimeoVideoProvider.php` (`embedUrl`)

Description:

YouTube se embebe con `youtube-nocookie.com` desde SPEC-004, una decisión de privacidad del proyecto. Vimeo se embebe como `https://player.vimeo.com/video/{id}` sin `dnt=1`, el parámetro de Vimeo para no establecer cookies de rastreo. No es requisito de SPEC-009 (plan §20 deja la privacidad de YouTube como opcional y §21 solo trata videos privados), pero hoy la política de privacidad difiere entre proveedores.

Required correction (sugerida):

Decisión humana: añadir `?dnt=1` al embed de Vimeo para igualar la postura de privacidad, con una prueba de `embedUrl`.

---

### CODE-003 — El ID de YouTube acepta un salto de línea final codificado en la query (heredado)

Severity: Info
Blocking: No
Classification: Recommendation
Status: NEW (relacionado con SPEC-004 CODE-007)

Affected files:

- `src/Courses/Video/YouTubeVideoProvider.php` (`VIDEO_ID_PATTERN = '/^[A-Za-z0-9_-]{11}$/'`)

Description:

`https://www.youtube.com/watch?v=dQw4w9WgXcQ%0A` → `parse_str` decodifica `%0A` → el patrón con `$` acepta `"dQw4w9WgXcQ\n"` → embed `…/embed/dQw4w9WgXcQ%0A…`. No es explotable: el dominio es fijo y la URL se escapa, y desde el admin `sanitize_text_field` elimina los octetos `%xx` antes de normalizar. El resultado es un video roto, no una inyección. Vimeo no se ve afectado (`https://vimeo.com/123456789%0A` → `null`).

Required correction (sugerida):

Usar `\z` en lugar de `$` en ambos patrones, como ya recomendó SPEC-004 CODE-007.

---

### CODE-004 — Observaciones menores

Severity: Info
Blocking: No
Classification: Recommendation
Status: NEW

- `VimeoVideoProvider` acepta el ID numérico directo (`123456789`). Es necesario, porque es el formato que se guarda y se re-normaliza al renderizar, y está probado ("normalized id"). Pero `implementation-status.md` (AC-006) no lo menciona entre los formatos soportados (RF-006: "formatos adicionales si se documentan y prueban").
- Un ID numérico de 11 dígitos es válido a la vez como ID de YouTube y de Vimeo. No hay ambigüedad de comportamiento, porque el proveedor siempre es explícito.
- `VideoProviders::forKey()` instancia ambos proveedores en cada llamada. Es inocuo a esta escala.
- La etiqueta del campo cambió a "Video URL or ID" y el admin sigue en inglés, mientras el sitio del alumno está en español: el patrón de i18n mixto ya registrado.

---

## 6. Findings de Rondas Anteriores

No aplica: es la ronda 1 de SPEC-009.

---

## 7. Recomendaciones

- CODE-001 es el único con impacto real en el día a día (configurar lecciones) y es barato de corregir.
- CODE-002 es una decisión de privacidad de una línea que conviene tomar explícitamente.

---

## 8. Validación Ejecutada

Entorno: shell remota Ubuntu 22.04 aarch64 con PHP 8.5.11 estático (`curso/.tools/`), usando el `vendor/` existente. Comandos ejecutados sobre `94605b6`, cuyo código es idéntico a `b3d315f`.

Comandos:

```text
find src tests templates -name '*.php' -print0 | xargs -0 -n1 php -l
php vendor/bin/phpunit --configuration phpunit.xml.dist
php vendor/bin/phpcs --warning-severity=0 --standard=phpcs.xml.dist
php vendor/bin/phpstan analyse --configuration=phpstan.neon --memory-limit=512M --debug
composer validate --strict
git diff --quiet b3d315f 94605b6 -- src tests templates assets kaanbal.php composer.json composer.lock
grep -rni "youtube\|vimeo" src templates   (fuera de src/Courses/Video/ y del admin)
grep -rn "YouTubeEmbedRenderer" src tests templates
```

Resultados:

```text
lint:      0 archivos con errores (incluye templates/)
PHPUnit:   OK (108 tests, 145 assertions)   ← coincide con implementation-status.md
PHPCS:     sin errores
PHPStan:   [OK] No errors (nivel 5)
composer:  ./composer.json is valid
git:       sin diferencias de código entre candidato y HEAD
grep:      sin ramas específicas de proveedor fuera de Video/ y admin; sin referencias al renderer eliminado
```

Arnés del auditor (clases reales de `src/`, stubs de WordPress):

```text
vimeo   https://vimeo.com.evil.test/123456789        → null  (sin iframe)
vimeo   https://evil.test/vimeo.com/123456789        → null
vimeo   https://notvimeo.com/123456789               → null
vimeo   https://attacker@vimeo.com/123456789         → 123456789 → player.vimeo.com/video/123456789 (inocuo)
vimeo   HTTPS://VIMEO.COM/123456789                  → 123456789
vimeo   //vimeo.com/123456789                        → null  (sin esquema)
vimeo   https://vimeo.com/123456789%0A               → null
vimeo   https://player.vimeo.com/video/123456789     → null  (CODE-001)
vimeo   https://vimeo.com/channels/staffpicks/123…   → null  (CODE-001)
youtube https://youtube.com.evil.test/watch?v=…      → null
youtube https://www.youtube.com/watch?v=…%0A         → "dQw4w9WgXcQ\n" (CODE-003)
youtube https://www.youtube.com/shorts/…             → null  (CODE-001)
youtube "><script>alert(1)</script>                  → null
```

En todos los casos aceptados, el `src` del iframe apunta a `player.vimeo.com` o `www.youtube-nocookie.com`; ninguna entrada controla el dominio.

Limitaciones ambientales:

`BLOCKED BY ENVIRONMENT` para `tests/Integration/*.php`, que necesitan WordPress y MySQL reales; las evalué por lectura. `implementation-status.md` declara que pasaron, con una advertencia no fatal de core en el runner de reporting. Es declaración de Codex.

---

## 9. Conclusión

Verdict:

`PASS WITH RECOMMENDATIONS`

Blocking findings:

- Ninguno

Non-blocking findings:

- CODE-001 — El admin descarta en silencio una fuente inválida o no soportada y conserva el video anterior
- CODE-002 — Vimeo embebido sin el parámetro de no-rastreo, a diferencia de YouTube
- CODE-003 — El ID de YouTube acepta un salto de línea final codificado en la query (heredado)
- CODE-004 — Observaciones menores

Comentario final:

SPEC-009 es una extensión limpia y bien acotada: amplía a dos proveedores sin tocar Course/Lesson, sin APIs externas, con el HTML generado siempre desde un ID validado y con las pruebas obligatorias completas. Desde la perspectiva de requisitos e implementación puede pasar a las auditorías de arquitectura y seguridad sobre el mismo candidato `b3d315f`.
