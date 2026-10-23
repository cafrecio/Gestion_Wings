"""Proyección por caso. No modifica el catálogo ni convierte tests aislados en aceptación."""
from collections import Counter, defaultdict
from datetime import date, timedelta
from pathlib import Path
import json

HERE = Path(__file__).resolve().parent
RESULTADOS = ('OK', 'Fallo', 'Parcial', 'Reparado')


def es_sitio(e, config):
    return (e.get('base') == config['base']
            and config['sitio'].split('://', 1)[1] in e.get('entorno', '')
            and e.get('tipo_ejecucion') == 'Humana con contraste técnico')


def estado_jornada(resultados, planes=False, consolidado=True):
    if not consolidado or not resultados:
        return 'Sin consolidar'
    if any(r in ('Fallo', 'Parcial') for r in resultados):
        return 'Plan de acción' if planes else 'Atención'
    if 'Reparado' in resultados:
        return 'Reparado'
    return 'OK'


def resultado_retest(previo, implementacion, retest):
    """Un commit no es una reparación acreditada. Las fechas deben estar ordenadas."""
    if not (previo and implementacion and retest):
        return False
    return bool(implementacion.get('commit') and implementacion.get('fecha')
                and retest.get('fecha') and retest.get('ejecutor')
                and retest.get('evidencia') and retest.get('sitio_validado')
                and retest.get('resultado') == 'OK'
                and implementacion['fecha'] <= retest['fecha'])


