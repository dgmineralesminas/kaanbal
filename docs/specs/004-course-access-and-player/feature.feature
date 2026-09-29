# Archivo: `docs/specs/004-course-access-and-player/feature.feature`

```gherkin
Feature: Acceso y reproductor de cursos Kaanbal

  Como alumno matriculado
  quiero acceder al contenido de mis cursos
  para consultar libremente sus módulos, lecciones y videos.

  @AC-001 @SC-001
  Scenario: Acceder con matrícula activa
    Given un usuario tiene una matrícula active para un curso
    When solicita el curso
    Then Kaanbal debe conceder acceso

  @AC-002 @SC-002
  Scenario: Acceder con curso completado
    Given un usuario tiene una matrícula completed
    When solicita el curso
    Then Kaanbal debe conceder acceso

  @AC-003 @SC-003
  Scenario: Rechazar matrícula revocada
    Given un usuario tiene una matrícula revoked
    When solicita el curso
    Then Kaanbal debe negar acceso al contenido protegido

  @AC-004 @SC-004
  Scenario: Rechazar usuario sin matrícula
    Given un usuario está autenticado
    And no tiene matrícula válida para el curso
    When solicita contenido protegido
    Then Kaanbal debe negar acceso

  @AC-005 @SC-005
  Scenario: Rechazar visitante
    Given el usuario no está autenticado
    When solicita contenido protegido
    Then Kaanbal no debe revelar el contenido

  @AC-006 @AC-007 @SC-006
  Scenario: Mostrar curso a alumno autorizado
    Given un usuario tiene acceso al curso
    When abre la vista del curso
    Then debe visualizar información del curso
    And debe visualizar los módulos ordenados
    And debe visualizar las lecciones ordenadas

  @AC-008 @SC-007
  Scenario: Navegar libremente
    Given un alumno tiene acceso al curso
    And existen varias lecciones
    When abre directamente cualquier lección válida
    Then debe poder consultar la lección
    And no debe requerirse completar las anteriores

  @AC-009 @SC-008
  Scenario: Mostrar una lección
    Given un alumno tiene acceso al curso
    And la lección pertenece al curso
    When abre la lección
    Then debe visualizar el título
    And debe visualizar su contenido
    And debe conocer el módulo y curso correspondientes

  @AC-010 @SC-009
  Scenario: Reproducir una lección de YouTube
    Given una lección tiene un video YouTube válido
    When un alumno autorizado abre la lección
    Then Kaanbal debe generar un embed seguro de YouTube

  @AC-011 @SC-010
  Scenario: Mostrar lección sin video
    Given una lección no tiene video configurado
    When un alumno autorizado la abre
    Then la lección debe mostrarse sin errores
    And su contenido textual debe seguir disponible

  @AC-012 @AC-013 @SC-011
  Scenario: Navegar entre lecciones
    Given una lección se encuentra entre otras dos lecciones
    When se genera su navegación
    Then Kaanbal debe identificar la lección anterior
    And debe identificar la lección siguiente

  @AC-014 @SC-012
  Scenario: Primera lección
    Given una lección es la primera del curriculum
    When se genera su navegación
    Then no debe existir una lección anterior

  @AC-015 @SC-013
  Scenario: Última lección
    Given una lección es la última del curriculum
    When se genera su navegación
    Then no debe existir una siguiente lección

  @AC-016 @AC-022 @SC-014
  Scenario: Intentar acceder a una lección de otro curso
    Given un usuario tiene acceso al Curso A
    And una lección pertenece al Curso B
    When intenta acceder a esa lección utilizando el contexto del Curso A
    Then Kaanbal debe rechazar la relación
    And no debe revelar el contenido protegido

  @AC-017 @SC-015
  Scenario: Solicitar curso inexistente
    Given el identificador no corresponde a un curso válido
    When se solicita el curso
    Then Kaanbal debe responder de forma segura

  @AC-018 @SC-016
  Scenario: Solicitar lección inexistente
    Given el identificador no corresponde a una lección válida
    When se solicita la lección
    Then Kaanbal debe responder de forma segura

  @AC-019 @SC-017
  Scenario: Mostrar curso sin módulos
    Given un alumno tiene acceso a un curso sin módulos
    When abre el curso
    Then la página debe mostrarse sin errores

  @AC-020 @SC-018
  Scenario: Mostrar módulo sin lecciones
    Given un curso contiene un módulo vacío
    When un alumno abre el curso
    Then el módulo puede mostrarse sin provocar errores

  @AC-021 @SC-019
  Scenario: Abrir una lección no registra progreso
    Given un alumno tiene acceso a una lección
    When abre la lección
    Then Kaanbal no debe marcarla como completada
    And no debe crear progreso académico
```
