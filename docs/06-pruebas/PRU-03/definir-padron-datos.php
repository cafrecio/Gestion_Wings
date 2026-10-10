<?php
// definir-padron-datos.php
// Genera las 90 filas con reglas exactas y coherencia total

$padronFilas = [];

// Helper para armar fila
// Columnas:
// 0: DNI
// 1: Apellido
// 2: Nombre
// 3: Fecha nacimiento (d/m/Y)
// 4: Fecha ingreso (d/m/Y)
// 5: Celular
// 6: Email
// 7: Tutor
// 8: Teléfono tutor
// 9: Deporte
// 10: Grupo
// 11: Plan
// 12: Debe inscripción (Sí / No)
// 13: Tiene deuda (Sí / No)
// 14..37: Pares Periodo/Monto (hasta 12 pares)

// Base de alumnos (algunos con deuda, inscripcion, hermanos, 2 deportes)
// Total 90 filas: 60 Patín, 30 Fútbol.

$raw = [];

// --- FAMILIAS CON HERMANOS (6 Familias) ---
// Familia 1: Gomez (Marcelo Gomez - 1145123401)
// Sofia Gomez (Patin Principiantes, 7 años) - Ingreso 15/03/2025. Debe inscrip: Sí. Deuda: No.
$raw[] = ['56100101', 'Gomez', 'Sofia', '15/04/2019', '15/03/2025', '1145123401', 'marcelo.gomez@testmail.com', 'Marcelo Gomez', '1145123401', 'Patín', 'Principiantes', 'Patín / Principiantes / 1 clases', 'Sí', 'No', []];
// Lucas Gomez (Futbol Principiantes, 9 años) - Ingreso 15/03/2025. Debe inscrip: Sí (mismo tutor/familia, prueba de cobro único por DNI). Deuda: Sí (102026: 28000)
$raw[] = ['54100102', 'Gomez', 'Lucas', '10/08/2017', '15/03/2025', '1145123401', 'marcelo.gomez@testmail.com', 'Marcelo Gomez', '1145123401', 'Fútbol', 'Principiantes', 'Fútbol / Principiantes / 1 clases', 'Sí', 'Sí', [['102026', 28000]]];

// Familia 2: Rodriguez (Laura Rodriguez - 1145123402)
// Mia Rodriguez (Patin Intermedias, 11 años) - Ingreso 10/04/2024. Debe inscrip: No. Deuda: No.
$raw[] = ['52100201', 'Rodriguez', 'Mia', '22/02/2015', '10/04/2024', '1145123402', 'laura.rodriguez@testmail.com', 'Laura Rodriguez', '1145123402', 'Patín', 'Intermedias', 'Patín / Intermedias / 2 clases', 'No', 'No', []];
// Joaquin Rodriguez (Futbol Principiantes, 8 años) - Ingreso 10/04/2024. Debe inscrip: No. Deuda: No.
$raw[] = ['55100202', 'Rodriguez', 'Joaquin', '14/11/2018', '10/04/2024', '1145123402', 'laura.rodriguez@testmail.com', 'Laura Rodriguez', '1145123402', 'Fútbol', 'Principiantes', 'Fútbol / Principiantes / 2 clases', 'No', 'No', []];
// Mateo Rodriguez (Futbol Avanzadas, 14 años) - Ingreso 10/04/2024. Debe inscrip: No. Deuda: Sí (092026: 38000, 102026: 38000)
$raw[] = ['49100203', 'Rodriguez', 'Mateo', '05/06/2012', '10/04/2024', '1145123402', 'laura.rodriguez@testmail.com', 'Laura Rodriguez', '1145123402', 'Fútbol', 'Avanzadas', 'Fútbol / Avanzadas / 1 clases', 'No', 'Sí', [['092026', 38000], ['102026', 38000]]];

// Familia 3: Fernandez (Carlos Fernandez - 1145123403)
// Emma Fernandez (Patin Avanzadas, 15 años) - Ingreso 01/03/2023. Debe inscrip: No. Deuda: Sí (082026: 45000, 092026: 45000, 102026: 45000)
$raw[] = ['48100301', 'Fernandez', 'Emma', '18/09/2011', '01/03/2023', '1145123403', 'carlos.f@testmail.com', 'Carlos Fernandez', '1145123403', 'Patín', 'Avanzadas', 'Patín / Avanzadas / 2 clases', 'No', 'Sí', [['082026', 45000], ['092026', 45000], ['102026', 45000]]];
// Benjamin Fernandez (Futbol Avanzadas, 13 años) - Ingreso 01/03/2023. Debe inscrip: No. Deuda: No.
$raw[] = ['50100302', 'Fernandez', 'Benjamin', '20/01/2013', '01/03/2023', '1145123403', 'carlos.f@testmail.com', 'Carlos Fernandez', '1145123403', 'Fútbol', 'Avanzadas', 'Fútbol / Avanzadas / 2 clases', 'No', 'No', []];

