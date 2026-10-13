Add-Type -AssemblyName System.Drawing

$dishes = @(
    @{ Name = "dish-shrimp.jpg"; Title = "Grilled Tiger Shrimp"; Sub = "Lemon Garlic Herb Butter"; Bg1 = "#ff7e5f"; Bg2 = "#feb47b"; Icon = "SEAFOOD" },
    @{ Name = "drink-coke.jpg"; Title = "Premium Coca-Cola"; Sub = "Glass Bottle 330ml on Ice"; Bg1 = "#cb2d3e"; Bg2 = "#ef473a"; Icon = "BEVERAGE" },
    @{ Name = "dish-yogurt.jpg"; Title = "Greek Yogurt Parfait"; Sub = "Wild Honey & Granola"; Bg1 = "#3a7bd5"; Bg2 = "#3a6073"; Icon = "DESSERT" },
    @{ Name = "dish-dessert.jpg"; Title = "Vanilla Panna Cotta"; Sub = "Raspberry Pomegranate Reduction"; Bg1 = "#cc2b5e"; Bg2 = "#753a88"; Icon = "DESSERT" },
    @{ Name = "drink-water.jpg"; Title = "San Pellegrino Sparkling"; Sub = "750ml with Fresh Lime"; Bg1 = "#00c6ff"; Bg2 = "#0072ff"; Icon = "BEVERAGE" },
    @{ Name = "dish-bruschetta.jpg"; Title = "Truffle Mushroom Bruschetta"; Sub = "Rustic Sourdough"; Bg1 = "#e65c00"; Bg2 = "#F9D423"; Icon = "APPETIZER" },
    @{ Name = "dish-cheese.jpg"; Title = "Farmstead Cheese Board"; Sub = "Gouda, Brie & Honeycomb"; Bg1 = "#f12711"; Bg2 = "#f5af19"; Icon = "APPETIZER" },
    @{ Name = "dish-macaron.jpg"; Title = "Handcrafted Macarons"; Sub = "Artisan Box of 6"; Bg1 = "#8A2387"; Bg2 = "#E94057"; Icon = "DESSERT" }
)

$targetDir = "C:\xampp\htdocs\venuepro\assets"
if (!(Test-Path $targetDir)) { New-Item -ItemType Directory -Path $targetDir | Out-Null }

foreach ($d in $dishes) {
    $bmp = New-Object System.Drawing.Bitmap(600, 400)
    $g = [System.Drawing.Graphics]::FromImage($bmp)
    $g.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias
    $g.TextRenderingHint = [System.Drawing.Text.TextRenderingHint]::ClearTypeGridFit

    $c1 = [System.Drawing.ColorTranslator]::FromHtml($d.Bg1)
    $c2 = [System.Drawing.ColorTranslator]::FromHtml($d.Bg2)
    $rect = New-Object System.Drawing.Rectangle(0, 0, 600, 400)
    $brush = New-Object System.Drawing.Drawing2D.LinearGradientBrush($rect, $c1, $c2, 45)
    $g.FillRectangle($brush, $rect)

    # Dark translucent overlay at bottom for contrast
    $overlayBrush = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(160, 15, 23, 42))
    $g.FillRectangle($overlayBrush, 0, 240, 600, 160)

    # Category Pill
    $pillBrush = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(230, 255, 255, 255))
    $pillTextBrush = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(255, 15, 23, 42))
    $pillFont = New-Object System.Drawing.Font("Segoe UI", 12, [System.Drawing.FontStyle]::Bold)
    $g.FillRectangle($pillBrush, 40, 40, 150, 36)
    $g.DrawString($d.Icon, $pillFont, $pillTextBrush, 50, 46)

    # Title text
    $titleFont = New-Object System.Drawing.Font("Segoe UI", 24, [System.Drawing.FontStyle]::Bold)
    $titleBrush = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::White)
    $g.DrawString($d.Title, $titleFont, $titleBrush, 40, 260)

    # Subtitle text
    $subFont = New-Object System.Drawing.Font("Segoe UI", 14, [System.Drawing.FontStyle]::Regular)
    $subBrush = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(255, 203, 213, 225))
    $g.DrawString($d.Sub, $subFont, $subBrush, 40, 315)

    $outPath = Join-Path $targetDir $d.Name
    $bmp.Save($outPath, [System.Drawing.Imaging.ImageFormat]::Jpeg)
    $g.Dispose()
    $bmp.Dispose()
    Write-Host "Created: $($d.Name)"
}
