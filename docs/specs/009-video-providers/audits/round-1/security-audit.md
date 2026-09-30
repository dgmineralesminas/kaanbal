# Auditoría de Seguridad

SPEC: `009-video-providers`
Ronda: `1`
Auditor: Mimo
Commit auditado: `b3d315f`
Fecha: `2026-09-29`

Verdict: `PASS`

Audit completeness: `Complete`

Notas de alcance de la ronda:

- `b3d315f` es el commit de implementación registrado en `implementation-status.md`. Verificado que el árbol de trabajo es idéntico a `b3d315f` para `src/`, `tests/`, `templates/`, `assets/` y `kaanbal.php`; el HEAD (`94605b6`) solo añade documentación.
- Primera auditoría de seguridad de SPEC-009. Los identificadores SEC inician en `SEC-001`.
- Auditoría realizada de forma independiente: los informes de otros auditores en `audits/round-1/` no fueron consultados.

---

## 1. Resumen

Evaluación de seguridad de la implementación dentro del alcance aprobado.

SPEC-009 extiende el reproductor a Vimeo. El riesgo central es el mismo que en SPEC-004 (RNF-006 de esa SPEC): que un campo de video almacenado produzca HTML arbitrario (XSS) o un iframe hacia un host no deseado. La implementación mantiene y refuerza el modelo seguro ya establecido:

- el iframe se construye exclusivamente desde URLs internas hardcodeadas por provider (`embedUrl()`) a partir de un ID normalizado;
- ambos providers validan host (allowlist exacta), scheme (`http`/`https`) y forma del ID antes de aceptar una fuente;
- el admin persiste únicamente el ID normalizado, nunca el texto crudo;
- el renderer devuelve cadena vacía ante provider desconocido o fuente inválida (fail-closed) y escapa `src` y `title`;
- los cinco casos de seguridad obligatorios del plan tienen pruebas con payloads hostiles reales (`javascript:`, iframe/HTML, hosts foráneos para ambos providers) y además se verifica que las aserciones de escaping existen.

No se encontraron vulnerabilidades ni vacíos de prueba materiales. Sin findings.

Blocking findings: `0`
Non-blocking findings: `0`

---

## 2. Superficies Revisadas

- XSS / output escaping: revisada — renderer, template de lección, formulario admin
- embed injection / host allowlist: revisada — YouTube y Vimeo, scheme, path, ID
- almacenamiento de fuentes de video: revisada — normalización en el guardado admin
- provider registry: revisada — resolución por clave, provider desconocido
- regresión de compatibilidad: revisada — lecciones YouTube existentes, lección sin video
- SQL / CSRF / autorización: N/A o sin cambios — esta SPEC no añade endpoints ni consultas; el guardado admin conserva el nonce + capability de SPEC-002
- manipulación de progreso: revisada — renderizar video no escribe estado (AC-018)

---

## 3. Threat Review

| Superficie | Estado | Evidence |
|---|---|---|
| XSS | `PASS` | `VideoEmbedRenderer::render()` produce el iframe con `sprintf` fijo, `esc_url($provider->embedUrl($video_id))` y `esc_attr($title)` (`src/Access/Application/VideoEmbedRenderer.php:15-26`); el template solo hace `echo $player` (marcado generado internamente) y no vuelca `provider`/`source` (`templates/frontend/lesson.php:18-19`). Pruebas: payload `<iframe …>` y `javascript:` → cadena vacía; título con comillas → `&quot;` (`tests/Unit/VideoEmbedRendererTest.php:18-42`). |
| Embed Host Allowlist | `PASS` | Vimeo acepta solo `vimeo.com`/`www.vimeo.com` con scheme `http`/`https` y un único segmento de path numérico (`src/Courses/Video/VimeoVideoProvider.php:18-38`); YouTube conserva sus hosts y ahora exige scheme `http`/`https` (`src/Courses/Video/YouTubeVideoProvider.php:26`, endurecimiento en esta SPEC). Pruebas de hosts foráneos para ambos providers (`VimeoVideoProviderTest.php:41-48`, `YouTubeVideoProviderTest.php:48-54`). |
| URL controlada | `PASS` | `embedUrl()` está hardcodeado por provider y aplica `rawurlencode` al ID (`VimeoVideoProvider.php:41-44`, `YouTubeVideoProvider.php:52-55`): incluso una normalización incompleta no podría desviar el iframe a otro host ni inyectar atributos. Doble defensa verificada estáticamente. |
| Almacenamiento de fuentes | `PASS` | `CurriculumMetaBoxes::saveVideo()` usa `sanitize_key` + `sanitize_text_field` y solo persiste `$provider->normalize($source)` cuando es válido; si la combinación provider/source es inválida, no sobrescribe la metadata existente (fail-closed) (`src/Courses/Presentation/Admin/CurriculumMetaBoxes.php:180-205`, AC-015). Verificado por integración: una fuente incompatible no pisa la metadata guardada (`tests/Integration/courses-curriculum.php`). |
| Provider Registry | `PASS` | `VideoProviders::forKey()` resuelve por clave exacta entre los providers implementados; clave desconocida → `null` → render vacío (`src/Courses/Video/VideoProviders.php`, AC-003; probado en `VideoProvidersTest.php:20-25` y `VideoEmbedRendererTest.php:33`). Un provider forzado en base de datos (`<script>`, `javascript`) no produce salida activa. |
| Render sin escrituras | `PASS` | Abrir una lección con video YouTube o Vimeo no modifica progreso: probado con conteo de filas antes/después del render del template (`tests/Integration/course-access-player.php`, AC-018). |
| Regresión YouTube | `PASS` | Las lecciones existentes con ID de YouTube siguen renderizando `youtube-nocookie.com/embed/{id}` y las lecciones sin video siguen sin player (integración, AC-016). |

