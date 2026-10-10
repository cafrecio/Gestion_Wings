Add-Type -AssemblyName System.Windows.Forms
Add-Type -AssemblyName System.Drawing

$excel = New-Object -ComObject Excel.Application
$excel.Visible = $true
$excel.WindowState = -4137 # xlMaximized

$wbPath = "C:\xampp\htdocs\Gestion_Wings\docs\06-pruebas\PRU-03\plantilla-original.xlsx"
$wb = $excel.Workbooks.Open($wbPath)
Start-Sleep -Seconds 2

# Capturar ventana principal (Primary: 0,0, 1920, 1080 o recortada a 1366x768)
function Capture-Screen($fileName, $width=1366, $height=768) {
    $bmp = New-Object System.Drawing.Bitmap $width, $height
    $graphics = [System.Drawing.Graphics]::FromImage($bmp)
    $graphics.CopyFromScreen(0, 0, 0, 0, (New-Object System.Drawing.Size $width, $height))
    $dest = "C:\xampp\htdocs\Gestion_Wings\docs\06-pruebas\PRU-03\capturas\$fileName"
    $bmp.Save($dest, [System.Drawing.Imaging.ImageFormat]::Png)
    $graphics.Dispose()
    $bmp.Dispose()
    Write-Output "Guardado: $dest"
}

# 1. Hoja Alumnos recién abierta
$wsAlumnos = $wb.Sheets.Item("Alumnos")
$wsAlumnos.Activate()
Start-Sleep -Seconds 1
Capture-Screen "excel-01-plantilla-alumnos.png"

# 2. Hoja Guía
$wsGuia = $wb.Sheets.Item("Guía")
$wsGuia.Activate()
Start-Sleep -Seconds 1
Capture-Screen "excel-02-hoja-guia.png"

# 3. Hoja Catálogos
$wsCat = $wb.Sheets.Item("Catálogos")
$wsCat.Activate()
Start-Sleep -Seconds 1
Capture-Screen "excel-03-hoja-catalogos.png"

$wb.Close($false)
$excel.Quit()
[System.Runtime.Interopservices.Marshal]::ReleaseComObject($excel) | Out-Null
Write-Output "Capturas base de Excel completadas."
