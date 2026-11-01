# generar_rutinas.py — arma las rutinas de los días 48 a 54 y las cuentas de plata hechas a mano, antes de simular.
# Las cuentas no consultan Wings: salen de esta lista de operaciones. Uso: python -X utf8 -I generar_rutinas.py
import json, os
H = os.path.dirname(os.path.abspath(__file__))
RUT = os.path.join(H, '..', '..', 'rutina')
SA, RO, PA, AD = 'Sandra Vidal', 'Rocío Paz', 'Pablo Ledesma', 'Admin Prueba'
LU, MA, HE = 'Lucía Gaitán', 'Mariela Ocampo', 'Hernán Quintana'
EF, MP, GA, NA = 'Efectivo', 'Mercado Pago', 'Banco Galicia', 'Banco Nación'
PPAT, INTE, AVA, FED = 'Patín — Principiantes', 'Patín — Intermedias', 'Patín — Avanzadas', 'Patín — Federadas'
FPRI, FAVA, ADU = 'Fútbol — Principiantes', 'Fútbol — Avanzadas', 'Patín — Adultos'


def mov(como, sub, medio, monto, obs):
    return {'como': como, 'accion': 'movimiento', 'subrubro': sub, 'medio': medio, 'monto': str(monto), 'obs': obs}


def torneo(como, grupo, importe, familias, mp=()):
    return [mov(como, 'Inscripciones', MP if f in mp else EF, importe, f'Torneo de diciembre, {grupo}: familia {f}') for f in familias]


def lista(como, grupo, hora, ausentes=(), motivos=None):
    p = {'como': como, 'accion': 'lista', 'grupo': grupo, 'hora': hora, 'ausentes': list(ausentes)}
    if motivos:
        p['motivos'] = motivos
    return p


def cobra(como, alumno, medio, monto, periodo='2026-12'):
    return {'como': como, 'accion': 'cobrar', 'alumno': alumno, 'medio': medio, 'periodos': [periodo], 'montos': {periodo: str(monto)}}


def escribir(nombre, dia, descripcion, pasos):
    with open(os.path.join(RUT, nombre), 'w', encoding='utf-8', newline='\n') as f:
        json.dump({'dia': dia, 'descripcion': descripcion, 'pasos': pasos}, f, ensure_ascii=False, indent=1)
        f.write('\n')


def abrir(como):
    return {'como': como, 'accion': 'abrir_caja'}


def noche(como, dia, descripcion, validar=True, cambio=None):
    cierre = {'como': como, 'accion': 'cerrar_caja'}
    if cambio is not None:
        cierre['cambio'] = cambio
    pasos = [cierre]
    if validar:
        pasos.append({'como': AD, 'accion': 'validar_caja', 'de': como})
    escribir(f'dia-{dia}-noche.json', {48: '2026-11-26', 49: '2026-11-27', 50: '2026-11-28', 52: '2026-11-30', 53: '2026-12-01', 54: '2026-12-02', 55: '2026-12-03', 56: '2026-12-04', 57: '2026-12-05'}[dia], descripcion, pasos)


