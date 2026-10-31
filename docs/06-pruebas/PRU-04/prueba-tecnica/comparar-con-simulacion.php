<?php
/**
 * PRU-04 · Prueba técnica · Plata al peso contra la simulación (Claude, 30/10/2026).
 *
 * Compara dos bases locales por claves naturales (nombre, DNI, período, fecha), no por id: la copia del sitio de
 * prueba después de la tanda y la copia donde Claude simuló la misma tanda antes de entregarla. Cuotas, recargos,
 * pagos, alumnos, planes, precios, cajas, movimientos de caja, Cashflow, clases con presentes y ausentes, y
 * excepciones. Solo lectura. Uso: php comparar-con-simulacion.php <base-del-sitio> <base-simulada>
 */
[$_, $a, $b] = $argv;
$q = [
 'deudas' => "SELECT CONCAT(al.nombre,' ',al.apellido,' ',IFNULL(al.dni,'')) k, d.periodo, d.monto_original, d.monto_pagado, d.estado FROM deuda_cuotas d JOIN alumnos al ON al.id=d.alumno_id",
 'cargos' => "SELECT CONCAT(al.nombre,' ',al.apellido,' ',IFNULL(al.dni,'')) k, c.tipo, c.monto_original, c.monto_condonado, IFNULL(c.monto_perdido,0) perdido, c.estado, IFNULL(d.periodo,'') periodo,
   (SELECT IFNULL(SUM(pc.monto_aplicado),0) FROM pago_cargo_alumno pc JOIN pagos p ON p.id=pc.pago_id WHERE pc.cargo_alumno_id=c.id AND p.estado<>'ANULADO') cobrado
   FROM cargos_alumno c JOIN alumnos al ON al.id=c.alumno_id LEFT JOIN deuda_cuotas d ON d.id=c.deuda_cuota_id",
 'pagos' => "SELECT CONCAT(al.nombre,' ',al.apellido,' ',IFNULL(al.dni,'')) k, DATE(p.fecha_pago) f, p.monto_final, p.estado, p.mes, p.anio FROM pagos p JOIN alumnos al ON al.id=p.alumno_id",
 'alumnos' => "SELECT CONCAT(nombre,' ',apellido,' ',IFNULL(dni,'')) k, activo, fecha_alta FROM alumnos",
 'planes' => "SELECT CONCAT(al.nombre,' ',al.apellido,' ',IFNULL(al.dni,'')) k, CONCAT(g.deporte_id,'-',g.nivel_id,'-',gp.clases_por_semana) plan, ap.fecha_desde, IFNULL(ap.fecha_hasta,''), IFNULL(ap.activo,'null') FROM alumno_planes ap JOIN alumnos al ON al.id=ap.alumno_id JOIN grupo_planes gp ON gp.id=ap.plan_id JOIN grupos g ON g.id=gp.grupo_id",
 'precios' => "SELECT CONCAT(g.deporte_id,'-',n.nombre,'-',gp.clases_por_semana) plan, pr.precio_mensual, pr.vigente_desde FROM grupo_plan_precios pr JOIN grupo_planes gp ON gp.id=pr.grupo_plan_id JOIN grupos g ON g.id=gp.grupo_id JOIN niveles n ON n.id=g.nivel_id",
 'cajas' => "SELECT DATE(c.apertura_at) f, u.name, c.estado, c.efectivo_inicial, c.efectivo_esperado, IFNULL(c.efectivo_contado,''), IFNULL(c.cambio_retenido,''), IFNULL(c.diferencia_efectivo,'') FROM cajas_operativas c JOIN users u ON u.id=c.usuario_operativo_id",
 'movs_caja' => "SELECT DATE(m.fecha) f, m.tipo_caja_id, s.nombre, m.monto, IFNULL(m.estado,'') e, u.name FROM movimientos_operativos m JOIN subrubros s ON s.id=m.subrubro_id JOIN users u ON u.id=m.usuario_id",
 'cashflow' => "SELECT DATE(cf.fecha) f, s.nombre, cf.tipo_caja_id, cf.monto, IF(cf.anulado_at IS NULL,'','anulado') an, IFNULL(cf.referencia_tipo,'') rt FROM cashflow_movimientos cf JOIN subrubros s ON s.id=cf.subrubro_id",
 'clases' => "SELECT c.fecha, c.hora_inicio, CONCAT(g.deporte_id,'-',n.nombre) grupo, c.cancelada, IFNULL(c.motivo_cancelacion,''), IFNULL(c.motivo_cambio_profesor,''),
   (SELECT GROUP_CONCAT(p.apellido ORDER BY p.apellido) FROM clase_profesor cp JOIN profesores p ON p.id=cp.profesor_id WHERE cp.clase_id=c.id) profesores,
   (SELECT COUNT(*) FROM asistencias a WHERE a.clase_id=c.id AND a.presente=1) pres, (SELECT COUNT(*) FROM asistencias a WHERE a.clase_id=c.id AND a.presente=0) aus FROM clases c JOIN grupos g ON g.id=c.grupo_id JOIN niveles n ON n.id=g.nivel_id",
 'excepciones' => "SELECT e.tipo, CONCAT(al.nombre,' ',al.apellido) k, e.motivo, e.registro_tipo FROM excepciones_cobranza e LEFT JOIN alumnos al ON al.id=e.alumno_id",
];
function filas($db, $sql) {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=$db;charset=utf8mb4", 'root', '');
    return array_map(fn($r) => implode(' | ', $r), $pdo->query($sql)->fetchAll(PDO::FETCH_NUM));
}
$total = 0;
foreach ($q as $nombre => $sql) {
    $x = filas($a, $sql); $y = filas($b, $sql);
    $cx = array_count_values($x); $cy = array_count_values($y);
    $solo = [];
    foreach ($cx as $k => $n) if (($cy[$k] ?? 0) < $n) $solo[] = "  + $a: $k" . ($n - ($cy[$k] ?? 0) > 1 ? ' x' . ($n - ($cy[$k] ?? 0)) : '');
    foreach ($cy as $k => $n) if (($cx[$k] ?? 0) < $n) $solo[] = "  - $b: $k" . ($n - ($cx[$k] ?? 0) > 1 ? ' x' . ($n - ($cx[$k] ?? 0)) : '');
    $total += count($solo);
    echo str_pad($nombre, 12) . count($x) . ' / ' . count($y) . ' filas, ' . count($solo) . " diferencias\n";
    foreach (array_slice($solo, 0, 40) as $s) echo $s . "\n";
}
echo "TOTAL diferencias: $total\n";