// Familia 4: Lopez (Mariana Lopez - 1145123404)
// Martina Lopez (Patin Principiantes, 6 años) - Ingreso 20/08/2026 (Reciente). Debe inscrip: Sí. Deuda: Sí (102026: 30000)
$raw[] = ['57100401', 'Lopez', 'Martina', '12/10/2020', '20/08/2026', '1145123404', '', 'Mariana Lopez', '1145123404', 'Patín', 'Principiantes', 'Patín / Principiantes / 1 clases', 'Sí', 'Sí', [['102026', 30000]]];
// Valentina Lopez (Patin Principiantes, 8 años) - Ingreso 20/08/2026 (Reciente). Debe inscrip: Sí. Deuda: Sí (102026: 40000)
$raw[] = ['55100402', 'Lopez', 'Valentina', '03/05/2018', '20/08/2026', '1145123404', '', 'Mariana Lopez', '1145123404', 'Patín', 'Principiantes', 'Patín / Principiantes / 2 clases', 'Sí', 'Sí', [['102026', 40000]]];

// Familia 5: Diaz (Roberto Diaz - 1145123405)
// Catalina Diaz (Patin Federadas, 16 años) - Ingreso 15/02/2022. Debe inscrip: No. Deuda: No.
$raw[] = ['47100501', 'Diaz', 'Catalina', '14/07/2010', '15/02/2022', '1145123405', 'roberto.diaz@testmail.com', 'Roberto Diaz', '1145123405', 'Patín', 'Federadas', 'Patín / Federadas / 2 clases', 'No', 'No', []];
// Thiago Diaz (Futbol Avanzadas, 12 años) - Ingreso 15/02/2022. Debe inscrip: No. Deuda: No.
$raw[] = ['51100502', 'Diaz', 'Thiago', '09/12/2014', '15/02/2022', '1145123405', 'roberto.diaz@testmail.com', 'Roberto Diaz', '1145123405', 'Fútbol', 'Avanzadas', 'Fútbol / Avanzadas / 1 clases', 'No', 'No', []];

// Familia 6: Martinez (Patricia Martinez - 1145123406)
// Delfina Martinez (Patin Intermedias, 10 años) - Ingreso 05/03/2024. Debe inscrip: No. Deuda: No.
$raw[] = ['53100601', 'Martinez', 'Delfina', '25/03/2016', '05/03/2024', '1145123406', 'patricia.m@testmail.com', 'Patricia Martinez', '1145123406', 'Patín', 'Intermedias', 'Patín / Intermedias / 1 clases', 'No', 'No', []];
// Bautista Martinez (Futbol Principiantes, 7 años) - Ingreso 05/03/2024. Debe inscrip: No. Deuda: No.
$raw[] = ['56100602', 'Martinez', 'Bautista', '19/08/2019', '05/03/2024', '1145123406', 'patricia.m@testmail.com', 'Patricia Martinez', '1145123406', 'Fútbol', 'Principiantes', 'Fútbol / Principiantes / 1 clases', 'No', 'No', []];

// --- ALUMNA EN DOS DEPORTES (Camila Benitez, DNI 51200700) ---
// Fila A: Patín Avanzadas (14 años). Ingreso 01/03/2023. Debe inscrip: Sí. Deuda: Sí (102026: 45000)
$raw[] = ['51200700', 'Benitez', 'Camila', '11/04/2012', '01/03/2023', '1145123407', 'esteban.benitez@testmail.com', 'Esteban Benitez', '1145123407', 'Patín', 'Avanzadas', 'Patín / Avanzadas / 2 clases', 'Sí', 'Sí', [['102026', 45000]]];
// Fila B: Fútbol Principiantes (14 años). Ingreso 01/03/2023. Debe inscrip: Sí (misma alumna, la inscrip se cobra 1 sola vez en el sistema). Deuda: No.
$raw[] = ['51200700', 'Benitez', 'Camila', '11/04/2012', '01/03/2023', '1145123407', 'esteban.benitez@testmail.com', 'Esteban Benitez', '1145123407', 'Fútbol', 'Principiantes', 'Fútbol / Principiantes / 1 clases', 'Sí', 'No', []];

