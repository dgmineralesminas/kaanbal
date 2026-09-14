Feature: Cursos y temarios de Kaanbal

  Como administrador
  quiero crear cursos organizados en módulos y lecciones
  para poder construir el contenido educativo que consumirán los alumnos.

  @AC-001 @AC-002 @SC-001
  Scenario: Crear un curso
    Given un usuario tiene permisos para administrar cursos
    When crea un nuevo curso con título, duración e instructor
    Then el curso debe guardarse correctamente
    And debe poder recuperarse desde WordPress

  @AC-003 @AC-004 @SC-002
  Scenario: Asociar un módulo a un curso
    Given existe un curso
    And existe un módulo
    When el administrador asocia el módulo al curso
    Then el módulo debe pertenecer a ese curso

  @AC-005 @SC-003
  Scenario: Recuperar módulos en orden
    Given un curso tiene varios módulos con un orden configurado
    When Kaanbal recupera los módulos del curso
    Then debe devolverlos en el orden configurado

  @AC-006 @AC-007 @SC-004
  Scenario: Asociar una lección a un módulo
    Given existe un módulo
    And existe una lección
    When el administrador asocia la lección al módulo
    Then la lección debe pertenecer a ese módulo

  @AC-008 @SC-005
  Scenario: Recuperar lecciones en orden
    Given un módulo tiene varias lecciones con un orden configurado
    When Kaanbal recupera sus lecciones
    Then debe devolverlas en el orden configurado

  @AC-009 @SC-006
  Scenario: Configurar video de YouTube
    Given existe una lección
    When el administrador guarda una fuente válida de YouTube
    Then Kaanbal debe reconocer el proveedor como youtube
    And debe almacenar una representación válida de la fuente

  @AC-010 @SC-007
  Scenario: Rechazar proveedor desconocido
    Given existe una lección
    When se intenta guardar un proveedor de video no soportado
    Then Kaanbal no debe tratarlo como un proveedor válido

  @AC-011 @SC-008
  Scenario: Obtener el temario completo
    Given un curso tiene módulos
    And sus módulos tienen lecciones
    When se solicita el temario del curso
    Then Kaanbal debe devolver los módulos ordenados
    And cada módulo debe contener sus lecciones ordenadas

  @AC-012 @SC-009
  Scenario: Resolver el curso de una lección
    Given existe una lección asociada a un módulo
    And el módulo está asociado a un curso
    When Kaanbal resuelve la jerarquía de la lección
    Then debe identificar el módulo
    And debe identificar el curso correspondiente

  @AC-013 @SC-010
  Scenario: Rechazar relación con curso inexistente
    Given existe un módulo
    When se intenta asociar a un identificador que no corresponde a un curso válido
    Then la relación no debe persistirse como válida

  @AC-013 @SC-011
  Scenario: Rechazar relación con módulo inexistente
    Given existe una lección
    When se intenta asociar a un identificador que no corresponde a un módulo válido
    Then la relación no debe persistirse como válida

  @AC-014 @SC-012
  Scenario: Crear curso sin módulos
    Given un administrador crea un curso
    When guarda el curso sin módulos
    Then el curso debe ser válido

  @AC-015 @SC-013
  Scenario: Crear módulo sin lecciones
    Given existe un curso
    When el administrador crea un módulo sin lecciones
    Then el módulo debe ser válido

  @AC-016 @SC-014
  Scenario: Impedir edición sin permisos
    Given un usuario no posee la capability necesaria
    When intenta modificar metadata o relaciones de Kaanbal
    Then la operación debe ser rechazada

  @AC-017 @SC-015
  Scenario: Mantener la SPEC dentro de alcance
    Given la implementación de Courses and Curriculum
    When se inspecciona su comportamiento
    Then no debe crear matrículas
    And no debe registrar progreso del alumno
    And no debe ejecutar quizzes
    And no debe emitir certificados