def construir(d):
    config = json.loads((HERE/'seguimiento-config.json').read_text(encoding='utf-8'))
    human = json.loads((HERE/'devolucion-humana.json').read_text(encoding='utf-8'))
    descripciones = {s['referencia']: s for j in human['jornadas'] for s in j['situaciones']}
    contextos = {s['referencia']: j for j in human['jornadas'] for s in j['situaciones']}
    ejecuciones = {e['id']: e for e in d['ejecuciones']}
    vinculos = defaultdict(list)
    for v in config['vinculos_retrospectivos']:
        assert v['ejecucion'] in ejecuciones
        vinculos[v['ejecucion']].append(dict(v, estado='Parcial', retrospectivo=True))
    planes = {p['caso']: p for p in config['planes']}
    casos = []
    for original in d['casos']:
        code = original['codigo']
        intentos, apoyo = [], []
        for e in d['ejecuciones']:
            criterios = [q for q in e.get('criterios', []) if q['caso'] == code]
            extra = [q for q in vinculos[e['id']] if q['caso'] == code]
            if not criterios and not extra:
                continue
            if not es_sitio(e, config):
                apoyo.append({'ejecucion': e['id'], 'resultado': e['resultado'],
                              'base': e.get('base'), 'fecha': e.get('fecha_hora_real'),
                              'criterios': criterios, 'evidencia': e['evidencia']})
                continue
            # Un vínculo caso-operación es un intento: varias letras no lo multiplican.
            criterios += extra
            descripcion = descripciones[e['id']]
            contexto = contextos[e['id']]
            completo = ({q['letra'] for q in criterios if q['estado'] == 'OK'}
                        >= {q['l'] for q in original['esperado_fuente']})
            resultado = 'Fallo' if any(q['estado'] == 'Fallo' for q in criterios) else 'OK' if completo else 'Parcial'
            intentos.append({'ejecucion': e['id'], 'dia': contexto['dia'], 'fecha_club': contexto['fecha_club'],
                            'fecha_real': contexto['fecha_ejecucion'], 'ejecutor': e['hizo'],
                            'rol': e['rol'], 'usuario': descripcion['quien'],
                            'sitio': config['sitio'], 'base': e['base'], 'version': e['commit'],
                            'metodo': descripcion['como'], 'pantalla': descripcion['donde'],
                            'esperado_variante': descripcion['esperado'],
                            'obtenido': descripcion['ocurrio'], 'limite': e['observado'].get('limites', ''),
                            'resultado_operacion': e['resultado'], 'resultado_caso': resultado,
                            'criterios': criterios, 'evidencia': e['evidencia'],
                            'revision': e['revision_independiente'],
                            'tecnico': {k: e.get(k, 'Pendiente') for k in
                                        ('datos_y_calculos', 'permisos_y_seguridad', 'visual_1366', 'visual_375', 'performance')},
                            'metas': [m for m in config['metas'] if m['caso'] == code and m['ejecucion'] == e['id']]})
        intentos.sort(key=lambda i: (i['dia'], i['fecha_real'], i['ejecucion']))
        conteo = {r: sum(i['resultado_caso'] == r for i in intentos) for r in RESULTADOS}
        actual = 'Sin probar' if not intentos else intentos[-1]['resultado_caso']
        plan = planes.get(code)
        if plan:
            assert not resultado_retest(conteo['Fallo'], plan['implementacion'], plan['retest']), 'Incorporar el retest como ejecución con evidencia antes de computarlo'
            actual = 'Plan de acción'
        cubiertas = {q['letra'] for i in intentos for q in i['criterios']}
        programacion = [p for p in config['programacion'] if code in p['casos']]
        casos.append({'codigo': code, 'modulo': original['modulo'], 'titulo': original['titulo'],
                      'estado': actual, 'roles': original['roles'], 'requiere': original['requiere_fuente'],
                      'metodo': original['accion_fuente'], 'esperado': original['esperado_fuente'],
                      'fuente': original['fuente'], 'necesario_salida': original['produccion']['necesario'],
                      'programacion': programacion, 'intentos': intentos, 'cantidad_pruebas': len(intentos),
                      'resultados': conteo, 'apoyo_aislado': apoyo, 'plan': plan,
                      'metas': [m for m in config['metas'] if m['caso'] == code],
                      'criterios_con_evidencia': sorted(cubiertas),
                      'criterios_sin_evidencia': [q['l'] for q in original['esperado_fuente'] if q['l'] not in cubiertas],
                      'revision_independiente': 'Pendiente'})
    modulos = []
    for n, nombre in enumerate(dict.fromkeys(c['modulo'] for c in casos), 1):
        grupo = [c for c in casos if c['modulo'] == nombre]
        modulos.append({'id': f'M{n:02}', 'nombre': nombre, 'casos': [c['codigo'] for c in grupo],
                        'total': len(grupo), 'probados': sum(bool(c['intentos']) for c in grupo),
                        'sin_probar': sum(not c['intentos'] for c in grupo),
                        'parciales': sum(c['resultados']['Parcial'] > 0 for c in grupo),
                        'planes': sum(bool(c['plan']) for c in grupo),
                        **{r: sum(c['estado'] == r for c in grupo) for r in ('OK', 'Fallo', 'Reparado')}})
    sitio = [e for e in d['ejecuciones'] if es_sitio(e, config)]
    asignadas = {i['ejecucion'] for c in casos for i in c['intentos']}
    pendientes = [{'ejecucion': e['id'], 'titulo': e['titulo'], 'resultado_operacion': e['resultado'],
                   'motivo': 'Falta un vínculo justificado al catálogo; no cuenta como caso probado.',
                   'evidencia': e['evidencia']} for e in sitio if e['id'] not in asignadas]
    dias = []
    for n in range(1, 61):
        dias.append({'dia': n, 'fecha_club': (date(2026, 10, 10)+timedelta(days=n-1)).isoformat(),
                     'estado': estado_jornada([i['resultado_caso'] for c in casos for i in c['intentos'] if i['dia'] == n],
                                              any(c['plan'] and c['plan']['dia_detectado'] == n for c in casos), any(i['dia']==n for c in casos for i in c['intentos'])),
                     'archivo': f'jornadas/DIA-{n:02}.html'})
    return {'version': 5, 'corte': config['corte'], 'sitio': config['sitio'], 'base': config['base'],
            'alcance': 'Evidencia consolidada del Día 4; jornadas 1–3 por conciliar. Sin probar significa sin ejecución vinculada aquí, no una afirmación sobre todo el historial.',
            'regla': 'Caso completo solo si todos sus criterios tienen evidencia de aceptación; operación coincidente no equivale a caso OK. Apoyo aislado excluido. Revisión independiente pendiente.',
            'resumen': {'casos': len(casos), 'modulos': len(modulos),
                        'probados': sum(bool(c['intentos']) for c in casos),
                        'sin_probar': sum(not c['intentos'] for c in casos),
                        'intentos': sum(c['cantidad_pruebas'] for c in casos),
                        'operaciones': len(sitio), 'operaciones_sin_caso': len(pendientes),
                        'parciales': sum(c['resultados']['Parcial'] > 0 for c in casos),
                        'planes': len(planes), 'metodos_aislados': sum(e['id'].startswith('SEG-') for e in d['ejecuciones']),
                        **{r: sum(c['estado'] == r for c in casos) for r in ('OK', 'Fallo', 'Reparado')},
                        'salida_seleccionados': sum(c['necesario_salida'] for c in casos),
                        'salida_probados': sum(c['necesario_salida'] and bool(c['intentos']) for c in casos)},
            'modulos': modulos, 'dias': dias, 'casos': casos, 'operaciones_sin_caso': pendientes,
            'mediciones_sitio': [e for e in d['ejecuciones'] if e['id'].startswith('MED-D4-')],
            'observaciones_sitio': [e for e in d['ejecuciones'] if e['id'] == 'D4-OBS-01'],
            'vinculos_retrospectivos': config['vinculos_retrospectivos']}
