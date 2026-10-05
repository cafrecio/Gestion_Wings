## 2026-09-21 — Claude CyE — ensayo del padron: "52.000" se grababa como $52

Ensayo del primer paso de PRU-02 en base descartable: seeder de 60 alumnos, exportar padron,
completarlo como una persona e importarlo. **Defecto mio, del importador:** el monto escrito
como texto "52.000" quedaba en $52 (`is_numeric` lee el punto como decimal, como COB-01), y
el periodo 092026 que Excel guarda como el numero 92026 se rechazaba sin decir por que.
Ahora: numero de Excel tal cual; texto en formato argentino; lo ambiguo ("52,000", "52.5",
"$") se rechaza con ejemplo. El export trae los periodos como texto. `CargaPadronFormatosExcelTest`
5 pruebas, las 5 fallan con el codigo anterior. Suite 284/1637. Instructivo actualizado.
Revision de FIN-09: cubre caja, cobro y Cashflow; **pagar una liquidacion acepta cualquier
fecha**, registrado en el plan para que decida Carlos. Ensayo de restauracion: no hecho.
