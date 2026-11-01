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
    escribir(f'dia-{dia}-noche.json', {48: '2026-11-26', 49: '2026-11-27', 50: '2026-11-28', 52: '2026-11-30', 53: '2026-12-01', 54: '2026-12-02'}[dia], descripcion, pasos)


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
