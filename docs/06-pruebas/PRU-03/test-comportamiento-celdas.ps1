$app = New-Object -ComObject Excel.Application
$app.Visible = $false
$wb = $app.Workbooks.Open('C:\xampp\htdocs\Gestion_Wings\docs\06-pruebas\PRU-03\plantilla-original.xlsx')
$sh = $wb.Sheets.Item(1)

# Probar ingresar en fila 2:
# DNI con puntos y espacios
$sh.Range("A2").Value2 = "50.300.001"
# Fecha nacimiento
$sh.Range("D2").Value2 = "15/05/2015"
# Fecha ingreso
$sh.Range("E2").Value2 = "10/03/2024"
# Periodo con cero adelante como texto o numero
$sh.Range("O2").Value2 = "082026"
$sh.Range("P2").Value2 = 48000

Write-Output "Valores leídos inmediatamente:"
Write-Output "A2 (DNI): $($sh.Range('A2').Value2) | Text: $($sh.Range('A2').Text)"
Write-Output "D2 (Nac): $($sh.Range('D2').Value2) | Text: $($sh.Range('D2').Text)"
Write-Output "E2 (Ing): $($sh.Range('E2').Value2) | Text: $($sh.Range('E2').Text)"
Write-Output "O2 (Per): $($sh.Range('O2').Value2) | Text: $($sh.Range('O2').Text)"
Write-Output "P2 (Mon): $($sh.Range('P2').Value2) | Text: $($sh.Range('P2').Text)"

$wb.Close($false)
$app.Quit()
[System.Runtime.InteropServices.Marshal]::ReleaseComObject($app) | Out-Null
