Feature: Matrículas mediante WooCommerce

  Como administrador de una plataforma Kaanbal
  quiero que la compra de productos WooCommerce conceda acceso a cursos
  para que el proceso comercial y académico estén integrados sin duplicar responsabilidades.

  @AC-001 @AC-002 @SC-001
  Scenario: Asociar varios cursos a un producto
    Given existe un producto WooCommerce
    And existen varios cursos Kaanbal
    When el administrador asocia los cursos al producto
    Then el producto debe conceder todos los cursos seleccionados
    And una misma relación producto-curso no debe duplicarse

  @AC-003 @AC-019 @SC-002
  Scenario: Configurar cursos desde un producto
    Given un usuario tiene permisos suficientes para editar el producto
    When modifica los cursos asociados
    Then las relaciones válidas deben persistirse

  @AC-019 @SC-003
  Scenario: Rechazar modificación sin permisos
    Given un usuario no tiene permisos suficientes
    When intenta modificar las relaciones producto-curso
    Then la operación debe ser rechazada

  @AC-004 @SC-004
  Scenario: Procesar producto sin cursos
    Given existe un producto sin cursos Kaanbal asociados
    When el producto forma parte de un pedido válido
    Then Kaanbal no debe crear ninguna matrícula por ese producto
    And WooCommerce debe continuar normalmente

  @AC-005 @AC-006 @SC-005
  Scenario: Crear una matrícula
    Given existe un usuario WordPress
    And existe un curso Kaanbal
    When Kaanbal concede acceso al curso
    Then debe existir una sola matrícula para el usuario y curso
    And la matrícula debe quedar activa

  @AC-007 @SC-006
  Scenario: Registrar el origen comercial
    Given una matrícula se concede mediante un item de un pedido WooCommerce
    When Kaanbal registra el acceso
    Then debe conservar la referencia del pedido
    And debe conservar la referencia del producto
    And debe conservar la referencia del item cuando esté disponible

  @AC-008 @SC-007
  Scenario: Comprar un producto que incluye varios cursos
    Given un producto concede tres cursos
    And un usuario compra ese producto
    When el pedido alcanza un estado que concede acceso
    Then el usuario debe tener matrícula en los tres cursos

  @AC-009 @SC-008
  Scenario: Comprar productos con cursos solapados
    Given un producto A concede el Curso X
    And un producto B también concede el Curso X
    And ambos forman parte del mismo pedido
    When el pedido concede acceso
    Then debe existir una sola matrícula para Curso X
    And deben conservarse las fuentes comerciales válidas

  @AC-010 @SC-009
  Scenario: Reprocesar el mismo pedido
    Given un pedido ya concedió sus cursos
    When Kaanbal procesa nuevamente el mismo pedido
    Then no debe duplicar matrículas
    And no debe duplicar fuentes equivalentes

  @AC-011 @SC-010
  Scenario: Conceder acceso al pasar a processing
    Given un pedido pertenece a un usuario WordPress válido
    And contiene productos asociados a cursos
    When el pedido cambia a processing
    Then Kaanbal debe conceder acceso a los cursos correspondientes

  @AC-012 @SC-011
  Scenario: Cambiar de processing a completed
    Given el pedido ya fue procesado en processing
    When el pedido cambia a completed
    Then no deben duplicarse matrículas
    And no deben duplicarse fuentes equivalentes

  @AC-013 @SC-012
  Scenario: Cancelar un pedido previamente concedido
    Given un pedido concedió acceso a uno o más cursos
    When el pedido cambia a cancelled
    Then las fuentes provenientes de ese pedido deben quedar revocadas

  @AC-014 @SC-013
  Scenario: Reembolsar un pedido previamente concedido
    Given un pedido concedió acceso a uno o más cursos
    When el pedido cambia a refunded
    Then las fuentes provenientes de ese pedido deben quedar revocadas

  @AC-015 @SC-014
  Scenario: Mantener acceso cuando existe otra fuente
    Given un alumno tiene dos fuentes válidas para el mismo curso
    When una de las fuentes es revocada
    Then la matrícula debe permanecer activa

  @AC-016 @SC-015
  Scenario: Revocar matrícula sin fuentes válidas
    Given un alumno tiene una matrícula activa
    And la matrícula depende de una única fuente válida
    When esa fuente es revocada
    Then la matrícula debe quedar revoked

  @AC-017 @SC-016
  Scenario: Reactivar una matrícula
    Given un alumno tiene una matrícula revoked
    When una nueva compra válida vuelve a conceder el mismo curso
    Then la matrícula existente debe volver a active
    And no debe crearse otra matrícula

  @AC-018 @SC-017
  Scenario: Cargar Kaanbal sin WooCommerce
    Given WooCommerce está desactivado
    When WordPress inicializa Kaanbal
    Then el plugin debe cargar sin errores fatales
    And la integración comercial no debe ejecutarse

  @AC-020 @SC-018
  Scenario: Rechazar curso inválido
    Given existe un producto WooCommerce
    When se intenta asociar un ID que no corresponde a un curso Kaanbal válido
    Then la relación no debe persistirse como válida

  @AC-020 @SC-019
  Scenario: Rechazar producto inválido
    Given existe un curso Kaanbal
    When se intenta crear una asociación con un ID que no corresponde a un producto WooCommerce
    Then la relación no debe persistirse como válida

  @SC-020
  Scenario: Pedido de invitado
    Given un pedido válido no está asociado a un usuario WordPress
    When el pedido alcanza processing o completed
    Then Kaanbal no debe crear una matrícula inválida
    And el procesamiento debe terminar de forma controlada

  @AC-021 @SC-021
  Scenario: Mantener el alcance académico
    Given Kaanbal procesa una compra
    When concede o revoca una matrícula
    Then no debe completar lecciones
    And no debe aprobar quizzes
    And no debe emitir certificados