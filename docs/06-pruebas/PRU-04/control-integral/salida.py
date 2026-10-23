"""Selección de salida y revisión independiente; sin cambiar criterios fuente."""
import re
from pathlib import Path
import json

HERE=Path(__file__).resolve().parent
REPO=HERE.parents[3]
PLAN='docs/07-evaluacion/PLAN-TRABAJO-IA-v2026-09-08.md'
BOARD='docs/00-estado/tareas.json'
HAPPY={
    'Acceso y preparación':'ACC-001 PCA-001 GRU-001 PRO-001 USU-001 USU-003',
    'Alumnos':'ALU-001 ALU-002 ALU-012 ALU-020',
    'Caja':'CAJ-004 CAJ-005 CAJ-017 CAJ-018 CAJ-025 CAJ-036',
    'Cobros y comprobantes':'COB-003 COB-004 COB-005 COB-008 COB-012 COB-016 REC-001 REC-002',
    'Movimientos':'MOV-001 MOV-002 CSH-002',
    'Clases y sueldos':'CLA-002 CLA-003 ASI-001 LIQ-001 LIQ-002 LIQ-008 LIQ-009 REC-005',
    'Conciliación y cambio de mes':'REP-001 REP-002 REP-003 REP-006 REP-007 CBZ-011 CBZ-018',
}
PROTECTIONS='ACC-004 ACC-007 ACC-008 ACC-009 ACC-010 ACC-011 ACC-012 CAJ-027 CAJ-028 ASI-005'.split()

def actualizar(d):
    board=json.loads((REPO/BOARD).read_text(encoding='utf-8-sig'))
    required=[t for t in board if t.get('prioridad')=='produccion']
    open_tasks=[t for t in required if t['estado']!='cerrado']
    all_ids={t['id'] for t in required}
    open_ids={t['id'] for t in open_tasks}
    selected={code:group for group,codes in HAPPY.items() for code in codes.split()}
    assert set(selected)|set(PROTECTIONS)<={c['codigo'] for c in d['casos']}
    # Conservar el contraste realizado, sin presentarlo como una firma independiente.
    for e in d['ejecuciones']:
        if not e.get('revision_independiente'):
            previous=e.get('verifica','')
            if previous.startswith('Codex'): e['contraste_tecnico_por']=previous
            e['revision_independiente']={'estado':'Pendiente','agente':None,'identidad':None,'evidencia':[]}
            e['agente_ejecutor']='Codex'
            e['identidad_ejecutor']='Codex CyE / equipo de esta entrega'
            e['verifica']='Pendiente — otro agente'
        rev=e['revision_independiente']
        if rev['estado']=='Verificada':
            assert rev.get('identidad') and rev['identidad']!=e.get('identidad_ejecutor'),e['id']
            assert rev.get('agente') and rev.get('evidencia'),e['id']
            if rev['agente']==e.get('agente_ejecutor'):
                assert rev.get('cuenta_distinta_confirmada') is True,e['id']
            for path in rev['evidencia']: assert (REPO/path).is_file(),path
            e['verifica']=rev['identidad']
    for c in d['casos']:
        tasks=sorted(set(re.findall(r'\b(?:T\d+|B\d+)\b',str(c.get('tarea_fuente',''))))&all_ids)
        tags=[]
        if c['codigo'] in selected:tags.append('Camino feliz')
        if c['codigo'] in PROTECTIONS:tags.append('Protección imprescindible')
        if tasks:tags.append('Requisito de Carlos')
        c['produccion']={'necesario':bool(tags),'grupos':tags or ['Ampliación'],
                         'flujo':selected.get(c['codigo'],'Seguridad' if c['codigo'] in PROTECTIONS else 'Requisito explícito' if tasks else ''),
                         'tareas':tasks,'dependencias_abiertas':sorted(set(tasks)&open_ids),
                         'fundamento':('Flujos mínimos del plan PRU y dinero/asistencia/sueldos.' if c['codigo'] in selected else
                                       'Permisos e integridad exigidos por el gate; el camino feliz no reemplaza estos rechazos.' if c['codigo'] in PROTECTIONS else
                                       'Prioridad producción fijada por Carlos en el tablero.' if tasks else 'Fuera de esta selección inicial; no se elimina del catálogo.'),
                         'fuentes':[PLAN]+([BOARD] if tasks else [])}
        # Ninguna firma se deduce del nombre de otro chat ni de un test verde.
        revision=c.setdefault('revision_salida',{'estado':'Pendiente','agente':None,'identidad':None,'evidencia':[],'letras_verificadas':[]})
        if revision['estado']=='Verificada':
            assert revision.get('identidad') and revision.get('evidencia') and revision.get('agente'),c['codigo']
            if revision['agente']=='Codex':assert revision.get('cuenta_distinta_confirmada') is True,c['codigo']
            assert {q['l'] for q in c['esperado_fuente']}<=set(revision['letras_verificadas']),c['codigo']
            for path in revision['evidencia']:assert (REPO/path).is_file(),path
        c['produccion']['firma_independiente']=revision['estado']
    essential=[c for c in d['casos'] if c['produccion']['necesario']]
    d['produccion']={
        'estado':'No habilitado para producción',
        'alcance':'Selección de trabajo basada en flujos vigentes. No reemplaza PRU-05 ni posterga requisitos de Carlos.',
        'casos_necesarios':len(essential),
        'camino_feliz':len(selected),'protecciones':len(PROTECTIONS),
        'con_evidencia_parcial':sum(bool(c.get('ejecuciones')) for c in essential),
        'sin_evidencia_incorporada':sum(not c.get('ejecuciones') for c in essential),
        'con_firma_independiente':sum(c['produccion']['firma_independiente']=='Verificada' for c in essential),
        'tareas_previas_abiertas':[{'id':t['id'],'titulo':t['titulo'],'estado':t['estado'],'responsable':t['tiene'],'fuente':BOARD} for t in open_tasks],
        'listo':['Día 4 entregado: 16 situaciones, 14 coinciden y 2 parciales.',
                 'Conciliados caja, deuda, alta y asistencias; diferencia de caja $0.',
                 '41 métodos técnicos aprobados en base aislada; evidencia entregada.'],
        'frena':[f'{len(open_tasks)} requisitos previos de Carlos siguen abiertos en el tablero.',
                 'Sin revisión independiente del Día 4, de la corrida y de esta herramienta.',
                 'D4-OBS-01: resolver Al día frente a la cuota ofrecida por Cobrar.'],
        'falta':['Completar selección de salida, por roles y criterios, y revisión de otro agente.',
                 'Seguridad, móvil, volumen/concurrencia y ciclo mensual según controles aplicables.',
                 'Suite completa, restauración, preflight y servidor sobre la candidata; preparación real según el gate.'],
        'fuentes':[PLAN,'docs/00-estado/PLAN-PRODUCCION.md',BOARD]}
    d['version']=3
    d['estado_entrega']='Resultados entregados y contrastados por Codex; revisión independiente pendiente. Selección de salida separada del catálogo.'
    return d
