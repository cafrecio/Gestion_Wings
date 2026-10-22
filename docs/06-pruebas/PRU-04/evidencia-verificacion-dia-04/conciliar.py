"""Contrasta el snapshot SQL con servicios y valores del informe humano; no conecta a la base."""
from pathlib import Path
from decimal import Decimal as D
import csv
import io
import json

base = Path(__file__).parent
raw = (base / "consulta-db.tsv").read_text(encoding="utf-8-sig")
secciones = {}
for bloque in raw.split("seccion\n"):
    lineas = bloque.strip().splitlines()
    if lineas:
        secciones[lineas[0]] = list(csv.DictReader(io.StringIO("\n".join(lineas[1:])), delimiter="\t")) if len(lineas) >= 2 else []
servicios = json.loads((base / "servicios.json").read_text(encoding="utf-8-sig"))
movimientos = secciones["MOVIMIENTOS_CAJA_2"]
assert len(movimientos) == 7
ingresos = sum(D(m["monto"]) for m in movimientos if m["tipo"] == "INGRESO")
egresos = sum(D(m["monto"]) for m in movimientos if m["tipo"] == "EGRESO")
neto = ingresos - egresos
efe = sum(D(m["monto"]) * (1 if m["tipo"] == "INGRESO" else -1)
          for m in movimientos if m["abreviatura"] == "EFE")
caja = secciones["CAJA_2"][0]
assert caja["estado"] == "VALIDADA"
assert D(caja["efectivo_esperado"]) == D(caja["efectivo_contado"]) == D(caja["efectivo_inicial"]) + efe == D("119500")
assert D(caja["diferencia_efectivo"]) == 0
assert D(caja["cambio_retenido"]) + D(caja["efectivo_retirado"]) == D("119500")
assert all(m["verificado_por"] == "1" and m["verificado_at"] != "NULL" for m in movimientos)
assert len(secciones["CONTROL_REFLEJOS"]) == 7
assert all(r["reflejos"] == r["iguales"] == "1" for r in secciones["CONTROL_REFLEJOS"])
assert all(D(p["monto_final"]) == D(p["imputado"]) for p in secciones["CONTROL_SUMAS_PAGOS"])
assert sum(D(p["monto_final"]) for p in secciones["PAGOS_5_A_9"]) == D("186000")
assert sum(int(a["activos"]) for a in secciones["MATRICULA_ACTUAL"]) == 101
assert not secciones["DUPLICADOS_ASISTENCIA"]
asistencias = {int(a["clase_id"]): (int(a["registros"]), int(a["presentes"]), int(a["ausentes"]))
               for a in secciones["TOTALES_ASISTENCIAS"]}
assert asistencias == {28: (12, 10, 2), 74: (14, 12, 2)}
assert secciones["SIN_MARCAS_CLASE_6"][0]["marcas"] == "0"
cuotas = D(secciones["DEUDA_CUOTAS_ACTIVOS"][0]["deuda"])
cargos = sum(D(c["saldo"]) for c in secciones["CARGOS_PENDIENTES_ACTIVOS"])
deuda = cuotas + cargos
saldo = sum(D(c["saldo"]) for c in secciones["SALDOS_POR_MEDIO"])
periodos = {r["periodo"]: D(r["cobrado"]) for r in secciones["CUOTAS_POR_PERIODO_PAGADO"]}
inscripciones = sum(D(r["monto_aplicado"]) for r in secciones["INSCRIPCIONES_COBRADAS_OCTUBRE"])
economico = sum(v for p, v in periodos.items() if p <= "2026-10") + inscripciones
aportes = sum(D(m["monto"]) for m in movimientos if m["reporte_clasificacion"] == "APORTE")
global_mes = sum(D(m["monto"]) for m in secciones["CASHFLOW_OCTUBRE"])
assert (ingresos, egresos, neto, efe) == (D("201000"), D("8500"), D("192500"), D("109500"))
assert deuda == D("1899000") and saldo == D("1926500")
assert economico == D("320000") and aportes == D("15000") and periodos["2026-11"] == D("30000")
assert global_mes == economico - egresos + aportes + periodos["2026-11"] == D("356500")
reporte = servicios["reporte_octubre_centavos"]
for clave, valor in {"ingresos": economico, "egresos": egresos, "resultado": economico-egresos,
                     "resultado_global": global_mes, "aportes": aportes, "disponible": saldo, "deuda": deuda}.items():
    assert D(reporte[clave]) == valor * 100, clave
assert servicios["contadores"] == {"DEUDOR": 21, "AL_DIA": 64, "MOROSO": 15, "EN_PLAZO": 1}
resultado = {"alcance": "Registros ejecutados del Dia 4; no acredita totalidad de deudas potenciales, seguridad ni carga",
             "estado": "CONCILIADO", "pagos": 5, "imputaciones": 6, "movimientos": 7, "reflejos_unicos": 7,
             "ingresos_turno": str(ingresos), "egresos_turno": str(egresos), "neto_turno": str(neto),
             "contado": caja["efectivo_contado"], "deuda_guardada": str(deuda), "disponible": str(saldo),
             "ingresos_octubre": str(economico), "aportes": str(aportes), "adelanto_noviembre": str(periodos["2026-11"]),
             "asistencias": asistencias, "clase_futura_sin_marcas": True}
(base / "conciliacion.json").write_text(json.dumps(resultado, ensure_ascii=False, indent=2)+"\n", encoding="utf-8")
print(json.dumps(resultado, ensure_ascii=True))
