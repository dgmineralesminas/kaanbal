# Kaanbal

Kaanbal es un plugin LMS para WordPress orientado a la creación y entrega de cursos online.

El objetivo del proyecto es construir una solución ligera, mantenible y extensible para:

* cursos
* módulos
* lecciones
* videos
* progreso del alumno
* quizzes
* certificados
* integración con WooCommerce

WooCommerce administra el proceso comercial.

Kaanbal administra la experiencia educativa.

---

## Estado del Proyecto

Actualmente el proyecto se encuentra en fase inicial de arquitectura y definición de SPEC.

La primera unidad de trabajo es:

```text
SPEC-001 — Plugin Foundation
```

Ruta:

```text
docs/specs/001-plugin-foundation/
```

---

## Stack

Base prevista:

* WordPress
* PHP
* Composer
* WooCommerce
* PHPUnit
* PHPCS / WordPress Coding Standards
* PHPStan

Las versiones mínimas compatibles se definirán después de inspeccionar el entorno real de desarrollo.

---

## Arquitectura

Kaanbal se desarrolla como un plugin independiente.

Ubicación típica:

```text
wp-content/plugins/kaanbal/
```

El repositorio contiene únicamente el código y documentación del plugin.

No se versiona:

* WordPress Core
* WooCommerce Core
* themes externos
* uploads
* archivos de configuración del sitio

---

## Estructura

Estructura prevista:

```text
kaanbal/
├── kaanbal.php
├── composer.json
├── composer.lock
├── README.md
├── .gitignore
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
├── assets/
├── tests/
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

La estructura real puede evolucionar conforme avancen las SPEC.

---

## Modelo de Desarrollo

Kaanbal utiliza Spec Driven Development con múltiples agentes de IA especializados.

Flujo general:

```text
SPEC
  ↓
Aprobación humana
  ↓
Codex
Implementación
  ↓
Quality Gate
  ↓
Ready for audit
  ↓
┌──────────────┬──────────────┬──────────────┐
│ Claude Code  │ Qwen         │ Mimo         │
│ Código       │ Arquitectura │ Seguridad    │
└──────────────┴──────────────┴──────────────┘
  ↓
Consolidación
  ↓
PASS / FAIL
  ↓
Corrección si aplica
  ↓
Revisión humana
  ↓
Merge
```

Las reglas completas están documentadas en:

```text
docs/agents.md
docs/development-workflow.md
docs/definition-of-done.md
docs/audit-standard.md
```

---

## Agentes

### Codex

Responsable de:

* implementación
* pruebas
* tasks
* remediación
* preparación para auditoría

Es el único agente autorizado para modificar código de producción.

### Claude Code

Responsable de:

* cumplimiento de requisitos
* revisión de tasks
* comportamiento
* pruebas
* trazabilidad SPEC → código

### Qwen

Responsable de:

* arquitectura
* mantenibilidad
* boundaries
* dependencias
* consistencia técnica

### Mimo

Responsable de:

* seguridad
* autorización
* sanitización
* XSS
* CSRF
* SQL injection
* IDOR
* ownership
* superficies de ataque

---

## SPECs Iniciales

Roadmap previsto:

```text
001-plugin-foundation
002-courses-and-curriculum
003-woocommerce-enrollment
004-course-access-and-player
005-student-progress
006-final-quiz
007-certificates
008-student-dashboard
009-admin-reporting
```

Este roadmap puede evolucionar mediante decisión humana.

---

## Producto

Flujo funcional esperado:

```text
WooCommerce
    ↓
Compra
    ↓
Matrícula
    ↓
Curso
    ↓
Módulos
    ↓
Lecciones
    ↓
Progreso
    ↓
Quiz final
    ↓
Curso completado
    ↓
Certificado
```

---

## Videos

El proveedor inicial previsto es YouTube.

En el MVP:

* no se requiere seguimiento del segundo exacto
* no se requiere porcentaje reproducido
* no se requiere reanudar video automáticamente

El alumno podrá marcar manualmente una lección como:

```text
Completed
```

---

## Progreso

El progreso del curso se calculará inicialmente con base en las lecciones completadas.

Ejemplo:

```text
13 lecciones completadas
20 lecciones totales

13 / 20 = 65%
```

---

## WooCommerce

WooCommerce controla:

* productos
* carrito
* checkout
* pagos
* pedidos
* promociones
* cupones

Un producto puede otorgar acceso a varios cursos.

Un curso puede estar asociado a varios productos.

La relación será muchos a muchos.

---

## Testing

La estrategia de pruebas está documentada en:

```text
docs/testing-strategy.md
```

Antes de una auditoría formal, Codex debe ejecutar los checks obligatorios configurados.

Un check no ejecutado nunca debe reportarse como `PASS`.

---

## Auditorías

Cada SPEC conserva el historial de auditorías por ronda.

Ejemplo:

```text
docs/specs/003-woocommerce-enrollment/
└── audits/
    ├── round-1/
    │   ├── code-audit.md
    │   ├── architecture-audit.md
    │   ├── security-audit.md
    │   └── round-summary.md
    │
    └── round-2/
```

Las auditorías previas no se sobrescriben.

---

## Branches

Convención recomendada:

```text
main
feature/spec-001-plugin-foundation
feature/spec-002-courses-and-curriculum
feature/spec-003-woocommerce-enrollment
```

Cada SPEC debe desarrollarse en su propia rama salvo excepción aprobada.

---

## Commits

Ejemplos:

```text
docs: define SPEC-001
feat: implement SPEC-001 foundation
test: add SPEC-001 validation
audit: add SPEC-001 round-1 reports
fix: remediate SPEC-001 audit findings
```

---

## Instalación

La instalación técnica se documentará una vez implementada SPEC-001.

Flujo previsto:

```bash
composer install
```

y activar Kaanbal desde WordPress Admin.

---

## Desarrollo

Las instrucciones definitivas de desarrollo se documentarán después de configurar:

* Composer
* PHPUnit
* WordPress test environment
* PHPCS
* PHPStan

No asumir comandos que todavía no hayan sido configurados.

---

## Seguridad

Principios globales:

```text
frontend is untrusted
authorization is server-side
input is validated
output is escaped
queries are prepared
ownership is verified
```

Kaanbal no debe modificar WordPress Core ni WooCommerce Core.

---

## Documentación

Documentación principal:

```text
docs/project-context.md
docs/architecture.md
docs/data-model.md
docs/testing-strategy.md
```

Gobierno de desarrollo:

```text
docs/agents.md
docs/development-workflow.md
docs/definition-of-done.md
docs/audit-standard.md
```

Plantillas:

```text
docs/templates/
```

SPECs:

```text
docs/specs/
```

---

## Filosofía del Proyecto

Kaanbal prioriza:

* simplicidad
* corrección
* mantenibilidad
* seguridad
* trazabilidad

sobre complejidad innecesaria.

Principio funcional:

> WooCommerce vende. Kaanbal enseña.

Principio de desarrollo:

> Construir únicamente lo aprobado, verificar lo construido y hacer merge solo después de evidencia y revisión humana.
