<?php
// Inventario exclusivamente de bases locales propias de Codex. No modifica datos.
date_default_timezone_set('America/Argentina/Buenos_Aires');
$fase=$argv[1] ?? 'previo';
if(!in_array($fase,['previo','posterior'],true)) throw new RuntimeException('Fase no permitida');
require dirname(__DIR__, 4).'/vendor/autoload.php';
$pdo = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$bases = array_values(array_filter($pdo->query('SHOW DATABASES')->fetchAll(PDO::FETCH_COLUMN), fn($b)=>str_starts_with($b, 'wings_testing_codex')));
$resultado=['fecha'=>date(DATE_ATOM),'bases_propias'=>$bases,'datos'=>[]];
foreach($bases as $base) {
    if(!preg_match('/^wings_testing_codex[a-z0-9_]*$/', $base)) throw new RuntimeException('Base no permitida');
    $pdo->exec('USE `'.$base.'`');
    $tablas=$pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $datos=[];
    foreach(['subrubros','movimientos_operativos','cashflow_movimientos'] as $tabla) {
        if(!in_array($tabla,$tablas,true)) {$datos[$tabla]=['existe'=>false]; continue;}
        $condicion=$tabla==='subrubros' ? "clasificacion_resultado='TRASPASO' AND es_reservado_sistema=0" : ($tabla==='cashflow_movimientos' ? "reporte_clasificacion='TRASPASO' AND (referencia_tipo IS NULL OR referencia_tipo<>'TRANSFERENCIA')" : "reporte_clasificacion='TRASPASO'");
        $campos=$tabla==='subrubros' ? 'id,nombre,rubro_id,es_reservado_sistema,clasificacion_resultado' : ($tabla==='cashflow_movimientos' ? 'id,subrubro_id,monto,fecha,referencia_tipo,referencia_id,reporte_clasificacion' : 'id,caja_operativa_id,subrubro_id,monto,fecha,reporte_clasificacion');
        if ($fase === 'posterior' && $tabla !== 'subrubros') {
            $clasificacion="COALESCE(m.reporte_clasificacion, s.clasificacion_resultado)='TRASPASO'";
            $sinTransferencia=$tabla==='cashflow_movimientos' ? " AND (m.referencia_tipo IS NULL OR m.referencia_tipo<>'TRANSFERENCIA' OR COALESCE(s.es_reservado_sistema,0)=0 OR NOT EXISTS (SELECT 1 FROM cashflow_transferencias t WHERE t.id=m.referencia_id))" : '';
            $consulta=' FROM '.$tabla.' m LEFT JOIN subrubros s ON s.id=m.subrubro_id WHERE '.$clasificacion.$sinTransferencia;
            $datos[$tabla]=['cantidad'=>(int)$pdo->query('SELECT COUNT(*)'.$consulta)->fetchColumn(), 'muestra'=>$pdo->query('SELECT m.id,m.subrubro_id,m.monto,m.fecha,m.reporte_clasificacion,s.clasificacion_resultado'.$consulta.' ORDER BY m.id LIMIT 5')->fetchAll(PDO::FETCH_ASSOC),'criterio'=>$consulta];
            continue;
        }
        $datos[$tabla]=['cantidad'=>(int)$pdo->query('SELECT COUNT(*) FROM '.$tabla.' WHERE '.$condicion)->fetchColumn(), 'muestra'=>$pdo->query('SELECT '.$campos.' FROM '.$tabla.' WHERE '.$condicion.' ORDER BY id LIMIT 5')->fetchAll(PDO::FETCH_ASSOC)];
    }
    $resultado['datos'][$base]=$datos;
}
file_put_contents(__DIR__.'/inventario-local-'.$fase.'.json',json_encode($resultado,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL);
echo json_encode($resultado,JSON_UNESCAPED_UNICODE).PHP_EOL;