// Hasta acá tenemos:
// Patín: Sofia Gomez (Princ), Mia Rodriguez (Interm), Emma Fernandez (Avanz), Martina Lopez (Princ), Valentina Lopez (Princ), Catalina Diaz (Feder), Delfina Martinez (Interm), Camila Benitez (Avanz) -> 8 filas Patín.
// Fútbol: Lucas Gomez (Princ), Joaquin Rodriguez (Princ), Mateo Rodriguez (Avanz), Benjamin Fernandez (Avanz), Thiago Diaz (Avanz), Bautista Martinez (Princ), Camila Benitez (Princ) -> 7 filas Fútbol.
// Total hasta acá: 15 filas.

// Faltan 52 filas de Patín (para completar 60) y 23 filas de Fútbol (para completar 30).

// --- MAYORES DE EDAD (18+ años, sin tutor) ---
// Patín Federadas: Florencia Vega (21 años, nac 2005) - Ingreso 10/03/2022. Deuda: No.
$raw[] = ['44100801', 'Vega', 'Florencia', '15/01/2005', '10/03/2022', '1145123408', 'flor.vega@testmail.com', '', '', 'Patín', 'Federadas', 'Patín / Federadas / 2 clases', 'No', 'No', []];
// Patín Federadas: Berenice Rossi (19 años, nac 2007) - Ingreso 05/04/2023. Deuda: Sí (102026: 50000)
$raw[] = ['46100802', 'Rossi', 'Berenice', '20/03/2007', '05/04/2023', '1145123409', 'bere.rossi@testmail.com', '', '', 'Patín', 'Federadas', 'Patín / Federadas / 2 clases', 'No', 'Sí', [['102026', 50000]]];
// Patín Avanzadas: Melina Castro (20 años, nac 2006) - Ingreso 12/03/2023. Deuda: No.
$raw[] = ['45100803', 'Castro', 'Melina', '11/09/2006', '12/03/2023', '1145123410', 'meli.castro@testmail.com', '', '', 'Patín', 'Avanzadas', 'Patín / Avanzadas / 2 clases', 'No', 'No', []];
// Patín Avanzadas: Carla Morales (19 años, nac 2007) - Ingreso 15/08/2026. Debe inscrip: Sí. Deuda: No.
$raw[] = ['46100804', 'Morales', 'Carla', '04/05/2007', '15/08/2026', '1145123411', 'carla.m@testmail.com', '', '', 'Patín', 'Avanzadas', 'Patín / Avanzadas / 1 clases', 'Sí', 'No', []];

// Fútbol Avanzadas mayores de edad:
// Nicolas Ferrari (20 años, nac 2006) - Ingreso 10/03/2022. Deuda: No.
$raw[] = ['45200805', 'Ferrari', 'Nicolas', '18/02/2006', '10/03/2022', '1145123412', 'nico.ferrari@testmail.com', '', '', 'Fútbol', 'Avanzadas', 'Fútbol / Avanzadas / 2 clases', 'No', 'No', []];
// Julian Navarro (19 años, nac 2007) - Ingreso 15/03/2024. Deuda: Sí (102026: 48000)
$raw[] = ['46200806', 'Navarro', 'Julian', '25/08/2007', '15/03/2024', '1145123413', 'julian.n@testmail.com', '', '', 'Fútbol', 'Avanzadas', 'Fútbol / Avanzadas / 2 clases', 'No', 'Sí', [['102026', 48000]]];

// --- CASOS ESPECIALES DE DEUDA REQUERIDOS ---
// 1. Caso meses de 2025 (4 a 8 meses, con 2025):
// Patín Intermedias: Julieta Alvarez (11 años). Deuda de 5 meses: 112025 (22000), 122025 (22000), 082026 (33000), 092026 (33000), 102026 (33000). Montos viejos menores!
$raw[] = ['52300901', 'Alvarez', 'Julieta', '14/06/2015', '10/03/2024', '1145123414', 'marta.alvarez@testmail.com', 'Marta Alvarez', '1145123414', 'Patín', 'Intermedias', 'Patín / Intermedias / 1 clases', 'No', 'Sí', [['112025', 22000], ['122025', 22000], ['082026', 33000], ['092026', 33000], ['102026', 33000]]];

