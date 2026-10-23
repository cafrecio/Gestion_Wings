"""Incorpora evidencia existente. No ejecuta Wings ni modifica el catálogo fuente.

Idempotente: reemplaza solo D4-*, MED-D4-* y SEG-20261023-*; conserva otros días.
Los criterios no citados quedan pendientes. Una variante no cierra el caso fuente.
"""
import copy
import json
import re
from pathlib import Path
import xml.etree.ElementTree as ET

HERE = Path(__file__).resolve().parent
REPO = HERE.parents[3]
PATH = HERE / 'control-integral.json'
d = json.loads(PATH.read_text(encoding='utf-8'))
old = [e for e in d['ejecuciones'] if not e['id'].startswith(('D4-', 'MED-D4-', 'SEG-20261023-'))]
root = 'docs/06-pruebas/PRU-04/'
report = root + 'VERIFICACION-DIA-04.md'
human = root + 'DIA-04.md'
db = root + 'evidencia-verificacion-dia-04/consulta-db.tsv'
svc = root + 'evidencia-verificacion-dia-04/servicios.json'
capt = root + 'evidencia-dia-04/'
catalog = {c['codigo']: c for c in d['casos']}

def criteria(*items):
    # Las letras son referencias de cobertura parcial; no cambian su texto fuente.
    return [{'caso': code, 'letra': letter, 'estado': 'Parcial', 'alcance': scope}
            for code, letters, scope in items for letter in letters]

