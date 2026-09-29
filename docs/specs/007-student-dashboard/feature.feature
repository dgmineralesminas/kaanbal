Feature: Dashboard del alumno

  Como alumno
  quiero ver mis cursos y su estado
  para saber qué estoy cursando, cuánto he avanzado y qué cursos he aprobado.

  @AC-001 @AC-002 @SC-001
  Scenario: Acceder al dashboard
    Given un alumno está autenticado
    When abre su dashboard
    Then debe visualizar su información académica

  @AC-002 @SC-002
  Scenario: Visitante intenta acceder
    Given el usuario no está autenticado
    When intenta abrir el dashboard
    Then Kaanbal debe impedir el acceso a información académica privada

  @AC-003 @AC-008 @SC-003
  Scenario: Mostrar curso activo
    Given un alumno tiene una matrícula active
    When abre el dashboard
    Then el curso debe aparecer
    And debe mostrarse como En curso

  @AC-004 @AC-009 @SC-004
  Scenario: Mostrar curso aprobado
    Given un alumno tiene una matrícula completed
    When abre el dashboard
    Then el curso debe aparecer
    And debe mostrarse como Aprobado

  @AC-005 @SC-005
  Scenario: No mostrar curso revocado como accesible
    Given un alumno tiene una matrícula revoked
    When abre el dashboard
    Then el curso no debe mostrarse como curso disponible normalmente

  @AC-006 @AC-007 @SC-006
  Scenario: Mostrar progreso
    Given un alumno tiene un curso activo
    And ha completado algunas lecciones
    When abre el dashboard
    Then debe visualizar lecciones completadas
    And lecciones totales
    And porcentaje de progreso

  @AC-010 @SC-007
  Scenario: Continuar un curso
    Given un curso está En curso
    When el alumno selecciona Continuar curso
    Then debe poder acceder al curso

  @AC-011 @SC-008
  Scenario: Volver a un curso aprobado
    Given un curso está Aprobado
    When el alumno selecciona Ver curso
    Then debe continuar teniendo acceso

  @AC-012 @SC-009
  Scenario: Curso sin quiz
    Given un curso no requiere quiz
    When se muestra en el dashboard
    Then no debe aparecer un quiz pendiente

  @AC-013 @SC-010
  Scenario: Quiz todavía bloqueado
    Given el curso requiere quiz
    And el alumno tiene menos de 100 por ciento de progreso
    When abre el dashboard
    Then debe indicarse que primero debe completar las lecciones

  @AC-014 @SC-011
  Scenario: Quiz disponible
    Given el alumno tiene 100 por ciento de progreso
    And el quiz es obligatorio
    And todavía no está aprobado
    And existen intentos disponibles
    When abre el dashboard
    Then debe indicarse que el quiz está disponible

  @AC-015 @SC-012
  Scenario: Quiz aprobado
    Given el alumno aprobó el quiz final
    When abre el dashboard
    Then el quiz debe aparecer como aprobado

  @AC-016 @SC-013
  Scenario: Mostrar intentos restantes
    Given el quiz permite una cantidad limitada de intentos
    And todavía existen intentos disponibles
    When abre el dashboard
    Then debe mostrarse un contador coherente de intentos restantes

  @AC-017 @SC-014
  Scenario: Intentos agotados
    Given el alumno utilizó todos los intentos permitidos
    And no aprobó el quiz
    When abre el dashboard
    Then debe indicarse que los intentos están agotados

  @AC-019 @AC-020 @SC-015
  Scenario: Curso aprobado con certificado
    Given el alumno aprobó un curso
    And el curso ofrece certificado
    When abre el dashboard
    Then debe informarse que posteriormente se comunicarán con él para entregarle su certificado
    And no debe existir un botón para descargarlo

  @AC-021 @SC-016
  Scenario: Curso aprobado sin certificado
    Given el alumno aprobó un curso sin certificado
    When abre el dashboard
    Then debe aparecer como Aprobado
    And no debe mostrarse un mensaje de entrega de certificado

  @AC-022 @SC-017
  Scenario: Alumno sin cursos
    Given un alumno no tiene matrículas disponibles
    When abre el dashboard
    Then debe mostrarse un estado vacío válido

  @AC-023 @SC-018
  Scenario: Intentar consultar otro usuario
    Given el Usuario A está autenticado
    When manipula la request intentando consultar los cursos del Usuario B
    Then Kaanbal no debe revelar información del Usuario B

  @AC-024 @SC-019
  Scenario: Abrir dashboard no modifica estado
    Given un alumno tiene cursos y progreso
    When abre el dashboard
    Then Kaanbal no debe crear completions
    And no debe cambiar Enrollment
    And no debe crear Quiz Attempts