// 2. Otro caso con meses de 2025 (6 meses de deuda):
// Fútbol Principiantes: Santino Romero (10 años). Deuda: 102025 (18000), 112025 (18000), 122025 (18000), 082026 (28000), 092026 (28000), 102026 (28000)
$raw[] = ['53300902', 'Romero', 'Santino', '09/01/2016', '01/08/2024', '1145123415', 'diego.romero@testmail.com', 'Diego Romero', '1145123415', 'Fútbol', 'Principiantes', 'Fútbol / Principiantes / 1 clases', 'No', 'Sí', [['102025', 18000], ['112025', 18000], ['122025', 18000], ['082026', 28000], ['092026', 28000], ['102026', 28000]]];

// 3. Caso meses salteados (pagó septiembre pero debe agosto):
// Patín Principiantes: Zoe Flores (7 años). Deuda: 082026 (30000), 102026 (30000). Septiembre pagado!
$raw[] = ['56300903', 'Flores', 'Zoe', '11/12/2018', '15/03/2025', '1145123416', 'claudia.flores@testmail.com', 'Claudia Flores', '1145123416', 'Patín', 'Principiantes', 'Patín / Principiantes / 1 clases', 'No', 'Sí', [['082026', 30000], ['102026', 30000]]];

// 4. Otro caso meses salteados:
// Fútbol Avanzadas: Felipe Sosa (13 años). Deuda: 072026 (38000), 082026 (38000), 102026 (38000). Septiembre pagado!
$raw[] = ['50300904', 'Sosa', 'Felipe', '03/03/2013', '05/04/2023', '1145123417', 'jorge.sosa@testmail.com', 'Jorge Sosa', '1145123417', 'Fútbol', 'Avanzadas', 'Fútbol / Avanzadas / 1 clases', 'No', 'Sí', [['072026', 38000], ['082026', 38000], ['102026', 38000]]];

// 5. Casos con pago parcial (lo que falta pagar):
// Patín Avanzadas: Lucia Ramirez (14 años). Plan 45000. Ya pagó 20000 de octubre, debe 25000. Deuda: 102026: 25000.
$raw[] = ['51300905', 'Ramirez', 'Lucia', '19/05/2012', '10/03/2024', '1145123418', 'silvia.ramirez@testmail.com', 'Silvia Ramirez', '1145123418', 'Patín', 'Avanzadas', 'Patín / Avanzadas / 2 clases', 'No', 'Sí', [['102026', 25000]]];

// Patín Intermedias: Helena Gimenez (12 años). Plan 43000. Debe 092026: 15000 (parcial), 102026: 43000.
$raw[] = ['52300906', 'Gimenez', 'Helena', '08/04/2014', '15/04/2023', '1145123419', 'raul.gimenez@testmail.com', 'Raul Gimenez', '1145123419', 'Patín', 'Intermedias', 'Patín / Intermedias / 2 clases', 'No', 'Sí', [['092026', 15000], ['102026', 43000]]];

// Patín Principiantes: Olivia Gutierrez (8 años). Plan 40000. Debe 102026: 18000 (parcial). Debe inscrip: Sí.
$raw[] = ['55300907', 'Gutierrez', 'Olivia', '27/07/2018', '01/09/2026', '1145123420', '', 'Mariela Gutierrez', '1145123420', 'Patín', 'Principiantes', 'Patín / Principiantes / 2 clases', 'Sí', 'Sí', [['102026', 18000]]];

// Fútbol Principiantes: Liam Pereyra (7 años). Plan 35000. Debe 102026: 12000 (parcial). Debe inscrip: Sí.
$raw[] = ['56300908', 'Pereyra', 'Liam', '14/10/2019', '01/09/2026', '1145123421', 'ana.pereyra@testmail.com', 'Ana Pereyra', '1145123421', 'Fútbol', 'Principiantes', 'Fútbol / Principiantes / 2 clases', 'Sí', 'Sí', [['102026', 12000]]];

// Fútbol Avanzadas: Dante Ramos (15 años). Plan 48000. Debe 092026: 20000 (parcial), 102026: 48000.
$raw[] = ['48300909', 'Ramos', 'Dante', '02/06/2011', '12/03/2023', '1145123422', 'sergio.ramos@testmail.com', 'Sergio Ramos', '1145123422', 'Fútbol', 'Avanzadas', 'Fútbol / Avanzadas / 2 clases', 'No', 'Sí', [['092026', 20000], ['102026', 48000]]];

