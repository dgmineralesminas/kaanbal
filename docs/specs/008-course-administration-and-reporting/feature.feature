Feature: Administración y reporte de alumnos por curso

  Como administrador
  quiero consultar el avance de los alumnos por curso
  para conocer quién está cursando y quién ya aprobó.

  @AC-001 @SC-001
  Scenario: Administrador consulta los reportes
    Given un usuario tiene permisos administrativos de Kaanbal
    When abre la sección de reportes
    Then debe poder consultar los cursos

  @AC-002 @AC-024 @SC-002
  Scenario: Usuario sin permisos intenta consultar reportes
    Given un usuario no tiene la capability necesaria
    When intenta abrir la sección administrativa
    Then Kaanbal debe rechazar el acceso
    And no debe revelar información privada de alumnos

  @AC-003 @AC-004 @AC-005 @AC-006 @AC-007 @SC-003
  Scenario: Mostrar resumen de un curso
    Given existe un curso con matrículas active, completed y revoked
    When el administrador consulta el listado
    Then debe visualizar el total de alumnos
    And el total En curso
    And el total Aprobado
    And el total Revocado

  @AC-008 @AC-009 @SC-004
  Scenario: Abrir detalle de curso
    Given existe un curso con alumnos
    When el administrador abre su detalle
    Then deben mostrarse únicamente alumnos relacionados con ese curso

  @AC-010 @SC-005
  Scenario: Mostrar identidad del alumno
    Given existe una matrícula válida
    When se muestra la fila del alumno
    Then debe mostrarse su nombre
    And debe mostrarse su email cuando esté disponible

  @AC-011 @SC-006
  Scenario: Mostrar estado académico
    Given existen alumnos active, completed y revoked
    When se muestra el reporte
    Then active debe aparecer como En curso
    And completed debe aparecer como Aprobado
    And revoked debe aparecer como Revocado

  @AC-012 @SC-007
  Scenario: Mostrar progreso individual
    Given un alumno tiene progreso registrado
    When aparece en el reporte
    Then debe mostrarse su número de lecciones completadas
    And el total
    And su porcentaje

  @AC-013 @SC-008
  Scenario: Alumno con cien por ciento pero quiz pendiente
    Given el alumno tiene 100 por ciento de lecciones
    And el curso requiere quiz
    And su Enrollment continúa active
    When aparece en el reporte
    Then debe mostrarse En curso
    And no debe mostrarse como Aprobado

  @AC-014 @SC-009
  Scenario: Curso sin quiz
    Given el curso no requiere quiz
    When se muestra el detalle
    Then el estado de quiz debe indicar No aplica

  @AC-015 @SC-010
  Scenario: Mostrar quiz aprobado
    Given un alumno aprobó el quiz final
    When aparece en el detalle
    Then el quiz debe mostrarse como aprobado

  @AC-016 @SC-011
  Scenario: Mostrar quiz no aprobado
    Given un alumno presentó el quiz pero no lo aprobó
    When aparece en el detalle
    Then el reporte debe reflejar un estado no aprobado

  @AC-017 @SC-012
  Scenario: Mostrar intentos usados
    Given el quiz tiene intentos limitados
    And el alumno ha utilizado algunos intentos
    When aparece en el reporte
    Then debe mostrarse una cantidad coherente de intentos utilizados

  @AC-018 @SC-013
  Scenario: Mostrar fecha de aprobación
    Given un alumno tiene Enrollment completed
    When aparece en el reporte
    Then debe mostrarse completed_at cuando esté disponible

  @AC-019 @SC-014
  Scenario: Buscar alumno por email
    Given varios alumnos pertenecen al curso
    When el administrador busca un email
    Then solo deben aparecer coincidencias dentro de ese curso

  @AC-020 @SC-015
  Scenario: Filtrar aprobados
    Given el curso tiene alumnos con distintos estados
    When el administrador filtra Aprobados
    Then solo deben mostrarse Enrollment completed

  @AC-021 @SC-016
  Scenario: Paginar alumnos
    Given el curso tiene más alumnos que el tamaño de página
    When el administrador navega a la siguiente página
    Then deben mostrarse los registros correspondientes
    And los filtros deben conservarse

  @AC-022 @SC-017
  Scenario: Calcular progreso promedio
    Given el curso tiene alumnos activos
    When se calcula el progreso promedio
    Then debe utilizarse la fórmula documentada

  @AC-023 @SC-018
  Scenario: Abrir reporte no cambia estado académico
    Given existen alumnos con progreso e intentos
    When el administrador abre o filtra el reporte
    Then no debe modificarse ningún Enrollment
    And no debe modificarse progreso
    And no deben crearse Quiz Attempts

  @AC-025 @SC-019
  Scenario: Solicitar curso inexistente
    Given el ID no corresponde a un curso válido
    When se solicita su reporte
    Then Kaanbal debe responder de forma segura