rows = [
    (1, 'Apertura heredando cambio', 'INT-12', 'OPERATIVO', 'Coincide',
     'Caja 2 abierta con $10.000 heredados de caja 1 validada; sin motivo.',
     'Pendiente variante con caja anterior sin validar, otra operativa y cruces antes de operar.',
     criteria(('CAJ-005','abce','Solo cierre anterior validado; propuesta y apertura $10.000.')), 'dia04-01-caja-apertura.png'),
    (2, 'Alta de menor con tutor', 'INT-06', 'OPERATIVO', 'Parcial',
     'Mora 341, grupo 1, plan 2. Cuota 246 $40.000 y cargo 28 $5.000; EN_PLAZO.',
     'T2: ficha dice 6 días de gracia; no muestra vencimiento explícito ni total conjunto. No se intentó alta sin tutor.',
     criteria(('ALU-002','bcde','Alta con tutor, ficha, deuda y cruces del relato; no se probó celular del alumno vacío ni todos los efectos.')), ''),
    (3, 'Nueva alumna en Cobranza', 'INT-06', 'OPERATIVO', 'Coincide',
     'Mora visible por filtro En plazo, deuda $45.000; matrícula 101.',
     'Se usó filtro, no búsqueda textual. Cruce propio sin equivalencia completa con un caso del catálogo.', [], ''),
    (4, 'Cobro efectivo de octubre', 'INT-13', 'OPERATIVO', 'Coincide',
     'Pago 5, Renata, $43.000 EFE. Octubre pagado y AL_DIA; movimiento y reflejo únicos.',
     'Recibo desde confirmación. No se comprobaron todos los accesos al PDF ni comisión y contadores intermedios.',
     criteria(('COB-003','bcdgk','Cobro, ficha/estado y registros; efectos intermedios y comisión pendientes.'),
              ('REC-001','ab','PDF 5 y evidencia visual aportada por Carlos; sin centavos, sin tres accesos ni estabilidad posterior.')), ''),
    (5, 'Cobro atrasado por Mercado Pago', 'INT-14', 'OPERATIVO', 'Coincide',
     'Pago 6, Emma, septiembre $45.000 MP; octubre conserva $45.000. Estado MOROSO.',
     'Variante de período anterior; fuente pide mes corriente. No acredita todos los cruces de COB-003.',
     criteria(('COB-004','abcdf','Mercado Pago, septiembre, $45.000; no aumenta efectivo.')), ''),
    (6, 'Entrega parcial de Dante', 'INT-15', 'OPERATIVO', 'Parcial',
     'Pago 7 $30.000 EFE: $20.000 a septiembre y $10.000 a octubre, saldo $38.000.',
     'T6: se escribieron dos importes. No demuestra distribución automática global, rechazo fuera de FIFO, segundo pago ni comisión.',
     criteria(('COB-008','acde','Variante: parcial de octubre $10.000 luego de cancelar septiembre; difiere de los $20.000/$28.000 fuente.')), ''),
    (7, 'Cobro por Banco Galicia', 'INT-14', 'OPERATIVO', 'Coincide',
     'Pago 8, Mateo, octubre $38.000 BGA; pagado, AL_DIA; no aumenta efectivo.',
     'Medio bancario, variante de Mercado Pago. Sin cubrir todos los cruces de la fuente.',
     criteria(('COB-004','abcdf','Transferencia Galicia $38.000; variante del medio.')), ''),
    (8, 'Adelanto de noviembre', 'INT-18', 'OPERATIVO / confirmación Carlos', 'Coincide',
     'Julieta sustituyó a Sofía por orden de Carlos. Pago 9 $30.000 EFE; cuota 247 noviembre PAGADA; recibo 9.',
     'D4-OBS-01: AL_DIA frente a octubre potencial en Cobrar. No repetir cobro. Generación 1/11, aumento y comisión pendientes.',
     criteria(('COB-012','abcehi','Variante Julieta $30.000; ingreso/comisión etiquetados noviembre; no se liquidó comisión. No se acredita deuda antes/después de la selección.')),
     ''),
    (9, 'Venta de remera', 'INT-21', 'OPERATIVO', 'Coincide',
     'Movimiento 11 $15.000 EFE, Indumentaria institucional APORTE, reflejo único.',
     'Solo efectivo; venta por MP pendiente. Aporte separado del resultado. No se liquidó comisión.',
     criteria(('MOV-002','abc','Una venta efectivo; la fuente pide también MP. Clasificación APORTE según catálogo actual.')), ''),
    (10, 'Gasto de limpieza', 'INT-21', 'OPERATIVO', 'Coincide',
     'Movimiento 12 $8.500 EFE, EGRESO NEGOCIO, cashflow -$8.500; observación Artículos para pista.',
     'No acredita ticket adjunto ni número del guion. Falta comparación de cada vista en estado pendiente.',
     criteria(('MOV-001','abcdeg','Gasto guardado, arqueo y resultado conciliados; estados previos y desglose completo pendientes.')), ''),
    (11, 'Nómina y asistencia del día', 'INT-28', 'PROFESOR', 'Coincide',
     'Clase 28: 12 marcas, 10 presentes/2 ausentes. Clase futura 6: Mora en nómina, cero marcas.',
     'Variante 12 alumnos frente a 8 fuente. Historial individual, sueldo, comisiones y generación mensual pendientes.',
     criteria(('ASI-001','abc','Lista 28 guardada y recargada, 10 presentes y 2 ausentes; no suplencia.')), ''),
    (12, 'Asistencia histórica reciente', 'INT-28', 'PROFESOR', 'Coincide',
     'Clase 74 del 10/10: 14 marcas, 12 presentes/2 ausentes, sin duplicados; contador actual 32.',
     'El ejecutor observó 33→32. No hay snapshot previo propio ni prueba de antigüedad extrema CLA-018.', [], ''),
    (13, 'Cierre sin diferencia', 'INT-22', 'OPERATIVO', 'Coincide',
     'Caja 2 CERRADA; esperado/contado $119.500, diferencia $0, retenido $10.000, retiro declarado $109.500.',
     'Importes del relato, diferentes de la fuente. No acredita entrega física, próxima apertura ni intento HTTP posterior al cierre.',
     criteria(('CAJ-018','abch','Arqueo sin diferencia y resumen cerrado; sin faltante ni cambio aumentado.')), ''),
    (14, 'Verificación y validación por admin', 'INT-23', 'ADMIN', 'Coincide',
     'Caja 2 VALIDADA; siete movimientos verificados y siete reflejos únicos con responsables.',
     'No había inscripción cobrada ni cancelado; sin doble envío ni intento de modificación posterior por otro rol.',
     criteria(('CAJ-025','abcdeg','Variante: cinco cuotas, aporte y gasto; condiciones parciales del caso fuente.')), ''),
    (15, 'Matrícula contrastada por admin', 'INT-30', 'ADMIN', 'Coincide',
     '101 activos: Patín 66, Fútbol 35, Patín Principiantes 27. Mora visible en ficha y reporte.',
     'Matrícula actual, no altas mensuales. Cruce propio; sin equivalencia completa asignada a un caso fuente.', [], ''),
    (16, 'Conciliación de cierre del día', 'INT-30', 'ADMIN', 'Coincide',
     'Deuda $1.899.000; disponible $1.926.500; resultado octubre $311.500. APORTE $15.000 y adelanto $30.000 separados.',
     'D4-OBS-01 abierta. Falta estado previo completo por operación, bajas, cancelaciones, condonación, liquidaciones y demás variantes.',
     criteria(('CBZ-018','abcd','101 activos, estados 64/1/15/21 y deuda final; no todos los filtros ni todos los movimientos de la fuente.'),
              ('REP-001','abceg','Cierre con cero cajas pendientes, deuda conciliada y 32 sin asistencia; fuente exige otras condiciones.'),
              ('REP-003','abcdefgh','Totales de octubre y disponibles conciliados al 13/10; no mes completo ni todas las variantes.')), ''),
]
entries = []
for n, title, route, role, result, observed, limit, checks, screenshot in rows:
    e = copy.deepcopy(d['plantilla_ejecucion'])
    e.update(id=f'D4-T{n:02}', recorrido=route, caso=', '.join(dict.fromkeys(c['caso'] for c in checks)),
             letras_criterio=[c['caso']+'.'+c['letra'] for c in checks], criterios=checks,
             dia='Día 4 / T'+str(n), titulo=title, tipo_ejecucion='Humana con contraste técnico',
             fecha_hora_wings='2026-10-13; cierre 19:33, validación 19:36; última lectura 19:51',
             fecha_hora_real='2026-10-22; incorporación a tabla 2026-10-23',
             entorno='staging, test.gestionar-te.com.ar', base='wingstest',
             commit=d['commit_sitio_leido'], rol=role,
             esperado_fijado_antes={'fuente':human, 'tarea':n, 'aviso':'Se conserva el esperado original. No se reconstruye un snapshot previo inexistente.'},
             observado={'resultado':observed, 'limites':limit}, funcional=result,
             datos_y_calculos='Contrastados: ver alcance', permisos_y_seguridad='Pendiente ensayo negativo en sitio',
             evidencia=[human,report,db,svc], hizo='Codex CyE / chat Prueba humana',
             verifica='Codex CyE / chat Casos prueba humana I', resultado=result)
    if screenshot and (REPO / (capt+screenshot)).is_file():
        e['evidencia'].append(capt+screenshot)
    if n in (8,16): e['defecto']='D4-OBS-01'
    if n in (2,6): e['defecto']='D4-T'+str(n)+'-PARCIAL'
    entries.append(e)

