Feature: Progreso académico del alumno

  Como alumno matriculado
  quiero marcar las lecciones que he completado
  para conocer mi avance dentro del curso.

  @AC-001 @SC-001
  Scenario: Marcar una lección como completada
    Given un alumno tiene acceso al curso
    And la lección pertenece al curso
    And la lección no está completada
    When el alumno marca la lección como completada
    Then Kaanbal debe registrar la completion
    And la lección debe quedar identificada como completada

  @AC-002 @SC-002
  Scenario: Abrir una lección no la completa
    Given una lección está pendiente
    When el alumno abre la lección
    Then no debe crearse ningún registro de completion

  @AC-003 @AC-016 @SC-003
  Scenario: Completar dos veces la misma lección
    Given la lección ya está completada por el alumno
    When vuelve a solicitar completar la misma lección
    Then no debe crearse otro registro
    And la operación debe terminar de forma segura

  @AC-004 @SC-004
  Scenario: Intentar completar sin acceso
    Given un usuario no tiene acceso al curso
    When intenta completar una lección del curso
    Then la operación debe ser rechazada

  @AC-005 @SC-005
  Scenario: Intentar completar una lección de otro curso
    Given un alumno tiene acceso al Curso A
    And la lección pertenece al Curso B
    When intenta completar la lección usando el contexto del Curso A
    Then Kaanbal debe rechazar la operación

  @AC-006 @SC-006
  Scenario: Intentar modificar progreso de otro usuario
    Given el Usuario A está autenticado
    When intenta registrar completion para el Usuario B
    Then Kaanbal debe impedir la operación
    And el progreso del Usuario B debe permanecer sin cambios

  @AC-007 @SC-007
  Scenario: Consultar una lección completada
    Given el alumno completó una lección
    When Kaanbal consulta su estado
    Then debe devolver completed

  @AC-008 @SC-008
  Scenario: Calcular progreso parcial
    Given un curso tiene 10 lecciones
    And el alumno completó 3
    When Kaanbal calcula el progreso
    Then el resultado debe ser 30 por ciento

  @AC-009 @SC-009
  Scenario: Completar todas las lecciones
    Given un curso tiene 10 lecciones
    And el alumno completó las 10
    When se calcula su progreso
    Then debe ser 100 por ciento

  @AC-010 @SC-010
  Scenario: Curso sin lecciones
    Given un curso no tiene lecciones
    When Kaanbal calcula el progreso
    Then debe devolver 0 por ciento
    And no debe producir división por cero

  @AC-011 @SC-011
  Scenario: El quiz no afecta el porcentaje
    Given el alumno tiene progreso de lecciones
    And existe un quiz final
    When Kaanbal calcula el porcentaje de progreso
    Then debe considerar únicamente las lecciones

  @AC-012 @AC-013 @SC-012
  Scenario: Mostrar progreso en el curso
    Given un alumno tiene algunas lecciones completadas
    When abre el curso
    Then debe poder distinguir lecciones completadas y pendientes
    And debe visualizar su porcentaje de progreso

  @AC-014 @AC-015 @SC-013
  Scenario: Mostrar estado en una lección pendiente
    Given una lección no está completada
    When el alumno abre la lección
    Then debe mostrarse como pendiente
    And debe existir una acción para marcarla como completada

  @AC-014 @AC-016 @SC-014
  Scenario: Mostrar una lección ya completada
    Given el alumno completó la lección
    When abre la lección
    Then debe mostrarse como completada

  @AC-017 @SC-015
  Scenario: Añadir nueva lección a curso previamente completado
    Given el curso tiene 10 lecciones
    And el alumno completó las 10
    And su progreso actual es 100 por ciento
    When se agrega una nueva lección al curriculum
    Then el progreso debe recalcularse usando 11 lecciones
    And ya no debe ser 100 por ciento

  @AC-018 @SC-016
  Scenario: Retirar una lección completada del curriculum
    Given un alumno tiene una completion para una lección
    When la lección deja de pertenecer al curriculum actual
    Then esa completion no debe contar en el porcentaje actual

  @AC-019 @SC-017
  Scenario: Mantener progreso independiente
    Given dos alumnos están matriculados en el mismo curso
    And el Usuario A completa una lección
    When se consulta el progreso del Usuario B
    Then la lección no debe aparecer completada para el Usuario B

  @AC-020 @SC-018
  Scenario: Alcanzar 100 por ciento sin completar matrícula
    Given un alumno completa todas las lecciones
    When su progreso alcanza 100 por ciento
    Then Kaanbal no debe cambiar automáticamente la matrícula a completed