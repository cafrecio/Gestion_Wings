$app = New-Object -ComObject Excel.Application
$app.Visible = $false
$wb = $app.Workbooks.Open('C:\xampp\htdocs\Gestion_Wings\docs\06-pruebas\PRU-03\plantilla-original.xlsx')

Write-Output "Hojas en el libro:"
for ($i = 1; $i -le $wb.Sheets.Count; $i++) {
    Write-Output "[$i] $($wb.Sheets.Item($i).Name)"
}

$sh = $wb.Sheets.Item(1)
Write-Output "`nValidaciones en fila 2:"
$cols = @('J', 'K', 'L', 'M', 'N')
foreach ($c in $cols) {
    $r = $sh.Range("${c}2")
    try {
        $vType = $r.Validation.Type
        $vF1 = $r.Validation.Formula1
        Write-Output "Col ${c} - Tipo=$vType, Formula=$vF1"
    } catch {
        Write-Output "Col ${c} - Sin validacion"
    }
}

$wb.Close($false)
$app.Quit()
[System.Runtime.InteropServices.Marshal]::ReleaseComObject($app) | Out-Null