def extra(ident, title, result, observed, evidence, **fields):
    e=copy.deepcopy(d['plantilla_ejecucion'])
    e.update(id=ident,titulo=title,resultado=result,observado=observed,evidencia=evidence,criterios=[],**fields)
    entries.append(e)

extra('MED-D4-01','Medición puntual del reporte mensual','Medido',
      {'medicion':'29 SQL; 11,37 ms SQL; 35,83 ms llamada CLI; 101 alumnos activos.',
       'limites':'Una muestra. Sin tiempo visible, carga masiva, comparación de volúmenes ni percentiles.'},
      [report,svc], tipo_ejecucion='Medición técnica', dia='Día 4',fecha_hora_real='2026-10-22',
      entorno='staging por CLI',base='wingstest',commit=d['commit_sitio_leido'],performance='Medición puntual',
      consultas_sql=29,tiempo_servicio_ms=35.83,tiempo_sql_ms=11.37,volumen_activos=101,
      hizo='Codex CyE',verifica='Pendiente revisión de este auxiliar',controles=['PER-01'])

extra('D4-OBS-01','Al día frente a cuota potencial de octubre en Cobrar','Observación abierta',
      {'hecho':'Julieta: AL_DIA con noviembre pagado y sin deuda octubre guardada; Cobrar añade octubre en memoria.',
       'implicacion':'Dos pantallas expresan conceptos diferentes. La revisión previa omitió la inscripción de Sofía.',
       'siguiente':'Resolver regla de deuda exigible/cuota potencial con Carlos antes de corregir. No crear deuda para forzar cuadre.'},
      [report,db,svc],tipo_ejecucion='Hallazgo técnico',dia='Día 4',fecha_hora_real='2026-10-22',
      entorno='staging / lectura de datos y código',base='wingstest',commit=d['commit_sitio_leido'],
      hizo='Codex CyE / ambos chats',verifica='Codex CyE / contraste técnico',defecto='D4-OBS-01')

