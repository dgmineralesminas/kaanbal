Feature: Fundación del plugin Kaanbal

  Como administrador de WordPress
  quiero que Kaanbal disponga de una infraestructura base estable
  para que las funcionalidades LMS futuras puedan construirse sobre ella.

  @AC-001 @AC-002 @SC-001
  Scenario: Activar Kaanbal en un entorno compatible
    Given WordPress está instalado en una versión compatible
    And PHP cumple la versión mínima soportada
    And Kaanbal está instalado pero inactivo
    When un administrador activa Kaanbal
    Then WordPress debe activar el plugin sin errores fatales
    And Kaanbal debe quedar disponible para inicialización

  @AC-003 @SC-002
  Scenario: Reactivar Kaanbal
    Given Kaanbal fue activado anteriormente
    And su infraestructura persistente ya existe
    When Kaanbal se activa nuevamente
    Then la activación debe completarse sin errores
    And no deben duplicarse recursos persistentes

  @AC-004 @SC-003
  Scenario: Desactivar Kaanbal
    Given Kaanbal está activo
    When un administrador desactiva el plugin
    Then la desactivación debe completarse sin errores fatales
    And los datos persistentes de Kaanbal no deben eliminarse

  @AC-005 @SC-004
  Scenario: Inicializar el bootstrap
    Given Kaanbal está activo
    When WordPress carga los plugins activos
    Then el bootstrap principal de Kaanbal debe inicializarse correctamente

  @AC-006 @SC-005
  Scenario: Cargar clases mediante Composer
    Given Composer autoload está disponible
    When Kaanbal necesita una clase bajo el namespace Kaanbal
    Then la clase debe resolverse mediante PSR-4
    And no debe requerir includes manuales dispersos

  @AC-007 @SC-006
  Scenario: Evitar ejecución directa de archivos protegidos
    Given un archivo PHP interno protegido de Kaanbal
    When se intenta ejecutar fuera del contexto esperado de WordPress
    Then no debe ejecutar comportamiento sensible

  @AC-008 @SC-007
  Scenario: Cargar Kaanbal sin WooCommerce
    Given WooCommerce no está instalado o está desactivado
    And Kaanbal está activo
    When WordPress inicializa Kaanbal
    Then Kaanbal Foundation debe cargar sin errores fatales

  @AC-009 @SC-008
  Scenario: Detectar un entorno incompatible
    Given el entorno no cumple uno de los requisitos mínimos configurados
    When Kaanbal evalúa los requisitos técnicos
    Then debe poder identificar la incompatibilidad
    And no debe continuar silenciosamente como si el entorno fuera compatible

  @AC-010 @SC-009
  Scenario: Consultar versión del plugin
    Given Kaanbal está instalado
    When un componente interno consulta la versión del plugin
    Then debe obtenerla desde la fuente técnica definida por Kaanbal

  @AC-011 @AC-012 @SC-010
  Scenario: Preparar el schema de forma idempotente
    Given Kaanbal dispone de un Schema Manager
    When el proceso de instalación de schema se ejecuta más de una vez
    Then debe conservar una versión de schema consistente
    And no debe duplicar recursos persistentes

  @AC-013 @SC-011
  Scenario: Registrar servicios
    Given el bootstrap de Kaanbal se está inicializando
    When registra sus servicios base
    Then cada servicio configurado debe inicializarse de forma predecible

  @AC-014 @SC-012
  Scenario: Ejecutar pruebas automatizadas
    Given la infraestructura de testing está instalada
    When se ejecuta la suite base de Kaanbal
    Then las pruebas configuradas deben poder ejecutarse
    And sus resultados deben ser reportados de forma verificable

  @AC-015 @SC-013
  Scenario: Ejecutar el quality gate
    Given la implementación de SPEC-001 está terminada
    When Codex ejecuta los checks configurados del proyecto
    Then cada check ejecutable debe reportar su resultado real
    And ningún check no ejecutado debe declararse como PASS