// Patín Federadas: Guadalupe Nuñez (16 años). Plan 50000. Debe 102026: 22000 (parcial).
$raw[] = ['47300910', 'Nuñez', 'Guadalupe', '29/01/2010', '10/03/2022', '1145123423', 'monica.nunez@testmail.com', 'Monica Nuñez', '1145123423', 'Patín', 'Federadas', 'Patín / Federadas / 2 clases', 'No', 'Sí', [['102026', 22000]]];

// Con esto tenemos cubiertos todos los requerimientos especiales:
// - Hermanos (6 familias)
// - Dos deportes (Camila Benitez)
// - Mayores de edad (4 patin, 2 futbol)
// - Meses 2025 y 4 a 8 meses
// - Meses salteados
// - Pagos parciales (6 casos)
// - Debe inscripción (8 alumnos)

// Ahora completamos el resto de las filas para alcanzar exactamente:
// 60 Patín y 30 Fútbol.
// Proporción de deuda:
// - ~60% al día (No tiene deuda) -> unas 54 filas al día.
// - ~20% debe solo 102026 -> unas 18 filas.
// - ~12% debe 2 o 3 meses seguidos -> unas 11 filas.
// - ~5% debe 4 a 8 meses -> unas 4 filas.
// - ~3% meses salteados -> unas 3 filas.

// Conteo actual en $raw:
$patinCount = 0;
$futbolCount = 0;
foreach ($raw as $r) {
    if ($r[9] === 'Patín') $patinCount++;
    if ($r[9] === 'Fútbol') $futbolCount++;
}

// Completar Patín hasta 60:
// Queremos Principiantes (22 total), Intermedias (18 total), Avanzadas (12 total), Federadas (8 total).
$currentPatin = ['Principiantes' => 0, 'Intermedias' => 0, 'Avanzadas' => 0, 'Federadas' => 0];
foreach ($raw as $r) {
    if ($r[9] === 'Patín') $currentPatin[$r[10]]++;
}

