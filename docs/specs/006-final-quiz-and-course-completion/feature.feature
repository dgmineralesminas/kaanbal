Feature: Quiz final y aprobación del curso

  Como alumno
  quiero presentar el quiz final cuando sea requerido
  para demostrar que cumplí los requisitos del curso.

  @AC-001 @SC-001
  Scenario: Curso sin quiz final
    Given un curso no requiere quiz final
    When se consulta su configuración
    Then el curso debe poder completarse sin presentar un quiz

  @AC-002 @SC-002
  Scenario: Curso con quiz final
    Given un curso requiere quiz final
    Then debe existir un quiz final válido asociado al curso

  @AC-003 @AC-004 @SC-003
  Scenario: Configurar pregunta single choice
    Given existe un quiz
    When el administrador crea una pregunta single choice
    Then debe poder configurar múltiples respuestas
    And exactamente una respuesta debe ser correcta

  @AC-008 @SC-004
  Scenario: Intentar presentar quiz antes de completar lecciones
    Given un alumno tiene menos de 100 por ciento de progreso
    When intenta presentar el quiz final
    Then Kaanbal debe rechazar el intento

  @AC-009 @AC-010 @SC-005
  Scenario: Presentar un quiz
    Given un alumno es elegible para presentar el quiz
    When envía sus respuestas
    Then Kaanbal debe evaluarlas del lado servidor
    And debe calcular el score

  @AC-011 @SC-006
  Scenario: Aprobar exactamente con el passing score
    Given el passing score es 80 por ciento
    When el alumno obtiene 80 por ciento
    Then el intento debe quedar passed

  @AC-012 @SC-007
  Scenario: Reprobar debajo del passing score
    Given el passing score es 80 por ciento
    When el alumno obtiene 79 por ciento
    Then el intento debe quedar failed

  @AC-013 @SC-008
  Scenario: Registrar intento
    Given un alumno presenta un quiz válido
    When termina la evaluación
    Then el intento debe quedar registrado
    And debe conservar su score y resultado

  @AC-014 @SC-009
  Scenario: Consumir todos los intentos
    Given el quiz permite 3 intentos
    And el alumno ha utilizado los 3 sin aprobar
    When intenta presentar nuevamente
    Then Kaanbal debe impedir un cuarto intento

  @AC-007 @SC-010
  Scenario: Intentos ilimitados
    Given el quiz tiene intentos ilimitados
    And el alumno todavía no aprueba
    When realiza otro intento válido
    Then Kaanbal debe permitirlo

  @AC-015 @SC-011
  Scenario: Completar curso sin quiz
    Given un curso no requiere quiz
    And el alumno completa todas las lecciones
    When se evalúa Course Completion
    Then la matrícula debe cambiar a completed

  @AC-016 @SC-012
  Scenario: No completar curso con quiz pendiente
    Given un curso requiere quiz
    And el alumno completó todas las lecciones
    And todavía no aprobó el quiz
    When se evalúa Course Completion
    Then la matrícula no debe cambiar a completed

  @AC-017 @SC-013
  Scenario: Completar curso al aprobar quiz
    Given el alumno completó todas las lecciones
    And el curso requiere quiz
    When el alumno aprueba el quiz
    Then la matrícula debe cambiar a completed

  @AC-018 @AC-019 @SC-014
  Scenario: Registrar completion una sola vez
    Given un alumno acaba de cumplir todos los requisitos
    When Kaanbal evalúa Course Completion múltiples veces
    Then la matrícula debe permanecer completed
    And completed_at debe conservar la fecha válida original

  @AC-020 @SC-015
  Scenario: Continuar accediendo después de aprobar
    Given la matrícula está completed
    When el alumno vuelve al curso
    Then debe conservar acceso al contenido

  @AC-021 @AC-022 @SC-016
  Scenario: Aprobar curso con certificado
    Given el curso está configurado para entregar certificado posteriormente
    When el alumno aprueba el curso
    Then Kaanbal debe informar que aprobó
    And debe indicar que posteriormente se comunicarán con él para hacerle llegar su certificado

  @AC-024 @SC-017
  Scenario: Aprobar curso sin certificado
    Given el curso no ofrece certificado
    When el alumno aprueba
    Then debe mostrarse un mensaje de aprobación
    And no debe prometerse un certificado

  @AC-023 @SC-018
  Scenario: No descargar certificado
    Given un alumno aprobó un curso
    When visualiza el estado final
    Then Kaanbal no debe generar un PDF de certificado
    And no debe ofrecer una descarga de certificado

  @AC-025 @SC-019
  Scenario: Quiz no modifica porcentaje
    Given el alumno tiene 100 por ciento de progreso en lecciones
    When presenta o aprueba el quiz
    Then el porcentaje de progreso de lecciones debe continuar siendo 100 por ciento

  @AC-026 @SC-020
  Scenario: Impedir intento en nombre de otro usuario
    Given el Usuario A está autenticado
    When intenta registrar un quiz attempt para el Usuario B
    Then la operación debe ser rechazada

  @AC-027 @SC-021
  Scenario: Impedir quiz perteneciente a otro curso
    Given el alumno tiene acceso al Curso A
    And el quiz pertenece al Curso B
    When intenta presentar el quiz mediante el contexto del Curso A
    Then Kaanbal debe rechazar la operación