# Cada método ejecutado conserva su nombre exacto, duración y aserciones JUnit.
ev = HERE / 'evidencia-seguridad-2026-10-23'
meta = json.loads((ev/'ejecucion.json').read_text(encoding='utf-8-sig'))
tree = ET.parse(ev/'junit.xml')
for i,t in enumerate(tree.findall('.//testcase'),1):
    name=t.attrib['name']; cls=t.attrib.get('class',t.attrib.get('classname','')).split('\\')[-1]
    source=t.attrib.get('file','').replace('\\','/')
    if not source or not Path(source).is_file(): source=str(REPO/'tests/Feature'/f'{cls}.php')
    source=Path(source).resolve().relative_to(REPO).as_posix()
    passed=not any(t.find(x) is not None for x in ['failure','error','skipped'])
    checks=[]; controls=[]; limits='Ensayo HTTP interno de Laravel o servicio directo, con fixtures. Sin navegador ni servidor remoto. No prueba CSRF real.'
    if cls=='AccesoSinPermisoTest':
        controls=['SEG-01']
        if 'test_operativo_sin_permiso' in name:
            checks=criteria(('ACC-009','abe','GET '+name.split('_en_')[-1]+': 403, aviso castellano, Volver, sesión conservada. Otras URLs/menú/datos no comprobados por este método.'))
        elif 'test_profesor_sin_permiso' in name:
            checks=criteria(('ACC-010','ab','GET '+name.split('_en_')[-1]+': 403, aviso castellano y Volver a Clases.'))
        elif 'sin_sesion' in name:
            checks=criteria(('ACC-007','a','GET Cashflow sin sesión redirige al login; faltan otras rutas, PDFs y destino tras login.'))
        elif 'inactiva' in name: controls=['SEG-02']
    elif cls=='SeguridadRutasProfesorTest':
        controls=['SEG-01']
        checks=criteria(('ACC-010','d' if 'conserva' in name else 'a','Lista de clases abre o cinco GET operativos responden 403, según método; ficha de cualquier clase pendiente.'))
    elif cls=='CobroReciboAccesoTest':
        if 'profesor' in name:
            controls=['SEG-01'];checks=criteria(('ACC-010','c','Recibo cuota: GET de profesor rechazado con 403.'))
        else: controls=['TEC-03'];limits+=' Prueba enlaces al recibo; no certifica contenido del PDF.'
    elif cls=='CajaVerificacionMovimientosT36Test':
        controls=['VAL-02']
        if 'operativa_y_el_profesor' in name:
            controls=['SEG-01'];checks=criteria(('ACC-011','abc','PATCH verificar movimiento y servicio directo: operativo/profesor 403 y marca permanece falsa. Faltan otras acciones exclusivas de admin.'))
        elif 'por_otra_caja' in name or 'pantalla_vieja' in name: controls=['SEG-03','VAL-02']
        elif 'una_sola_vez' in name:
            controls=['TEC-01'];checks=criteria(('CAJ-027','ab','Una validación HTTP y segunda llamada de servicio no duplican los dos reflejos. No doble POST ni concurrencia.'))
    elif cls=='CajaRechazoMotivoT37Test':
        controls=['VAL-01','VAL-02']
        if 'sin_motivo' in name or 'solo_espacios' in name:
            checks=criteria(('CAJ-029','a','POST sin motivo/espacios rechazado; caja cerrada, sin cashflow; Detalle y Resumen.'))
        elif 'motivo_valido' in name:
            checks=criteria(('CAJ-029','bc','Motivo guardado y visible en Resumen de operativa; no Inicio ni corrección completa.'))
    elif cls=='ReciboErrorSanitizadoTest':
        controls=['SEG-03','TEC-03'];limits+=' Fallo de generador simulado: respuesta sin traza y registro técnico comprobados.'
    role='ADMIN' if cls in ['CajaVerificacionMovimientosT36Test','CajaRechazoMotivoT37Test'] else 'Según fixture del método'
    if 'profesor' in name: role='PROFESOR'
    if 'operativo_sin' in name: role='OPERATIVO'
    if 'operativa_y_el_profesor' in name: role='OPERATIVO y PROFESOR; ADMIN de control'
    if 'sin_sesion' in name: role='ANÓNIMO'
    if 'inactiva' in name: role='ADMIN inactivo'
    if cls=='ReciboErrorSanitizadoTest': role='OPERATIVO (cuota) / ADMIN (liquidación), según método'
    extra(f'SEG-20261023-{i:02}',cls+'::'+name,'Aprobado' if passed else 'Falló / omitido',
          {'prueba':name,'aserciones':int(t.attrib.get('assertions',0)), 'duracion_s':float(t.attrib.get('time',0)),
           'limites':limits,'detalle_fallo':' '.join(''.join(t.itertext()).split()) if not passed else ''},
          [f'{root}control-integral/evidencia-seguridad-2026-10-23/{x}' for x in ['junit.xml','salida.txt','base-efectiva.json']]+[source],
          tipo_ejecucion='Automatizada aislada',dia='Seguridad posterior al Día 4',
          fecha_hora_real=meta['inicio'],fecha_hora_wings='Fixtures: fecha propia de cada prueba; no se mueve wingstest',
          entorno='local testing / PHP 8.2.12 / MariaDB',base='wings_testing_codex',commit=meta['commit'],
          hizo='Codex CyE',verifica='Pendiente revisión independiente de esta corrida',controles=controls,rol=role,
          esperado_fijado_antes={'fuente':source,'metodo':name,'aviso':'Aserciones permanentes existentes antes de la corrida; no modificadas.'},
          pasos=['Preparación y peticiones del método '+cls+'::'+name],
          permisos_y_seguridad='Ensayo aprobado' if passed and 'SEG-01' in controls else 'Ver alcance del método')
    entries[-1]['criterios']=checks
    entries[-1]['caso']=', '.join(dict.fromkeys(c['caso'] for c in checks))
    entries[-1]['letras_criterio']=[c['caso']+'.'+c['letra'] for c in checks]
    if not passed:
        for check in checks: check['estado']='Falló / omitido'