// Generador de nombres extras de Patín
$extraPatin = [
    // Principiantes (faltan hasta 22)
    ['Victoria', 'Acosta', '12/03/2019', '15/03/2025', 'Principiantes', 'Patín / Principiantes / 1 clases', 'No', 'No', []],
    ['Renata', 'Flores', '05/07/2020', '10/04/2025', 'Principiantes', 'Patín / Principiantes / 1 clases', 'No', 'No', []],
    ['Emilia', 'Medina', '18/11/2018', '12/03/2024', 'Principiantes', 'Patín / Principiantes / 2 clases', 'No', 'No', []],
    ['Alma', 'Herrera', '22/01/2019', '15/04/2024', 'Principiantes', 'Patín / Principiantes / 2 clases', 'No', 'No', []],
    ['Juana', 'Suarez', '30/08/2020', '10/03/2025', 'Principiantes', 'Patín / Principiantes / 1 clases', 'No', 'No', []],
    ['Paulina', 'Gutierrez', '14/05/2019', '15/03/2025', 'Principiantes', 'Patín / Principiantes / 1 clases', 'No', 'Sí', [['102026', 30000]]],
    ['Bianca', 'Pereyra', '09/09/2018', '20/04/2024', 'Principiantes', 'Patín / Principiantes / 2 clases', 'No', 'Sí', [['102026', 40000]]],
    ['Pilar', 'Ramos', '17/02/2020', '12/03/2025', 'Principiantes', 'Patín / Principiantes / 1 clases', 'No', 'No', []],
    ['Clara', 'Iglesias', '25/06/2019', '10/04/2025', 'Principiantes', 'Patín / Principiantes / 1 clases', 'No', 'No', []],
    ['Morena', 'Rossi', '03/12/2018', '15/03/2024', 'Principiantes', 'Patín / Principiantes / 2 clases', 'No', 'Sí', [['092026', 40000], ['102026', 40000]]],
    ['Abril', 'Silva', '19/04/2020', '10/03/2025', 'Principiantes', 'Patín / Principiantes / 1 clases', 'No', 'No', []],
    ['Milagros', 'Molina', '08/08/2019', '15/04/2025', 'Principiantes', 'Patín / Principiantes / 1 clases', 'No', 'No', []],
    ['Micaela', 'Ortiz', '21/10/2018', '12/03/2024', 'Principiantes', 'Patín / Principiantes / 2 clases', 'No', 'No', []],
    ['Agustina', 'Morales', '11/01/2020', '15/03/2025', 'Principiantes', 'Patín / Principiantes / 1 clases', 'No', 'No', []],
    ['Lola', 'Rios', '29/03/2019', '10/04/2025', 'Principiantes', 'Patín / Principiantes / 1 clases', 'No', 'No', []],
    ['Luciana', 'Torres', '15/02/2020', '10/03/2025', 'Principiantes', 'Patín / Principiantes / 1 clases', 'No', 'No', []],
    ['Mia', 'Perez', '28/07/2019', '15/04/2025', 'Principiantes', 'Patín / Principiantes / 2 clases', 'No', 'No', []],
    ['Catalina', 'Romero', '04/11/2018', '12/03/2024', 'Principiantes', 'Patín / Principiantes / 1 clases', 'No', 'No', []],
    ['Lucia', 'Flores', '19/09/2019', '15/04/2025', 'Principiantes', 'Patín / Principiantes / 1 clases', 'No', 'No', []],

    // Intermedias (faltan hasta 18)
    ['Josefina', 'Ferrari', '14/07/2015', '10/03/2023', 'Intermedias', 'Patín / Intermedias / 1 clases', 'No', 'No', []],
    ['Malena', 'Navarro', '23/02/2016', '15/04/2023', 'Intermedias', 'Patín / Intermedias / 2 clases', 'No', 'No', []],
    ['Constanza', 'Cabrera', '05/11/2014', '12/03/2022', 'Intermedias', 'Patín / Intermedias / 1 clases', 'No', 'No', []],
    ['Paula', 'Ledesma', '19/08/2015', '10/03/2023', 'Intermedias', 'Patín / Intermedias / 2 clases', 'No', 'Sí', [['102026', 43000]]],
    ['Candela', 'Vega', '30/01/2016', '15/04/2024', 'Intermedias', 'Patín / Intermedias / 1 clases', 'No', 'No', []],
    ['Berenice', 'Sanchez', '12/06/2014', '12/03/2022', 'Intermedias', 'Patín / Intermedias / 2 clases', 'No', 'Sí', [['082026', 43000], ['092026', 43000], ['102026', 43000]]],
    ['Florencia', 'Romero', '04/10/2015', '10/03/2023', 'Intermedias', 'Patín / Intermedias / 1 clases', 'No', 'No', []],
    ['Rocio', 'Sosa', '22/04/2016', '15/04/2024', 'Intermedias', 'Patín / Intermedias / 1 clases', 'No', 'No', []],
    ['Sol', 'Ruiz', '16/12/2014', '12/03/2022', 'Intermedias', 'Patín / Intermedias / 2 clases', 'No', 'No', []],
    ['Carla', 'Perez', '09/03/2015', '10/03/2023', 'Intermedias', 'Patín / Intermedias / 1 clases', 'No', 'Sí', [['102026', 33000]]],
    ['Melina', 'Garcia', '27/09/2016', '15/04/2024', 'Intermedias', 'Patín / Intermedias / 2 clases', 'No', 'No', []],
    ['Juana', 'Flores', '18/05/2015', '10/03/2023', 'Intermedias', 'Patín / Intermedias / 1 clases', 'No', 'No', []],
    ['Paulina', 'Benitez', '01/02/2016', '12/03/2024', 'Intermedias', 'Patín / Intermedias / 2 clases', 'No', 'No', []],

    // Avanzadas (faltan hasta 12)
    ['Martina', 'Herrera', '15/08/2012', '10/03/2022', 'Avanzadas', 'Patín / Avanzadas / 2 clases', 'No', 'No', []],
    ['Valentina', 'Suarez', '20/03/2013', '15/04/2023', 'Avanzadas', 'Patín / Avanzadas / 1 clases', 'No', 'Sí', [['102026', 35000]]],
    ['Catalina', 'Castro', '11/11/2011', '12/03/2022', 'Avanzadas', 'Patín / Avanzadas / 2 clases', 'No', 'No', []],
    ['Delfina', 'Gimenez', '03/06/2013', '10/03/2023', 'Avanzadas', 'Patín / Avanzadas / 1 clases', 'No', 'No', []],
    ['Zoe', 'Pereyra', '29/01/2012', '15/04/2022', 'Avanzadas', 'Patín / Avanzadas / 2 clases', 'No', 'Sí', [['092026', 45000], ['102026', 45000]]],
    ['Camila', 'Alvarez', '14/10/2012', '10/03/2023', 'Avanzadas', 'Patín / Avanzadas / 1 clases', 'No', 'No', []],
    ['Milagros', 'Benitez', '05/02/2011', '12/03/2022', 'Avanzadas', 'Patín / Avanzadas / 2 clases', 'No', 'No', []],
    ['Sofia', 'Romero', '19/07/2013', '15/04/2023', 'Avanzadas', 'Patín / Avanzadas / 1 clases', 'No', 'No', []],

    // Federadas (faltan hasta 8)
    ['Lucia', 'Ramos', '14/05/2010', '10/03/2022', 'Federadas', 'Patín / Federadas / 2 clases', 'No', 'No', []],
    ['Helena', 'Iglesias', '22/09/2011', '15/04/2022', 'Federadas', 'Patín / Federadas / 1 clases', 'No', 'Sí', [['102026', 40000]]],
    ['Olivia', 'Rossi', '08/02/2010', '12/03/2022', 'Federadas', 'Patín / Federadas / 2 clases', 'No', 'No', []],
    ['Martina', 'Diaz', '17/08/2009', '10/03/2022', 'Federadas', 'Patín / Federadas / 2 clases', 'No', 'No', []],
    ['Valentina', 'Perez', '25/03/2010', '15/04/2022', 'Federadas', 'Patín / Federadas / 1 clases', 'No', 'No', []],
    ['Emma', 'Gonzalez', '09/11/2008', '12/03/2022', 'Federadas', 'Patín / Federadas / 2 clases', 'No', 'No', []]
];

