# Plan — SPEC-001 Plugin Foundation

## 1. Resumen Técnico

La SPEC-001 establecerá la infraestructura base de Kaanbal como plugin WordPress moderno y modular.

La implementación debe permanecer mínima.

Se utilizará:

- archivo principal `kaanbal.php`
- Composer PSR-4
- namespace `Kaanbal\`
- bootstrap central
- lifecycle separado
- componente de requisitos
- Schema Manager
- registro simple de servicios
- testing automatizado

No se implementará funcionalidad LMS.

---

## 2. Inspección Previa Obligatoria

Antes de modificar código, Codex debe inspeccionar el entorno y registrar:

- versión de WordPress
- versión de PHP
- servidor
- base de datos
- versión de MySQL/MariaDB
- WooCommerce instalado o no
- versión de WooCommerce si está disponible
- plugins activos relevantes
- theme activo
- disponibilidad de Composer
- disponibilidad de Git
- estructura real del repositorio

La evidencia debe registrarse en `implementation-status.md` o documentación técnica relacionada.

---

## 3. Componentes Afectados

Estructura prevista:

```text id="ubh97q"
kaanbal.php
composer.json
composer.lock

src/
├── Bootstrap/
│   ├── Plugin.php
│   ├── Activator.php
│   ├── Deactivator.php
│   ├── Requirements.php
│   └── ServiceRegistry.php
│
└── Shared/
    └── Database/
        └── SchemaManager.php

tests/
├── Unit/
├── Integration/
└── Support/
```

La estructura puede ajustarse ligeramente si la inspección demuestra una alternativa más simple.

No debe ampliarse sin necesidad.

---

## 4. Bootstrap

`kaanbal.php` será responsable únicamente de:

- metadata
- direct access guard
- localizar autoload
- cargar Composer
- definir versionado mínimo necesario
- registrar activation/deactivation hooks
- iniciar `Plugin`

La lógica real vivirá fuera del archivo principal.

---

## 5. `Plugin`

Responsabilidades previstas:

- iniciar Kaanbal
- verificar compatibilidad
- registrar servicios
- ejecutar inicialización

Conceptualmente:

```text id="9ji1xl"
WordPress loads kaanbal.php
        ↓
Composer autoload
        ↓
Plugin::boot()
        ↓
Requirements
        ↓
ServiceRegistry
        ↓
Registered services
```

---

## 6. Requirements

`Requirements` encapsulará la comprobación de requisitos técnicos.

Podrá evaluar:

- PHP
- WordPress
- extensiones PHP si fueran necesarias

WooCommerce no será requisito obligatorio de SPEC-001.

Debe ser posible consultar el resultado sin provocar fatal error por utilizar APIs inexistentes.

---

## 7. Activator

Responsabilidades:

- ejecutar preparación de schema
- establecer versión inicial
- registrar infrastructure data estrictamente necesaria
- ejecutar tareas idempotentes de instalación

No debe:

- crear contenido demo
- crear cursos
- crear usuarios
- modificar WooCommerce
- eliminar datos

---

## 8. Deactivator

Responsabilidades:

- realizar cleanup temporal estrictamente necesario

Inicialmente puede ser mínimo.

No debe eliminar:

- options necesarias para upgrades
- tablas
- contenido
- información académica futura

---

## 9. Service Registry

Se utilizará un registro simple de servicios.

Objetivo:

evitar hardcoding desordenado de inicializaciones en `Plugin`.

No se implementará un container enterprise.

Interfaz conceptual:

```text id="3r2x6w"
Plugin
  ↓
ServiceRegistry
  ↓
register service
  ↓
service boot/register
```

La implementación puede usar una colección explícita de servicios.

---

## 10. Persistencia

SPEC-001 solo necesita infraestructura de schema.

No necesita crear todas las tablas definidas en `docs/data-model.md`.

Puede requerir únicamente:

- opción/version metadata
- mecanismo para ejecutar migraciones futuras

La decisión sobre si crear alguna tabla técnica debe justificarse.

---

## 11. Schema Manager

El Schema Manager debe:

- conocer la versión de schema esperada
- leer versión instalada
- determinar si requiere instalación/upgrade
- ejecutar pasos de actualización
- actualizar la versión solo después de éxito

Debe diseñarse para futuras migraciones incrementales.

No debe contener schema académico completo en SPEC-001 salvo necesidad aprobada.

---

## 12. Estrategia de Migraciones

Preferencia inicial:

una infraestructura sencilla de migraciones internas versionadas.

Ejemplo conceptual:

```text id="ur83o2"
SchemaManager
    ↓
