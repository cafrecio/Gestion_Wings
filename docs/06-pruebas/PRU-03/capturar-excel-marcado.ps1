Add-Type -AssemblyName System.Windows.Forms
Add-Type -AssemblyName System.Drawing

function Export-RangeImage($range, $outFile) {
    $range.CopyPicture(1, 2)
    Start-Sleep -Milliseconds 250
    if ([System.Windows.Forms.Clipboard]::ContainsImage()) {
        $img = [System.Windows.Forms.Clipboard]::GetImage()
        $img.Save($outFile, [System.Drawing.Imaging.ImageFormat]::Png)
        $img.Dispose()
        Write-Output "Guardado: $outFile"
    } else {
        Write-Output "Error al capturar $outFile"
    }
}

$app = New-Object -ComObject Excel.Application
$app.Visible = $false

# 1. Excel marcado con columna Errores a la vista
$wbMarc = $app.Workbooks.Open('C:\xampp\htdocs\Gestion_Wings\docs\06-pruebas\PRU-03\PADRON-PRU-03-v2-marcado-errores.xlsx')
$shMarc = $wbMarc.Sheets.Item("Alumnos")
Export-RangeImage $shMarc.Range("AL1:AM18") "C:\xampp\htdocs\Gestion_Wings\docs\06-pruebas\PRU-03\capturas\excel-09-marcado-con-errores.png"
$wbMarc.Close($false)

# 2. Desplegable de Deporte, Grupo y Plan
# Mostramos el rango de Catálogos de donde provienen y la columna de Alumnos
$wbPlant = $app.Workbooks.Open('C:\xampp\htdocs\Gestion_Wings\docs\06-pruebas\PRU-03\plantilla-original.xlsx')
$shAlum = $wbPlant.Sheets.Item("Alumnos")
Export-RangeImage $shAlum.Range("I1:M10") "C:\xampp\htdocs\Gestion_Wings\docs\06-pruebas\PRU-03\capturas\excel-04-desplegable-deporte-grupo-plan.png"

$shCat = $wbPlant.Sheets.Item(2)
Export-RangeImage $shCat.Range("F1:H14") "C:\xampp\htdocs\Gestion_Wings\docs\06-pruebas\PRU-03\capturas\excel-05-listas-catalogos-origen.png"
$wbPlant.Close($false)

$app.Quit()
[System.Runtime.InteropServices.Marshal]::ReleaseComObject($app) | Out-Null
Write-Output "Capturas de Excel adicionales finalizadas."