$dniBasePatin = 55400000;
foreach ($extraPatin as $ep) {
    if ($currentPatin[$ep[4]] < ($ep[4] === 'Principiantes' ? 23 : ($ep[4] === 'Intermedias' ? 18 : ($ep[4] === 'Avanzadas' ? 12 : 8)))) {
        $dniBasePatin++;
        $currentPatin[$ep[4]]++;
        $tutorNom = 'Tutor ' . $ep[1];
        $tutorTel = '114512' . substr($dniBasePatin, -4);
        $raw[] = [
            (string)$dniBasePatin,
            $ep[1],
            $ep[0],
            $ep[2],
            $ep[3],
            $tutorTel,
            strtolower($ep[0] . '.' . $ep[1] . '@testmail.com'),
            $tutorNom,
            $tutorTel,
            'Patín',
            $ep[4],
            $ep[5],
            $ep[6],
            $ep[7],
            $ep[8]
        ];
    }
}

// Completar Fútbol hasta 30:
// Queremos Principiantes (18 total), Avanzadas (12 total).
$currentFutbol = ['Principiantes' => 0, 'Avanzadas' => 0];
foreach ($raw as $r) {
    if ($r[9] === 'Fútbol') $currentFutbol[$r[10]]++;
}

$extraFutbol = [
    // Principiantes (faltan hasta 18)
    ['Benicio', 'Torres', '12/04/2018', '10/03/2024', 'Principiantes', 'Fútbol / Principiantes / 1 clases', 'No', 'No', []],
    ['Thiago', 'Ruiz', '05/09/2019', '15/04/2025', 'Principiantes', 'Fútbol / Principiantes / 1 clases', 'No', 'No', []],
    ['Felipe', 'Ramirez', '18/01/2018', '12/03/2024', 'Principiantes', 'Fútbol / Principiantes / 2 clases', 'No', 'Sí', [['102026', 35000]]],
    ['Santino', 'Flores', '22/07/2019', '15/04/2025', 'Principiantes', 'Fútbol / Principiantes / 1 clases', 'No', 'No', []],
    ['Agustin', 'Acosta', '30/11/2017', '10/03/2024', 'Principiantes', 'Fútbol / Principiantes / 2 clases', 'No', 'No', []],
    ['Lautaro', 'Medina', '14/03/2018', '15/03/2024', 'Principiantes', 'Fútbol / Principiantes / 1 clases', 'No', 'No', []],
    ['Facundo', 'Herrera', '09/08/2019', '20/04/2025', 'Principiantes', 'Fútbol / Principiantes / 1 clases', 'No', 'No', []],
    ['Ignacio', 'Suarez', '17/12/2018', '12/03/2024', 'Principiantes', 'Fútbol / Principiantes / 2 clases', 'No', 'Sí', [['092026', 35000], ['102026', 35000]]],
    ['Bruno', 'Castro', '25/05/2019', '10/04/2025', 'Principiantes', 'Fútbol / Principiantes / 1 clases', 'No', 'No', []],
    ['Tomas', 'Gutierrez', '03/10/2018', '15/03/2024', 'Principiantes', 'Fútbol / Principiantes / 1 clases', 'No', 'No', []],
    ['Manuel', 'Pereyra', '19/02/2019', '10/03/2025', 'Principiantes', 'Fútbol / Principiantes / 2 clases', 'No', 'No', []],
    ['Santiago', 'Ramos', '08/06/2018', '15/04/2024', 'Principiantes', 'Fútbol / Principiantes / 1 clases', 'No', 'Sí', [['102026', 28000]]],

    // Avanzadas (faltan hasta 12)
    ['Leon', 'Rossi', '14/04/2012', '10/03/2023', 'Avanzadas', 'Fútbol / Avanzadas / 1 clases', 'No', 'No', []],
    ['Simon', 'Silva', '23/09/2013', '15/04/2023', 'Avanzadas', 'Fútbol / Avanzadas / 2 clases', 'No', 'No', []],
    ['Franco', 'Molina', '05/01/2011', '12/03/2022', 'Avanzadas', 'Fútbol / Avanzadas / 1 clases', 'No', 'Sí', [['102026', 38000]]],
    ['Tobias', 'Ortiz', '19/07/2012', '10/03/2023', 'Avanzadas', 'Fútbol / Avanzadas / 2 clases', 'No', 'No', []],
    ['Lorenzo', 'Morales', '30/11/2013', '15/04/2024', 'Avanzadas', 'Fútbol / Avanzadas / 1 clases', 'No', 'No', []],
    ['Ramiro', 'Ferrari', '12/03/2012', '12/03/2023', 'Avanzadas', 'Fútbol / Avanzadas / 2 clases', 'No', 'Sí', [['082026', 48000], ['092026', 48000], ['102026', 48000]]]
];