d.update(version=2,fecha='2026-10-23',alcance='Seguimiento de ejecuciones y brechas por criterio. Catálogo Carlos/Claude solo lectura.',
         estado_entrega='Día 4 conciliado; seguridad dirigida ejecutada después; seguimiento pendiente de revisión independiente',
         horizonte_historia='Dos meses ficticios, día a día. El 02/11 es un hito, no el final.',
         cierre_previsto_historia=None,
         reloj_leido='Última comprobación propia: 2026-10-22, Wings 13/10/2026 19:51. No releído ni cambiado el 23/10.',
         commit_local_leido=meta['commit'],ejecuciones=old+entries)
d['criterio_cobertura']='Caso completo solo al verificar todas sus condiciones, roles y letras. Una variante parcial no lo cierra. Sin evidencia significa pendiente, no falla.'
d['pendientes_prioritarios']=[
    'Resolver D4-OBS-01 sin alterar los datos para hacerlos coincidir.',
    'Día 5: primera asistencia de Mora y actividad normal; no repetir el Día 4.',
    'Seguridad: POST validar/rechazar de operativa; cancelación/reasignación de profesora; CSRF, XSS, sesiones/cambio de clave, headers y rate limit reales.',
    'Concurrencia real, cortes y comparación de performance con más volumen en base aislada.',
    'Visual: revisión sistemática 1366/375 y comportamiento al decidir filas.',
    'Hitos: 19–20/10 plazo de Mora; 01/11 no duplicación de Julieta; 02/11 liquidaciones.',
    'Revisión independiente de esta consolidación/corrida; aceptación integral y despliegue pendientes.'
]
for c in d['casos']:
    refs=[(e,q) for e in d['ejecuciones'] for q in e.get('criterios',[]) if q['caso']==c['codigo']]
    c['revision_actual']='Parcial' if refs else 'Pendiente'
    c['ejecuciones']=list(dict.fromkeys(e['id'] for e,q in refs))
    c['criterios_seguimiento']=[{'letra':q['l'],'estado':'Parcial' if any(z['letra']==q['l'] for e,z in refs) else 'Pendiente',
                               'ejecuciones':list(dict.fromkeys(e['id'] for e,z in refs if z['letra']==q['l']))}
                              for q in c['esperado_fuente']]