---

## 4. Findings

Ninguno.

Los cinco casos obligatorios de `plan.md` §27 están cubiertos con pruebas significativas y aisladas: payload `javascript:` como source, payload iframe/HTML como source, URL maliciosa (scheme no soportado), host foráneo fingiendo Vimeo y host foráneo fingiendo YouTube. Verificado por inspección que las aserciones fallarían si se retirara la validación correspondiente (no son placeholders): los casos inválidos exigen `null` o cadena vacía y los válidos exigen el ID normalizado exacto.

---

## 5. Findings de Rondas Anteriores

Ninguno. Primera ronda de auditoría de seguridad de SPEC-009.

---

## 6. Recomendaciones de Hardening

Ninguna.

Las mejoras observadas durante el diseño de esta SPEC ya están implementadas (allowlist de hosts, whitelist de scheme, IDs normalizados, `embedUrl` interno, `esc_url`/`esc_attr`). No se identifican mejoras opcionales proporcionales al alcance que no representen alcance nuevo (por ejemplo, soporte de Vimeo privado o `sandbox` en el iframe, ambos fuera de la SPEC).

---

## 7. Limitaciones

- Ejecución dinámica de fixtures de integración: `BLOCKED BY ENVIRONMENT`. Los fixtures de `courses-curriculum` y `course-access-player` requieren `KAANBAL_WP_PATH` con un WordPress vivo; no se ejecutaron durante esta auditoría. Fueron revisados estáticamente y sus aserciones consideradas significativas (verificado: se afirma el marcado del iframe concreto de cada provider y la no escritura de progreso).
- Suite unitaria ejecutada por el auditor: `composer test` → `OK (108 tests, 145 assertions)` (verificado, 2026-09-29).
- No se realizó explotación dinámica (pentest); el análisis de payloads hostiles se hizo por inspección estática de `normalize()`/`embedUrl()` y de las pruebas de regresión existentes.
- Observación informativa sin impacto (no finding): la whitelist de scheme de Vimeo no tiene un caso propio con host válido y scheme inválido (p. ej. `ftp://vimeo.com/123`); la ruta es no explotable porque `embedUrl()` es interno y el ID es numérico. El caso equivalente de YouTube sí está probado (`ftp://`).

---

## 8. Conclusión

Verdict:

`PASS`

Blocking findings:

- Ninguno.

Findings no bloqueantes:

- Ninguno.

Recommendations:

- Ninguna.

Comentario final:

SPEC-009 amplía el reproductor sin ampliar la superficie de ataque: el HTML del player sigue construyéndose solo desde URLs internas controladas y IDs normalizados, con allowlist de hosts y scheme, doble defensa en `embedUrl()`, guardado sanitizado en admin y fail-closed ante datos inválidos o forzados. Las pruebas de seguridad cubren exactamente los payloads y hosts foráneos que el plan exige, incluidas las aserciones de escaping. La implementación está lista para revisión humana desde el punto de vista de seguridad.
