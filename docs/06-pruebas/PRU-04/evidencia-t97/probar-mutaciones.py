"""Contrapruebas T97: una regla retirada por vez, base Codex, restauración byte por byte.

Ejecutar después de las dirigidas verdes y sin ninguna otra suite en esta computadora.
Se puede reanudar: conserva los casos ya ejecutados si sus fuentes base siguen iguales.
"""
from pathlib import Path
import hashlib
import json
import os
import re
import subprocess
import time

REPO = Path(__file__).resolve().parents[4]
EVIDENCIA = Path(__file__).resolve().parent
SALIDA = EVIDENCIA / 'mutaciones'
SERVICIO = 'app/Services/AnulacionTransferenciaCashflowService.php'
CONTROLADOR = 'app/Http/Controllers/CashflowWebController.php'
PRIMERO = 'test_cualquier_pata_anula_el_par_y_conserva_motivo_autor_y_fecha'
PAR = 'test_par_inconsistente_se_rechaza_sin_reparar_ni_escribir'

# Cada reemplazo es único salvo la retirada conjunta de los tres candados.
CASOS = [
 ('01-permisos', SERVICIO, 'test_permisos_get_post_y_servicio_sin_escrituras', [
     ('if (!$admin->isAdmin() || !$admin->isActivo()) throw new AuthorizationException();', 'if (false) throw new AuthorizationException();')]),
 ('02-motivo-obligatorio', SERVICIO, 'test_motivo_invalido_rechazado_sin_escribir', [
     ("'required|string|max:500'", "'nullable|string|max:500'")]),
 ('03-motivo-maximo', SERVICIO, 'test_motivo_invalido_rechazado_sin_escribir', [
     ("'required|string|max:500'", "'required|string'")]),
 ('04-origen-transferencia', SERVICIO, 'test_un_directo_no_puede_entrar_en_el_circuito_de_transferencias', [
     ('$movimiento->referencia_tipo !== CashflowMovimiento::REF_TRANSFERENCIA', 'false')]),
 ('05-par-completo', SERVICIO, PAR, [
     ('if ($patas->count() !== 2) $this->inconsistente();', 'if (false) $this->inconsistente();')]),
 ('06-importe-cabecera', SERVICIO, PAR, [
     (' || $pata->monto !== $signo.$transferencia->monto', '')]),
 ('07-fecha-cabecera', SERVICIO, PAR, [
     ('$pata->fecha->toDateString() !== $transferencia->fecha', 'false')]),
 ('08-fecha-inverso', SERVICIO, PRIMERO, [
     ("'fecha' => $pata->fecha->toDateString()", "'fecha' => today()->toDateString()")]),
 ('09-cuenta-inverso', SERVICIO, PRIMERO, [
     ("'tipo_caja_id' => $pata->tipo_caja_id", "'tipo_caja_id' => $transferencia->destino_id")]),
 ('10-signo-inverso', SERVICIO, PRIMERO, [
     ("str_starts_with($pata->monto, '-') ? substr($pata->monto, 1) : '-'.$pata->monto", '$pata->monto')]),
 ('11-fotos-historicas', SERVICIO, 'test_inversos_conservan_fotos_aunque_cambie_el_catalogo', [
     ("                    'reporte_tipo' => $pata->reporte_tipo, 'reporte_clasificacion' => $pata->reporte_clasificacion,\n                    'reporte_deporte_id' => $pata->reporte_deporte_id,\n", '')]),
 ('12-marca-original', SERVICIO, PRIMERO, [
     ('$pata->anulado_at = now();', '$pata->anulado_at = null;')]),
 ('13-auditoria-motivo', SERVICIO, PRIMERO, [
     ("'motivo_anulacion' => $datos['motivo']", "'motivo_anulacion' => null")]),
 ('14-auditoria-autor', SERVICIO, PRIMERO, [
     ("'anulo_admin_id' => $adminId", "'anulo_admin_id' => null")]),
 ('15-referencia-propia', SERVICIO, PRIMERO, [
     ("'referencia_tipo' => CashflowMovimiento::REF_ANULACION_TRANSFERENCIA", "'referencia_tipo' => CashflowMovimiento::REF_CORRECCION")]),
 ('16-atomicidad', SERVICIO, 'test_fallo_en_segundo_inverso_revierte_primer_inverso_marcas_y_cabecera', [
     ('return DB::transaction(function () use ($origen, $adminId, $datos) {', 'return (function () use ($origen, $adminId, $datos) {'),
     ('        }, 3);', '        })();')]),
 ('17-candados-reales', SERVICIO, 'test_dos_conexiones_anulando_el_mismo_par_no_duplican_ni_escriben_sin_candado', [
     ('->lockForUpdate()', '', 3)]),
 ('18-no-repetir', SERVICIO, 'test_repetir_anulacion_por_la_otra_pata_no_duplica_ni_cambia_auditoria', [
     ('if ($transferencia->anulada_at !== null)', 'if (false)')]),
 ('19-acciones-anulada', CONTROLADOR, 'test_originales_y_contras_no_admiten_correccion_individual_ni_reanular_contra', [
     ("&& $mov->anulado_at === null && $transferencia && $transferencia->anulada_at === null", '&& $transferencia')]),
]