installed version 0
target version 1
    ↓
Migration_001
    ↓
store version 1
```

Si `dbDelta()` se utiliza, debe encapsularse.

La decisión final dependerá del entorno inspeccionado.

---

## 13. Versionado

Deben distinguirse:

```text id="ely7xs"
Plugin version
Database/schema version
```

Ejemplo conceptual:

```text id="udhy9s"
KAANBAL_VERSION
KAANBAL_DB_VERSION
```

La fuente definitiva debe ser única y no duplicarse innecesariamente.

---

## 14. WooCommerce

SPEC-001 no registrará lógica funcional de pedidos.

La única consideración requerida es:

```text id="slh1dg"
WooCommerce inactive
→ Kaanbal Foundation does not crash
```

No crear adapters comerciales todavía salvo que sean estrictamente necesarios.

---

## 15. Seguridad

Controles:

- direct file access guard
- no ejecutar input de usuario
- no introducir endpoints
- no modificar Core
- no exponer información sensible
- no ejecutar migraciones desde requests arbitrarias sin control del lifecycle

El riesgo de ataque en SPEC-001 es bajo, pero el lifecycle debe ser seguro.

---

## 16. Testing

### Unit

Candidatos:

- `RequirementsTest`
- `SchemaVersionTest`
- `ServiceRegistryTest`

### Integration

Candidatos:

- `PluginLoadsTest`
- `PluginActivationTest`
- `PluginReactivationTest`
- `PluginDeactivationTest`
- `WooCommerceUnavailableTest`
- `SchemaManagerTest`

La selección definitiva dependerá del harness disponible.

---

## 17. Quality Tooling

Codex debe intentar configurar, de forma compatible con el entorno:

- PHPUnit
- Composer scripts
- PHP syntax validation
- PHPCS + WordPress Coding Standards
- PHPStan

Si una herramienta no puede configurarse razonablemente en SPEC-001 por incompatibilidad de entorno:

- no fingir que está disponible
- documentar `NOT CONFIGURED`
- explicar motivo

No debe instalarse tooling desproporcionado.

---

## 18. Comandos Deseables

Ejemplos conceptuales:

```text id="9r9m3h"
composer test
composer lint
composer cs
composer analyse
```

Los scripts reales se definirán tras inspeccionar compatibilidad.

---

## 19. Flujo de Implementación

```text id="0brw57"
1. inspeccionar entorno
2. registrar requisitos soportados
3. crear composer.json
4. configurar PSR-4
5. crear kaanbal.php
6. implementar bootstrap
7. implementar requirements
8. implementar lifecycle
9. implementar schema manager
10. implementar service registry
11. configurar testing
12. crear pruebas
13. configurar quality gate
14. ejecutar suite
15. actualizar implementation-status.md
```

---

## 20. Riesgos Técnicos

### TECH-001

WordPress testing framework puede requerir configuración adicional.

### TECH-002

PHPUnit debe elegirse según PHP real.

### TECH-003

WordPress Coding Standards puede introducir dependencias Composer adicionales.

### TECH-004

Una arquitectura demasiado abstracta en Foundation puede generar clases sin uso.

Mitigación:

mantener implementación pequeña y concreta.

---

## 21. Decisiones

### DEC-001

Namespace base:

`Kaanbal\`

### DEC-002

Slug:

`kaanbal`

### DEC-003

Text domain:

`kaanbal`

### DEC-004

Autoload:

Composer PSR-4.

### DEC-005

WooCommerce no es dependencia obligatoria para cargar SPEC-001.

### DEC-006

Desactivación no elimina datos.

### DEC-007

El código de negocio no vive en `kaanbal.php`.

---

## 22. Condición de Finalización Técnica

La implementación estará lista para auditoría cuando:

- plugin carga
- plugin activa
- plugin reactiva
- plugin desactiva
- WooCommerce puede estar ausente
- autoload funciona
- schema manager base funciona
- pruebas obligatorias configuradas pasan
- tooling disponible pasa
- documentación refleja el estado real
- `Ready for audit: Yes`