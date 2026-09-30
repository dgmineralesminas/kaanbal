Feature: Proveedores de video para lecciones Kaanbal

  Como administrador
  quiero utilizar YouTube o Vimeo en las lecciones
  para elegir dónde alojar el contenido audiovisual sin almacenar videos pesados en WordPress.

  @AC-001 @SC-001
  Scenario: Resolver proveedor YouTube
    Given YouTube está registrado como proveedor
    When Kaanbal solicita el provider youtube
    Then debe obtener un proveedor válido

  @AC-002 @SC-002
  Scenario: Resolver proveedor Vimeo
    Given Vimeo está registrado como proveedor
    When Kaanbal solicita el provider vimeo
    Then debe obtener un proveedor válido

  @AC-003 @SC-003
  Scenario: Solicitar provider desconocido
    Given un identificador de provider no registrado
    When Kaanbal intenta resolverlo
    Then debe considerarlo no soportado

  @AC-004 @SC-004
  Scenario: Normalizar URL estándar de YouTube
    Given una URL válida de youtube.com/watch
    When Kaanbal la normaliza
    Then debe obtener el video ID esperado

  @AC-005 @SC-005
  Scenario: Normalizar URL corta de YouTube
    Given una URL válida de youtu.be
    When Kaanbal la normaliza
    Then debe obtener el video ID esperado

  @AC-006 @SC-006
  Scenario: Normalizar URL de Vimeo
    Given una URL válida de Vimeo
    When Kaanbal la normaliza
    Then debe obtener el identificador numérico del video

  @AC-007 @SC-007
  Scenario: Rechazar fuente YouTube inválida
    Given el provider es youtube
    And la source no corresponde a una fuente YouTube válida
    When Kaanbal intenta normalizarla
    Then no debe producir un video válido

  @AC-008 @SC-008
  Scenario: Rechazar fuente Vimeo inválida
    Given el provider es vimeo
    And la source no corresponde a una fuente Vimeo válida
    When Kaanbal intenta normalizarla
    Then no debe producir un video válido

  @AC-009 @SC-009
  Scenario: Renderizar video YouTube
    Given una lección tiene un video YouTube válido
    When un alumno autorizado abre la lección
    Then Kaanbal debe mostrar un embed seguro de YouTube

  @AC-010 @SC-010
  Scenario: Renderizar video Vimeo
    Given una lección tiene un video Vimeo válido
    When un alumno autorizado abre la lección
    Then Kaanbal debe mostrar un embed seguro de Vimeo

  @AC-011 @SC-011
  Scenario: Rechazar HTML almacenado como source
    Given la source contiene HTML o JavaScript arbitrario
    When Kaanbal intenta utilizarla como video
    Then el contenido no debe renderizarse directamente

  @AC-012 @SC-012
  Scenario: Configurar Vimeo
    Given un administrador edita una lección
    When selecciona Vimeo y proporciona una URL válida
    Then la configuración debe guardarse correctamente

  @AC-013 @SC-013
  Scenario: Configurar YouTube
    Given un administrador edita una lección
    When selecciona YouTube y proporciona una URL válida
    Then la configuración debe guardarse correctamente

  @AC-014 @SC-014
  Scenario: Lección sin video
    Given el administrador selecciona Sin video
    When guarda la lección
    Then la lección debe continuar siendo válida

  @AC-015 @SC-015
  Scenario: Cambiar de YouTube a Vimeo
    Given una lección utiliza YouTube
    When el administrador cambia el provider a Vimeo
    And proporciona una URL Vimeo válida
    Then la lección debe utilizar Vimeo

  @AC-015 @SC-016
  Scenario: Cambiar provider con source incompatible
    Given una lección está configurada como Vimeo
    When se intenta guardar una URL YouTube como source
    Then la combinación debe rechazarse

  @AC-016 @SC-017
  Scenario: Mantener compatibilidad con lección YouTube existente
    Given existe una lección YouTube creada antes de SPEC-009
    When se actualiza Kaanbal
    Then la lección debe continuar renderizándose correctamente

  @AC-017 @SC-018
  Scenario: Player utiliza Provider Registry
    Given una lección tiene un provider soportado
    When se genera su player
    Then la implementación debe resolver el provider mediante la infraestructura común

  @AC-018 @SC-019
  Scenario: Reproducir video no completa lección
    Given una lección contiene un video YouTube o Vimeo
    When el alumno abre o reproduce la lección
    Then no debe generarse automáticamente una completion