def sin_otras_pruebas():
    padre = int(os.environ.get('WINGS_T97_BATCH_PID', '0'))
    orden = ("$p=Get-CimInstance Win32_Process -Filter \"name='php.exe'\" | "
             "Where-Object { $_.CommandLine -match 'artisan test|phpunit' -and $_.ProcessId -ne " + str(padre)
             + " }; if($p){$p | Select-Object ProcessId,CommandLine | Format-List; exit 3}")
    estado = subprocess.run(['powershell', '-NoProfile', '-Command', orden], capture_output=True)
    if estado.returncode == 3:
        print('Espera: otra prueba PHP activa; ninguna fuente se modifica durante la espera.', flush=True)
    while estado.returncode == 3:
        time.sleep(1)
        estado = subprocess.run(['powershell', '-NoProfile', '-Command', orden], capture_output=True)
    if estado.returncode:
        raise RuntimeError('No se inicia la contraprueba: hay otra suite o no se pudo comprobar. ' + estado.stdout.decode(errors='replace'))


if __name__ == '__main__':
    SALIDA.mkdir(exist_ok=True)
    archivos = {nombre: (REPO / nombre).read_bytes() for nombre in {c[1] for c in CASOS}}
    huellas = {p: hashlib.sha256(b).hexdigest() for p, b in archivos.items()}
    informe = SALIDA / 'resultado.json'
    previo = json.loads(informe.read_text(encoding='utf-8')) if informe.exists() else {}
    if previo and previo['fuentes_base_sha256'] != huellas:
        raise RuntimeError('Las fuentes cambiaron desde las contrapruebas anteriores; revisar y guardar un nuevo corte.')
    resultados = previo.get('casos', [])
    entorno = dict(os.environ, DB_DATABASE='wings_testing_codex', APP_ENV='testing', WINGS_EVIDENCIA_T97='0')
    for nombre, archivo, filtro, reemplazos in CASOS:
        if any(r['caso'] == nombre and r['detectada'] for r in resultados):
            continue
        sin_otras_pruebas()
        ruta = REPO / archivo
        original = archivos[archivo]
        assert ruta.read_bytes() == original, f'Otra edición en {archivo}; no se toca.'
        cambiado = original.decode('utf-8').replace('\r\n', '\n')
        for reemplazo in reemplazos:
            antes, despues, *cantidad = reemplazo
            assert cambiado.count(antes) == (cantidad[0] if cantidad else 1), (nombre, antes)
            cambiado = cambiado.replace(antes, despues)
        datos = cambiado.encode('utf-8')
        try:
            ruta.write_bytes(datos)
            corrida = subprocess.run(['php', 'artisan', 'test', '--filter='+filtro], cwd=REPO,
                                     env=entorno, stdout=subprocess.PIPE, stderr=subprocess.STDOUT)
        finally:
            if ruta.read_bytes() != datos:
                raise RuntimeError(f'Edición ajena durante {nombre}: no se sobrescribe {archivo}.')
            ruta.write_bytes(original)
        salida = corrida.stdout.decode('utf-8', errors='replace')
        (SALIDA / (nombre+'.txt')).write_text(salida, encoding='utf-8')
        detectada = (corrida.returncode != 0 and 'FAILED' in salida and 'Fatal error' not in salida
                     and 'ParseError' not in salida and 'No tests found' not in salida)
        resultados = [r for r in resultados if r['caso'] != nombre]
        resultados.append({'caso': nombre, 'archivo': archivo, 'filtro': filtro,
                           'exit': corrida.returncode, 'detectada': detectada,
                           'fuente_restaurada_sha256': hashlib.sha256(ruta.read_bytes()).hexdigest()})
        informe.write_text(json.dumps({'base': 'wings_testing_codex', 'fuentes_base_sha256': huellas,
                                      'casos': resultados}, ensure_ascii=False, indent=2)+'\n', encoding='utf-8')
        print(nombre, 'DETECTADA' if detectada else 'REVISAR', 'exit', corrida.returncode, flush=True)
        if not detectada:
            raise SystemExit('Una contraprueba no detectó la retirada de la regla; no seguir hasta revisarla.')
    assert all((REPO/p).read_bytes() == b for p, b in archivos.items())
    print(f'{len(resultados)}/{len(CASOS)} retiradas detectadas; fuentes restauradas byte por byte.', flush=True)