$dniBaseFutbol = 54400000;
foreach ($extraFutbol as $ef) {
    if ($currentFutbol[$ef[4]] < ($ef[4] === 'Principiantes' ? 18 : 12)) {
        $dniBaseFutbol++;
        $currentFutbol[$ef[4]]++;
        $tutorNom = 'Tutor ' . $ef[1];
        $tutorTel = '114512' . substr($dniBaseFutbol, -4);
        $raw[] = [
            (string)$dniBaseFutbol,
            $ef[1],
            $ef[0],
            $ef[2],
            $ef[3],
            $tutorTel,
            strtolower($ef[0] . '.' . $ef[1] . '@testmail.com'),
            $tutorNom,
            $tutorTel,
            'Fútbol',
            $ef[4],
            $ef[5],
            $ef[6],
            $ef[7],
            $ef[8]
        ];
    }
}

// Convertir $raw al formato plano de 38 columnas de la plantilla
foreach ($raw as $item) {
    $filaPlana = array_fill(0, 38, '');
    for ($i = 0; $i <= 13; $i++) {
        $filaPlana[$i] = $item[$i];
    }
    // Pares de deuda
    $pares = $item[14];
    $idx = 14;
    foreach ($pares as $par) {
        if ($idx < 38) {
            $filaPlana[$idx] = $par[0];     // Período
            $filaPlana[$idx + 1] = $par[1]; // Monto
            $idx += 2;
        }
    }
    $padronFilas[] = $filaPlana;
}

// Validar y reportar estadísticas
$totalPatin = 0;
$totalFutbol = 0;
$totalDeudaCuotas = 0;
$totalAlumnosConDeuda = 0;
$totalInscripciones = 0;

foreach ($padronFilas as $f) {
    if ($f[9] === 'Patín') $totalPatin++;
    if ($f[9] === 'Fútbol') $totalFutbol++;
    if ($f[12] === 'Sí') $totalInscripciones++;
    if ($f[13] === 'Sí') {
        $totalAlumnosConDeuda++;
        for ($p = 15; $p <= 37; $p += 2) {
            if (!empty($f[$p])) {
                $totalDeudaCuotas += (float)$f[$p];
            }
        }
    }
}

echo "Estadísticas del Padrón Generado:" . PHP_EOL;
echo "- Total Filas: " . count($padronFilas) . PHP_EOL;
echo "- Total Patín: $totalPatin" . PHP_EOL;
echo "- Total Fútbol: $totalFutbol" . PHP_EOL;
echo "- Alumnos que deben inscripción (Sí): $totalInscripciones" . PHP_EOL;
echo "- Alumnos con deuda de cuotas (Sí): $totalAlumnosConDeuda" . PHP_EOL;
echo "- Suma total de deuda de cuotas: $" . number_format($totalDeudaCuotas, 2, ',', '.') . PHP_EOL;