# Día 48, jueves 26/11
escribir('dia-48-manana.json', '2026-11-26', 'Día 48, jueves 26/11: Sandra abre; Fútbol Avanzadas paga el torneo; remera y gastos; listas de Avanzadas, Federadas y Adultos. Fútbol Avanzadas la toma Hernán por pantalla.', [
    abrir(SA), *torneo(SA, 'Fútbol Avanzadas', 3000, ['Diaz', 'Fernandez', 'Ferrari', 'Gomez', 'Molina'], mp=['Gomez']),
    mov(SA, 'Indumentaria institucional', EF, 18000, 'Remera del club: Rossi'),
    mov(SA, 'Limpieza', EF, 3200, 'Trapos y lavandina'), mov(SA, 'Librería', EF, 1500, 'Cuadernos de asistencia'),
    mov(SA, 'Insumos Varios', EF, 2600, 'Pilas para el silbato electrónico'), mov(SA, 'Botiquín', EF, 1700, 'Curitas'),
    mov(SA, 'Reparaciones menores', EF, 4800, 'Cerradura del vestuario'),
    lista(LU, AVA, '17:00', ['Suarez, Valentina', 'Pereyra, Zoe']), lista(MA, FED, '18:00', ['Rossi, Olivia']),
    lista(MA, ADU, '20:30', ['Luna, Joaquina', 'Cabrera, Bruno']),
])
noche(SA, 48, 'Día 48, noche: Sandra cierra con lo esperado y deja $10.000; el dueño valida.')
# Día 49, viernes 27/11
escribir('dia-49-manana.json', '2026-11-27', 'Día 49, viernes 27/11: Rocío abre; Adultos paga el torneo; remeras y gastos; lista de Intermedias.', [
    abrir(RO), *torneo(RO, 'Patín Adultos', 3000, ['Funes', 'Ibañez']),
    mov(RO, 'Indumentaria institucional', EF, 18000, 'Remera del club: Cabrera'), mov(RO, 'Indumentaria institucional', MP, 18000, 'Remera del club: Navarro'),
    mov(RO, 'Limpieza', EF, 2900, 'Detergente'), mov(RO, 'Librería', EF, 1100, 'Sobres para recibos'),
    mov(RO, 'Insumos Varios', EF, 3400, 'Conos de la cancha'), mov(RO, 'Botiquín', EF, 2200, 'Vendas'),
    lista(LU, INTE, '16:00', ['Navarro, Alma', 'Villalba, Emma', 'Sosa, Rocio']),
])
escribir('dia-49-noche.json', '2026-11-27', 'Día 49, noche: el dueño valida la caja que Rocío cerró por pantalla.', [{'como': AD, 'accion': 'validar_caja', 'de': RO}])
# Día 50, sábado 28/11
escribir('dia-50-manana.json', '2026-11-28', 'Día 50, sábado 28/11: Pablo, ya con su caja abierta, carga el torneo que falta, una remera y gastos.', [
    *torneo(PA, 'Fútbol Infantiles', 3000, ['Acuña']),
    mov(PA, 'Indumentaria institucional', EF, 18000, 'Remera del club: Luna'),
    mov(PA, 'Limpieza', EF, 2500, 'Bolsas de residuos'), mov(PA, 'Librería', EF, 900, 'Marcadores'), mov(PA, 'Insumos Varios', EF, 4100, 'Pelotas: inflador'),
])
noche(PA, 50, 'Día 50, noche: Pablo cierra con lo esperado y deja $10.000; el dueño valida.')
# Día 52, lunes 30/11
escribir('dia-52-manana.json', '2026-11-30', 'Día 52, lunes 30/11: Sandra abre; último día del mes; gastos; lista de Patín Principiantes. Hernán y Lucía se olvidan de las suyas.', [
    abrir(SA), mov(SA, 'Indumentaria institucional', EF, 18000, 'Remera del club: Romero'),
    mov(SA, 'Limpieza', EF, 3600, 'Lavandina y escobillón'), mov(SA, 'Librería', EF, 1250.5, 'Fotocopias de la planilla mensual'),
    mov(SA, 'Insumos Varios', EF, 2800, 'Cinta de piso'), mov(SA, 'Botiquín', EF, 1400, 'Alcohol'),
    lista(LU, PPAT, '16:00', ['Ramos, Pilar', 'Godoy, Valentino', 'Ruiz, Thiago']),
])
noche(SA, 52, 'Día 52, noche: Sandra cierra y deja $10.000. El dueño valida recién el 1/12.', validar=False)
# Día 53, martes 1/12
escribir('dia-53-manana.json', '2026-12-01', 'Día 53, martes 1/12: Pablo abre; arranca la cobranza de diciembre; gastos; las listas de Avanzadas, Federadas y Adultos.', [
    abrir(PA),
    cobra(PA, 'Acosta, Victoria', EF, 30000), cobra(PA, 'Flores, Renata', EF, 30000), cobra(PA, 'Gutierrez, Olivia', MP, 44000), cobra(PA, 'Gutierrez, Paulina', EF, 30000),
    cobra(PA, 'Herrera, Alma', MP, 44000), cobra(PA, 'Molina, Milagros', EF, 30000), cobra(PA, 'Morales, Agustina', EF, 30000),
    cobra(PA, 'Cabrera, Constanza', EF, 33000), cobra(PA, 'Ferrari, Josefina', GA, 33000), cobra(PA, 'Flores, Juana', EF, 33000), cobra(PA, 'Garcia, Melina', MP, 43000), cobra(PA, 'Martinez, Delfina', EF, 33000),
    mov(PA, 'Limpieza', EF, 3100, 'Limpieza de diciembre'), mov(PA, 'Librería', EF, 1700, 'Planillas'), mov(PA, 'Insumos Varios', EF, 2400, 'Bombín'),
    lista(LU, AVA, '17:00', ['Suarez, Valentina', 'Morales, Carla']), lista(MA, FED, '18:00', ['Diaz, Catalina']),
    lista(HE, FAVA, '19:00', ['Silva, Simon']), lista(MA, ADU, '20:30', ['Luna, Joaquina']),
])
noche(PA, 53, 'Día 53, noche: Pablo cierra con lo esperado y deja $10.000; el dueño valida.')
# Día 54, miércoles 2/12
escribir('dia-54-manana.json', '2026-12-02', 'Día 54, miércoles 2/12: Rocío abre; sigue la cobranza de diciembre; gastos; lista de Patín Principiantes.', [
    abrir(RO),
    cobra(RO, 'Lopez, Valentina', EF, 44000), cobra(RO, 'Morales, Julieta', EF, 30000), cobra(RO, 'Ortiz, Micaela', MP, 44000), cobra(RO, 'Romero, Catalina', EF, 30000),
    cobra(RO, 'Suarez, Juana', EF, 30000), cobra(RO, 'Torres, Luciana', MP, 30000), cobra(RO, 'Navarro, Malena', EF, 43000), cobra(RO, 'Perez, Carla', EF, 33000),
    cobra(RO, 'Rodriguez, Mia', GA, 43000), cobra(RO, 'Sosa, Rocio', EF, 33000), cobra(RO, 'Benitez, Milagros', MP, 45000), cobra(RO, 'Herrera, Martina', EF, 45000),
    cobra(RO, 'Diaz, Thiago', EF, 38000), cobra(RO, 'Gomez, Lucas', EF, 38000), cobra(RO, 'Rossi, Leon', MP, 38000),
    mov(RO, 'Limpieza', EF, 2700, 'Rejilla y jabón'), mov(RO, 'Botiquín', EF, 1900, 'Gasas'), mov(RO, 'Librería', EF, 1300, 'Tinta'),
    lista(LU, PPAT, '16:00', ['Ramos, Pilar', 'Godoy, Valentino', 'Silva, Paulina', 'Ruiz, Thiago']),
])
noche(RO, 54, 'Día 54, noche: Rocío cierra con lo esperado y deja $10.000; el dueño valida.')
# Día 55, jueves 3/12: se termina de pagar noviembre
escribir('dia-55-manana.json', '2026-12-03', 'Día 55, jueves 3/12: Sandra abre; sigue la cobranza; remera y gastos; listas de Avanzadas, Federadas y Fútbol Avanzadas.', [
    abrir(SA),
    cobra(SA, 'Castro, Martina', EF, 30000), cobra(SA, 'Lopez, Martina', EF, 30000), cobra(SA, 'Cabrera, Renata', MP, 43000), cobra(SA, 'Gimenez, Helena', EF, 43000),
    cobra(SA, 'Romero, Florencia', EF, 33000), cobra(SA, 'Alvarez, Camila', EF, 35000), cobra(SA, 'Ramirez, Lucia', MP, 45000), cobra(SA, 'Diaz, Martina', EF, 50000),
    cobra(SA, 'Nuñez, Guadalupe', GA, 50000), cobra(SA, 'Vega, Florencia', EF, 50000), cobra(SA, 'Castro, Bruno', EF, 28000), cobra(SA, 'Gutierrez, Tomas', EF, 28000), cobra(SA, 'Herrera, Facundo', MP, 28000),
    mov(SA, 'Indumentaria institucional', EF, 18000, 'Remera del club: Vega'),
    mov(SA, 'Limpieza', EF, 2900, 'Limpiavidrios'), mov(SA, 'Botiquín', EF, 1500, 'Gasas y cinta'), mov(SA, 'Librería', EF, 900, 'Biromes'),
    lista(LU, AVA, '17:00', ['Ramirez, Lucia']), lista(MA, FED, '18:00', ['Rossi, Olivia']), lista(HE, FAVA, '19:00', ['Silva, Simon']),
])
noche(SA, 55, 'Día 55, noche: Sandra cierra con lo esperado y deja $10.000. El dueño valida por pantalla, con los tildes.', validar=False)
# Día 56, viernes 4/12: dos turnos
escribir('dia-56-manana.json', '2026-12-04', 'Día 56, viernes 4/12, turno de la mañana: Pablo abre, cobra y carga gastos.', [
    abrir(PA),
    cobra(PA, 'Quiroga, Lara', EF, 43000), cobra(PA, 'Ramos, Santiago', EF, 28000), cobra(PA, 'Ferrari, Nicolas', MP, 52000), cobra(PA, 'Ortiz, Tobias', EF, 52000),
    cobra(PA, 'Funes, Graciela', EF, 33000), cobra(PA, 'Ibañez, Marcelo', MP, 43000), mov(PA, 'Insumos Varios', EF, 2200, 'Silbatos'),
])
escribir('dia-56-mediodia.json', '2026-12-04', 'Día 56, 13:00: Pablo cierra y deja $12.000; el dueño valida. Rocío tenía la apertura abierta desde las 09:00.', [
    {'como': PA, 'accion': 'cerrar_caja', 'cambio': 12000}, {'como': AD, 'accion': 'validar_caja', 'de': PA},
])
escribir('dia-56-tarde.json', '2026-12-04', 'Día 56, turno de la tarde: Rocío cobra y carga gastos.', [
    cobra(RO, 'Torres, Benicio', EF, 28000), cobra(RO, 'Martinez, Bautista', EF, 28000), cobra(RO, 'Pereyra, Manuel', MP, 35000), cobra(RO, 'Ramirez, Felipe', EF, 35000),
    cobra(RO, 'Rodriguez, Joaquin', EF, 35000), mov(RO, 'Limpieza', EF, 2400, 'Bolsas'), mov(RO, 'Librería', EF, 800, 'Sobres'),
])
noche(RO, 56, 'Día 56, noche: Rocío cierra con lo esperado y deja $10.000; el dueño valida.')
# Día 57, sábado 5/12: el torneo
JP = 'Juan Pablo Ledesma'
escribir('dia-57-manana.json', '2026-12-05', 'Día 57, sábado 5/12: Juan Pablo abre; cobros; gastos; listas de Federadas y de Fútbol Infantiles, la primera clase de Renzo.', [
    abrir(JP),
    cobra(JP, 'Paredes, Iván', EF, 35000), cobra(JP, 'Ortega, Valentín', EF, 35000), cobra(JP, 'Quiroga, Tomás', MP, 28000), cobra(JP, 'Sosa, Bautista', EF, 35000),
    cobra(JP, 'Suarez, Ignacio', EF, 35000), cobra(JP, 'Acuña, Renzo', EF, 25000), cobra(JP, 'Diaz, Catalina', MP, 50000), cobra(JP, 'Rossi, Olivia', EF, 50000),
    mov(JP, 'Limpieza', EF, 3200, 'Limpieza después del torneo'), mov(JP, 'Insumos Varios', EF, 1800, 'Medallas: cintas'),
    lista(MA, FED, '10:00', ['Ramos, Lucia']), lista(JP, 'Fútbol — Infantiles', '10:00'),
])
noche(JP, 57, 'Día 57, noche: Juan Pablo cierra con lo esperado y deja $10.000; el dueño valida.')
