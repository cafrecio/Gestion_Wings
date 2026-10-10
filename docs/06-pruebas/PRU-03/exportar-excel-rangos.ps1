Add-Type -AssemblyName System.Windows.Forms
Add-Type -AssemblyName System.Drawing

function Export-RangeImage($range, $outFile) {
    # Copiar rango como imagen al portapapeles
    # 1 = xlScreen, 2 = xlBitmap
    $range.CopyPicture(1, 2)
    Start-Sleep -Milliseconds 250
    if ([System.Windows.Forms.Clipboard]::ContainsImage()) {
        $img = [System.Windows.Forms.Clipboard]::GetImage()
        $img.Save($outFile, [System.Drawing.Imaging.ImageFormat]::Png)
        $img.Dispose()
        Write-Output "Guardado: $outFile"
    } else {
        Write-Output "Error: Portapapeles no contiene imagen para $outFile"
    }
}

$app = New-Object -ComObject Excel.Application
$app.Visible = $false
$wb = $app.Workbooks.Open('C:\xampp\htdocs\Gestion_Wings\docs\06-pruebas\PRU-03\plantilla-original.xlsx')

# 1. Hoja Alumnos recién abierta (primeras columnas A a N, filas 1 a 15)
$shAlumnos = $wb.Sheets.Item("Alumnos")
Export-RangeImage $shAlumnos.Range("A1:N16") "C:\xampp\htdocs\Gestion_Wings\docs\06-pruebas\PRU-03\capturas\excel-01-plantilla-alumnos.png"

# 2. Hoja Guía (A1:I23) -> Hoja 3
$shGuia = $wb.Sheets.Item(3)
Export-RangeImage $shGuia.Range("A1:I24") "C:\xampp\htdocs\Gestion_Wings\docs\06-pruebas\PRU-03\capturas\excel-02-hoja-guia.png"

# 3. Hoja Catálogos (A1:I15) -> Hoja 2
$shCat = $wb.Sheets.Item(2)
Export-RangeImage $shCat.Range("A1:I15") "C:\xampp\htdocs\Gestion_Wings\docs\06-pruebas\PRU-03\capturas\excel-03-hoja-catalogos.png"

$wb.Close($false)

# 4. Fila completa de alumno al día y fila con deuda en PADRON-PRU-03-v2.xlsx
$wb2 = $app.Workbooks.Open('C:\xampp\htdocs\Gestion_Wings\docs\06-pruebas\PRU-03\PADRON-PRU-03-v2.xlsx')
$sh2 = $wb2.Sheets.Item("Alumnos")

# Alumno al día (Fila 2: Sofia Gomez o Fila 92: Julieta Morales)
Export-RangeImage $sh2.Range("A1:R5") "C:\xampp\htdocs\Gestion_Wings\docs\06-pruebas\PRU-03\capturas\excel-07-fila-alumno-al-dia.png"

# Alumno con deuda de varios meses (Fila 6: Emma Fernandez o Fila 95: Joaquin Vega)
Export-RangeImage $sh2.Range("A1:V8") "C:\xampp\htdocs\Gestion_Wings\docs\06-pruebas\PRU-03\capturas\excel-08-fila-con-deuda-varios-meses.png"

$wb2.Close($false)

$app.Quit()
[System.Runtime.InteropServices.Marshal]::ReleaseComObject($app) | Out-Null
Write-Output "Exportación de rangos de Excel finalizada."
