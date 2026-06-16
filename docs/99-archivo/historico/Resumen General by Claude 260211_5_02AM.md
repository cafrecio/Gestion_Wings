Resumen General by Claude 260211_5_02AM                                                                                                                                                                                                                                                                                                                                                                                                                                                                           
  Wings es una API backend en Laravel 11 con base de datos SQLite, autenticación via Sanctum, y generación de PDFs con DomPDF. Toda la lógica de negocio vive en la capa de Services, los controllers son delegadores, y los modelos tienen   
  reglas de inmutabilidad en sus métodos boot().                                                                                                                                                                                              
                                                                                                                                                                                                                                                ---                                                                                                                                                                                                                                           1. Autenticación y Roles                                                                                                                                                                                                                                                                                                                                                                                                                                                                  
  Archivos: AuthController, EnsureAdmin middleware, modelo User

  - Login con email/password → devuelve token Sanctum
  - 2 roles: ADMIN (acceso total) y OPERATIVO (operación diaria restringida)
  - Middleware ensure.admin protege rutas /api/admin/*
  - Middleware bloqueo.caja.vieja bloquea operativos si tienen caja abierta de días anteriores

  ---
  2. CRUD Básicos (ABM Admin)

  Archivos: app/Http/Controllers/Admin/ (8 controllers)

  Entidades con CRUD completo (listar, crear, ver, editar, eliminar):
  ┌────────────┬──────────────────────────┬──────────────────────────────────────────────────────────────────────────┐
  │  Entidad   │        Controller        │                               Descripción                                │
  ├────────────┼──────────────────────────┼──────────────────────────────────────────────────────────────────────────┤
  │ Deportes   │ Admin/DeporteController  │ Disciplinas (Patín, Natación, etc.) con tipo_liquidacion (HORA/COMISION) │
  ├────────────┼──────────────────────────┼──────────────────────────────────────────────────────────────────────────┤
  │ Grupos     │ Admin/GrupoController    │ Divisiones dentro de un deporte (ej: "Patín Inicial Lunes")              │
  ├────────────┼──────────────────────────┼──────────────────────────────────────────────────────────────────────────┤
  │ Profesores │ Admin/ProfesorController │ Instructores con valor_hora o porcentaje_comision                        │
  ├────────────┼──────────────────────────┼──────────────────────────────────────────────────────────────────────────┤
  │ Alumnos    │ Admin/AlumnoController   │ Estudiantes vinculados a 1 deporte y 1 grupo                             │
  ├────────────┼──────────────────────────┼──────────────────────────────────────────────────────────────────────────┤
  │ Clases     │ Admin/ClaseController    │ Sesiones con fecha, horario y profesores asignados                       │
  ├────────────┼──────────────────────────┼──────────────────────────────────────────────────────────────────────────┤
  │ Rubros     │ Admin/RubroController    │ Categorías contables (INGRESO/EGRESO)                                    │
  ├────────────┼──────────────────────────┼──────────────────────────────────────────────────────────────────────────┤
  │ Subrubros  │ Admin/SubrubroController │ Subcategorías con permisos y flag es_reservado_sistema                   │
  ├────────────┼──────────────────────────┼──────────────────────────────────────────────────────────────────────────┤
  │ Tipos Caja │ Admin/TipoCajaController │ Tipos de caja (Chica, General, Reserva)                                  │
  └────────────┴──────────────────────────┴──────────────────────────────────────────────────────────────────────────┘
  ---
  3. Estructura Alumno → Deporte → Grupo → Plan

  Archivos: Modelos Alumno, Deporte, Grupo, GrupoPlan, AlumnoPlan

  Deporte (ej: Patín)
    └── Grupo (ej: Patín Inicial Lunes)
         └── GrupoPlan (ej: 2x/semana → $33.333)
              └── AlumnoPlan (asigna plan activo al alumno)
                   └── Alumno (1 alumno = 1 deporte, mismo DNI en otro deporte = otro registro)

  Reglas clave:
  - DNI único por deporte, no global
  - Cambio de grupo → mismo alumno. Cambio de deporte → nuevo alumno
  - Tutores automáticamente nulos si el alumno es mayor de 18

  ---
  4. Clases y Asistencias

  Archivos: ClaseController, AsistenciaController, ClaseService, modelos Clase, Asistencia, ClaseProfesor

  - Una clase puede tener múltiples profesores (N:M via clase_profesor)
  - Asistencia registrada individual o masivamente por clase
  - Validación de conflicto horario para profesores y alumnos
  - Una clase es liquidable si: NO está cancelada Y (tiene asistentes O está validada_para_liquidacion)

  ---
  5. Sistema de Pagos (Cuotas Mensuales)

  Archivos: PagoController, PagoService, modelo Pago, ReglaPrimerPago

  Flujo de pago:

  1. Se calcula el monto_base del plan activo del alumno
  2. Primer pago: se aplica descuento proporcional según fecha de alta:
    - Día 1-15 → 100%
    - Día 16-22 → 70%
    - Día 23-31 → 40%
  3. Pagos siguientes: siempre 100%
  4. Se crea el registro Pago → INMUTABLE (no se pueden modificar montos después)

  ---
  6. Sistema de Deudas

  Archivos: PagoCuotaController, PagoCuotaService, modelos DeudaCuota, PagoDeudaCuota

  Estados de deuda:

  PENDIENTE → PAGADA | CONDONADA | AJUSTADA

  Flujo de pago de deuda:

  1. Operativo o admin envía items con deuda_cuota_id + monto_aplicado
  2. Se crea un Pago y se vincula a las deudas via pivot pago_deuda_cuota
  3. Se actualiza monto_pagado en cada deuda; si alcanza el original → PAGADA
  4. Operativo: además crea movimiento en su caja operativa
  5. Admin: crea movimiento directo en cashflow

  Operaciones admin exclusivas:

  - Crear deuda manual
  - Condonar deuda (perdonar)
  - Ajustar monto de deuda

  ---
  7. Liquidaciones (Pago a Profesores)

  Archivos: LiquidacionController, LiquidacionService, LiquidacionPagoService, modelos Liquidacion, LiquidacionDetalle

  Dos tipos según deporte:

  POR HORA: total = Σ(clases_válidas × valor_hora)
  - Cada clase válida genera un detalle
  - Si hay 2 profesores en la misma clase, cada uno cobra completo

  POR COMISIÓN: total = Σ(pago_alumno × porcentaje_comision)
  - Solo alumnos que pagaron Y asistieron a clases del profesor
  - Si asistieron a clases de múltiples profesores, cada uno cobra comisión completa

  Ciclo de vida:

  ABIERTA → recalculable → CERRADA (inmutable) → PAGADA (admin registra pago → genera CashflowMovimiento)

  ---
  8. Caja Operativa (Caja Diaria)

  Archivos: CajaOperativaController, MovimientoOperativoController, CajaService, CajaResumenService, middleware BloqueoCajaViejaOperativo

  Ciclo:

  ABIERTA (auto-creada en primer movimiento del día)
    → CERRADA (operativo o admin cierra)
      → VALIDADA (admin aprueba) | RECHAZADA (admin rechaza con motivo obligatorio)

  Reglas:

  - 1 caja abierta por operativo por día
  - Si queda una caja abierta de un día anterior → BLOQUEADO hasta cerrarla
  - Cada movimiento registra: tipo_caja, subrubro, monto, usuario

  ---
  9. Cashflow (Consolidación Financiera)

  Archivos: CashflowMovimientoController, CashflowSaldoController, CashflowService, CashflowIntegracionCajaService, CashflowSaldoService

  Fuentes de movimientos cashflow:

  - Movimientos operativos (integración idempotente caja→cashflow)
  - Pagos de deuda (operativo/admin)
  - Pagos de liquidaciones (admin)
  - Entradas directas del admin

  Idempotencia:

  Usa referencia_tipo + referencia_id para evitar duplicados al convertir movimientos de caja a cashflow.

  Reportes:

  - Saldo por tipo de caja
  - Saldo acumulado a una fecha
  - Cierre de día (operativo y admin)

  ---
  10. Recibos PDF

  Archivos: ReciboController, ReciboService, templates en resources/views/pdfs/

  - Recibo de cuota: recibo-cuota.blade.php
  - Recibo de liquidación: recibo-liquidacion.blade.php
  - Se generan asincrónicamente en DB::afterCommit() para no afectar la transacción

  ---
  11. Reportes y Estado Operativo

  Archivos: CierreDiaController, OperativoController, CierreDiaResumenService, OperativoEstadoService

  - GET /api/cierres-dia → resumen diario del operativo
  - GET /api/admin/cierres-dia → resumen global de todos los operativos
  - GET /api/operativo/estado-hoy → estado actual del día (caja abierta, movimientos, etc.)

  ---
  Arquitectura en Capas

  Routes (api.php)
    → Middleware (auth:sanctum, ensure.admin, bloqueo.caja.vieja)
      → Controllers (delegan a Services)
        → Services (lógica de negocio, transacciones DB)
          → Models (Eloquent, relaciones, inmutabilidad en boot())
            → Migrations (schema SQLite, 40 migraciones)
              → FormRequests (42 clases de validación)

  ---
  Patrones de Diseño Clave

  - Inmutabilidad financiera: Pagos y liquidaciones cerradas no se pueden modificar
  - Idempotencia: Integración caja→cashflow previene duplicados
  - Transacciones: Operaciones de escritura envueltas en DB::transaction()
  - Separación de responsabilidades: Controllers → Services → Models
  - Eager loading: Previene problemas N+1