for r in d['recorridos']:
    r['ejecuciones']=[e['id'] for e in d['ejecuciones'] if e.get('recorrido')==r['id'] or any(q['caso'] in r['casos'] for q in e.get('criterios',[]))]
    r['estado']='Parcial' if r['ejecuciones'] else 'Pendiente'
for c in d['controles']:
    c['ejecuciones']=[e['id'] for e in d['ejecuciones'] if c['id'] in e.get('controles',[])]
    if c['id']=='TEC-02': c['ejecuciones']=['D4-T16']
    c['estado']='Parcial' if c['ejecuciones'] else 'Pendiente'
    c['evidencia']=list(dict.fromkeys(p for e in d['ejecuciones'] if e['id'] in c['ejecuciones'] for p in e['evidencia']))
d['resumen']={
    'tareas_dia4':16,'coinciden_dia4':14,'parciales_dia4':2,
    'casos_catalogo':len(d['casos']),
    'casos_con_evidencia_parcial':sum(c['revision_actual']=='Parcial' for c in d['casos']),
    'casos_sin_evidencia':sum(c['revision_actual']=='Pendiente' for c in d['casos']),
    'casos_completos':0,
    'criterios_catalogo':sum(len(c['esperado_fuente']) for c in d['casos']),
    'criterios_con_evidencia_parcial':sum(q['estado']=='Parcial' for c in d['casos'] for q in c['criterios_seguimiento']),
    'automatizadas':len(tree.findall('.//testcase')),
    'automatizadas_aprobadas':sum(e['resultado']=='Aprobado' for e in entries if e['id'].startswith('SEG-')),
    'aserciones':sum(int(t.attrib.get('assertions',0)) for t in tree.findall('.//testcase')),
    'salida_suite':meta['salida']}
assert len({e['id'] for e in d['ejecuciones']})==len(d['ejecuciones'])
for e in d['ejecuciones']:
    for q in e.get('criterios',[]):
        assert q['caso'] in catalog and q['letra'] in [a['l'] for a in catalog[q['caso']]['esperado_fuente']]
    for p in e['evidencia']: assert (REPO/p).is_file(), p
PATH.write_text(json.dumps(d,ensure_ascii=False,indent=2)+'\n',encoding='utf-8')
print(json.dumps(d['resumen'],ensure_ascii=False))
