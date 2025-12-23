$ErrorActionPreference = 'Stop'
$base = 'http://127.0.0.1:8001/api/v1'

function Get-DotEnvValue([string]$Key) {
    $envPath = Join-Path $PSScriptRoot '.env'
    if (-not (Test-Path $envPath)) {
        return $null
    }

    $line = Get-Content $envPath | Where-Object { $_ -match ("^\s*" + [regex]::Escape($Key) + "\s*=") } | Select-Object -First 1
    if (-not $line) {
        return $null
    }

    $value = $line.Substring($line.IndexOf('=') + 1).Trim()
    if (($value.StartsWith('"') -and $value.EndsWith('"')) -or ($value.StartsWith("'") -and $value.EndsWith("'"))) {
        $value = $value.Substring(1, $value.Length - 2)
    }

    return $value
}

function Show-Section($title) {
    Write-Host "`n=== $title ===" -ForegroundColor Cyan
}

function Invoke-WebRequestCompat {
    param(
        [Parameter(Mandatory = $true)][string]$Uri,
        [Parameter(Mandatory = $true)][hashtable]$Headers
    )

    if ($PSVersionTable.PSVersion.Major -lt 6) {
        return Invoke-WebRequest -Method Get -Uri $Uri -Headers $Headers -UseBasicParsing
    }

    return Invoke-WebRequest -Method Get -Uri $Uri -Headers $Headers
}

Show-Section 'Health'
$health = Invoke-RestMethod -Method Get -Uri "$base/health" -Headers @{ Accept = 'application/json' }
$health | ConvertTo-Json -Depth 5

$loginBody = @{
    email    = 'admin@local.test'
    password = 'admin123456'
} | ConvertTo-Json

Show-Section 'Login'
$login = Invoke-RestMethod -Method Post -Uri "$base/auth/login" -ContentType 'application/json' -Body $loginBody
$login | ConvertTo-Json -Depth 5
$token = $login.token

if (-not $token) {
    throw 'Login did not return a token.'
}

Show-Section 'Current User'
$user = Invoke-RestMethod -Method Get -Uri "$base/user" -Headers @{ Authorization = "Bearer $token"; Accept = 'application/json' }
$user | ConvertTo-Json -Depth 5

Show-Section 'Addresses - Create (auth)'
$addressBody = @{
    label = 'API Test Address'
    first_name = 'John'
    last_name = 'Doe'
    phone = '+33102030405'
    line1 = '1 Rue de Test'
    city = 'Paris'
    postal_code = '75001'
    country_code = 'FR'
    is_default_shipping = $true
    is_default_billing = $true
} | ConvertTo-Json
$createdAddress = Invoke-RestMethod -Method Post -Uri "$base/addresses" -ContentType 'application/json' -Body $addressBody -Headers @{ Authorization = "Bearer $token"; Accept = 'application/json' }
$createdAddress | ConvertTo-Json -Depth 6
$createdAddressId = $createdAddress.data.id
if (-not $createdAddressId) {
    throw 'Address create did not return an id.'
}

Show-Section 'Addresses - List (auth)'
$addresses = Invoke-RestMethod -Method Get -Uri "$base/addresses" -Headers @{ Authorization = "Bearer $token"; Accept = 'application/json' }
$addresses | ConvertTo-Json -Depth 6

Show-Section 'Addresses - Show (auth)'
$shownAddress = Invoke-RestMethod -Method Get -Uri "$base/addresses/$createdAddressId" -Headers @{ Authorization = "Bearer $token"; Accept = 'application/json' }
$shownAddress | ConvertTo-Json -Depth 6

Show-Section 'Addresses - Update (auth)'
$updateAddressBody = @{
    label = 'API Test Address Updated'
    phone = '+33111111111'
} | ConvertTo-Json
$updatedAddress = Invoke-RestMethod -Method Patch -Uri "$base/addresses/$createdAddressId" -ContentType 'application/json' -Body $updateAddressBody -Headers @{ Authorization = "Bearer $token"; Accept = 'application/json' }
$updatedAddress | ConvertTo-Json -Depth 6

Show-Section 'Categories - List (public)'
$categoriesList = Invoke-RestMethod -Method Get -Uri "$base/categories" -Headers @{ Accept = 'application/json' }
$categoriesList | ConvertTo-Json -Depth 5
$existingCategoryId = $categoriesList.data[0].id

Show-Section 'Categories - Show (public)'
$shownCategory = Invoke-RestMethod -Method Get -Uri "$base/categories/$existingCategoryId" -Headers @{ Accept = 'application/json' }
$shownCategory | ConvertTo-Json -Depth 5

Show-Section 'Categories - Create (auth)'
$newCategoryBody = @{
    name = "API Test Category $(Get-Random -Maximum 9999)"
    description = 'Created via invoke_api_tests.ps1'
    is_active = $true
    position = 99
} | ConvertTo-Json
$createdCategory = Invoke-RestMethod -Method Post -Uri "$base/categories" -ContentType 'application/json' -Body $newCategoryBody -Headers @{ Authorization = "Bearer $token"; Accept = 'application/json' }
$createdCategory | ConvertTo-Json -Depth 5
$createdCategoryId = $null
if ($createdCategory.PSObject.Properties.Name -contains 'id') {
    $createdCategoryId = $createdCategory.id
}
elseif ($createdCategory.PSObject.Properties.Name -contains 'data' -and $createdCategory.data.id) {
    $createdCategoryId = $createdCategory.data.id
}
if (-not $createdCategoryId -and ($createdCategory | Get-Member -Name 'First' -MemberType Method)) {
    try { $createdCategoryId = ($createdCategory | Select-Object -First 1).id } catch { }
}
if (-not $createdCategoryId) {
    throw "Unable to determine created category id from response: $($createdCategory | ConvertTo-Json -Depth 5)"
}

Show-Section 'Categories - Update (auth)'
$updateCategoryBody = @{
    name = 'API Test Category Updated'
    is_active = $false
} | ConvertTo-Json
$updatedCategory = Invoke-RestMethod -Method Patch -Uri "$base/categories/$createdCategoryId" -ContentType 'application/json' -Body $updateCategoryBody -Headers @{ Authorization = "Bearer $token"; Accept = 'application/json' }
$updatedCategory | ConvertTo-Json -Depth 5

Show-Section 'Categories - Delete (auth)'
Invoke-RestMethod -Method Delete -Uri "$base/categories/$createdCategoryId" -Headers @{ Authorization = "Bearer $token"; Accept = 'application/json' }
Write-Host "Category $createdCategoryId deleted." -ForegroundColor Yellow

Show-Section 'Products - List (public)'
$productsList = Invoke-RestMethod -Method Get -Uri "$base/products" -Headers @{ Accept = 'application/json' }
$productsList | ConvertTo-Json -Depth 5
$existingProductId = $null
if ($productsList -and $productsList.data -and $productsList.data.Count -gt 0) {
    $existingProductId = $productsList.data[0].id
}

Show-Section 'Products - List (filters)'
$productsFiltered = Invoke-RestMethod -Method Get -Uri "$base/products?has_stock=true&price_min=0&price_max=999999&sort=created_at&direction=desc" -Headers @{ Accept = 'application/json' }
$productsFiltered | ConvertTo-Json -Depth 5

if ($existingProductId) {
    Show-Section 'Products - Show (public)'
    $shownProduct = Invoke-RestMethod -Method Get -Uri "$base/products/$existingProductId" -Headers @{ Accept = 'application/json' }
    $shownProduct | ConvertTo-Json -Depth 5
}

Show-Section 'Products - Create (auth)'
$productSku = "SKU-TEST-{0}" -f (Get-Random -Maximum 999999)
$newProductBody = @{
    category_id    = $existingCategoryId
    type           = 'simple'
    sku            = $productSku
    name           = "API Test Product $(Get-Random -Maximum 9999)"
    price          = 199.99
    stock_quantity = 5
    stock_status   = 'in_stock'
    is_active      = $true
} | ConvertTo-Json
$createdProduct = Invoke-RestMethod -Method Post -Uri "$base/products" -ContentType 'application/json' -Body $newProductBody -Headers @{ Authorization = "Bearer $token"; Accept = 'application/json' }
$createdProduct | ConvertTo-Json -Depth 5
$createdProductId = $null
if ($createdProduct.PSObject.Properties.Name -contains 'id') {
    $createdProductId = $createdProduct.id
}
elseif ($createdProduct.PSObject.Properties.Name -contains 'data' -and $createdProduct.data.id) {
    $createdProductId = $createdProduct.data.id
}
if (-not $createdProductId) {
    throw "Unable to determine created product id from response: $($createdProduct | ConvertTo-Json -Depth 5)"
}

if (-not $existingProductId) {
    $existingProductId = $createdProductId
}

Show-Section 'Products - Update (auth)'
$updateProductBody = @{
    name      = 'API Test Product Updated'
    price     = 149.99
    is_active = $true
} | ConvertTo-Json
$updatedProduct = Invoke-RestMethod -Method Patch -Uri "$base/products/$createdProductId" -ContentType 'application/json' -Body $updateProductBody -Headers @{ Authorization = "Bearer $token"; Accept = 'application/json' }
$updatedProduct | ConvertTo-Json -Depth 5

Show-Section 'Products - Export (auth, CSV)'
$productsExport = Invoke-WebRequestCompat -Uri "$base/products/export?sort=created_at&direction=desc" -Headers @{ Authorization = "Bearer $token"; Accept = 'text/csv' }
Write-Host "Export status: $($productsExport.StatusCode)" -ForegroundColor Yellow
if ($productsExport.Content) {
    ($productsExport.Content -split "`n" | Select-Object -First 3) | ForEach-Object { Write-Host $_ }
}

Show-Section 'Cart - Guest - Get'
$guestCart = Invoke-RestMethod -Method Get -Uri "$base/cart" -Headers @{ Accept = 'application/json' }
$guestCart | ConvertTo-Json -Depth 6
$guestToken = $guestCart.data.guest_token
if (-not $guestToken) {
    throw "Guest cart did not return a guest_token."
}

if ($guestCart.data.totals.grand_total -ne '0.00') {
    throw "Expected empty guest cart grand_total=0.00, got $($guestCart.data.totals.grand_total)"
}

Show-Section 'Cart - Guest - Add Item'
$guestAddBody = @{
    purchasable_type = 'product'
    purchasable_id   = $createdProductId
    quantity         = 1
} | ConvertTo-Json
$guestCartAfterAdd = Invoke-RestMethod -Method Post -Uri "$base/cart/items" -ContentType 'application/json' -Body $guestAddBody -Headers @{ Accept = 'application/json'; 'X-Cart-Token' = $guestToken }
$guestCartAfterAdd | ConvertTo-Json -Depth 6
$guestItemId = $guestCartAfterAdd.data.items[0].id
if (-not $guestItemId) {
    throw 'Unable to determine guest cart item id.'
}

if ($guestCartAfterAdd.data.totals.subtotal -ne '149.99') {
    throw "Expected subtotal=149.99 after add, got $($guestCartAfterAdd.data.totals.subtotal)"
}
if ($guestCartAfterAdd.data.totals.grand_total -ne '149.99') {
    throw "Expected grand_total=149.99 after add, got $($guestCartAfterAdd.data.totals.grand_total)"
}

Show-Section 'Cart - Guest - Update Item Qty'
$guestUpdateBody = @{ quantity = 2 } | ConvertTo-Json
$guestCartAfterUpdate = Invoke-RestMethod -Method Patch -Uri "$base/cart/items/$guestItemId" -ContentType 'application/json' -Body $guestUpdateBody -Headers @{ Accept = 'application/json'; 'X-Cart-Token' = $guestToken }
$guestCartAfterUpdate | ConvertTo-Json -Depth 6

if ($guestCartAfterUpdate.data.totals.subtotal -ne '299.98') {
    throw "Expected subtotal=299.98 after qty update, got $($guestCartAfterUpdate.data.totals.subtotal)"
}
if ($guestCartAfterUpdate.data.totals.grand_total -ne '299.98') {
    throw "Expected grand_total=299.98 after qty update, got $($guestCartAfterUpdate.data.totals.grand_total)"
}

Show-Section 'Cart - Merge (auth)'
$mergeBody = @{ guest_token = $guestToken } | ConvertTo-Json
$mergedCart = Invoke-RestMethod -Method Post -Uri "$base/cart/merge" -ContentType 'application/json' -Body $mergeBody -Headers @{ Accept = 'application/json'; Authorization = "Bearer $token" }
$mergedCart | ConvertTo-Json -Depth 6

if ($mergedCart.data.totals.grand_total -ne '299.98') {
    throw "Expected merged cart grand_total=299.98, got $($mergedCart.data.totals.grand_total)"
}

Show-Section 'Orders - Create (auth)'
$orderBody = @{
    shipping_address_id = $createdAddressId
    billing_address_id  = $createdAddressId
} | ConvertTo-Json
$createdOrder = Invoke-RestMethod -Method Post -Uri "$base/orders" -ContentType 'application/json' -Body $orderBody -Headers @{ Accept = 'application/json'; Authorization = "Bearer $token" }
$createdOrder | ConvertTo-Json -Depth 8
$createdOrderId = $createdOrder.data.id
if (-not $createdOrderId) {
    throw 'Order create did not return an id.'
}

if ($createdOrder.data.totals.grand_total -ne '299.98') {
    throw "Expected order grand_total=299.98, got $($createdOrder.data.totals.grand_total)"
}

if (-not $createdOrder.data.items -or $createdOrder.data.items.Count -lt 1) {
    throw 'Expected order to contain at least 1 item.'
}

if ($createdOrder.data.items[0].quantity -ne 2) {
    throw "Expected order item quantity=2, got $($createdOrder.data.items[0].quantity)"
}

Show-Section 'Payments - Create (auth)'
$createdPayment = Invoke-RestMethod -Method Post -Uri "$base/orders/$createdOrderId/payments" -Headers @{ Accept = 'application/json'; Authorization = "Bearer $token" }
$createdPayment | ConvertTo-Json -Depth 6

$createdPaymentId = $createdPayment.data.id
if (-not $createdPaymentId) {
    throw 'Payment create did not return an id.'
}

if ($createdPayment.data.order_id -ne $createdOrderId) {
    throw "Expected payment order_id=$createdOrderId, got $($createdPayment.data.order_id)"
}

if ($createdPayment.data.provider -ne 'fake') {
    if (@('fake', 'stripe', 'paypal', 'cod') -notcontains $createdPayment.data.provider) {
        throw "Expected payment provider in [fake,stripe,paypal,cod], got $($createdPayment.data.provider)"
    }
}

if ($createdPayment.data.provider -eq 'paypal') {
    if (-not $createdPayment.data.provider_reference) {
        throw 'Expected PayPal provider_reference (order id) to be present.'
    }

    if (-not $createdPayment.data.checkout_url) {
        throw 'Expected PayPal checkout_url (approval_url) to be present.'
    }

    if (($createdPayment.data.checkout_url -notlike '*paypal*') -or ($createdPayment.data.checkout_url -notlike '*checkoutnow*')) {
        throw "Expected PayPal checkout_url to look like a sandbox approval link, got $($createdPayment.data.checkout_url)"
    }
}

if ($createdPayment.data.provider -ne 'cod') {
    if ((-not $createdPayment.data.client_secret) -and (-not $createdPayment.data.checkout_url)) {
        throw 'Expected payment client_secret or checkout_url to be present for non-COD payments.'
    }
}

if ($createdPayment.data.provider -eq 'stripe') {
    if (-not $createdPayment.data.provider_reference -or ($createdPayment.data.provider_reference -notlike 'pi_*')) {
        throw "Expected Stripe provider_reference to start with pi_, got $($createdPayment.data.provider_reference)"
    }

    if (-not $createdPayment.data.client_secret -or ($createdPayment.data.client_secret -notmatch '^pi_.*_secret_')) {
        throw 'Expected Stripe client_secret to look like pi_..._secret_...'
    }
}

if ($createdPayment.data.provider -eq 'stripe') {
    Show-Section 'Webhooks - Stripe - Simulate payment_intent.succeeded'

    $eventId = "evt_test_{0}" -f (Get-Random -Maximum 999999)
    $webhookPayload = @{
        id   = $eventId
        type = 'payment_intent.succeeded'
        data = @{
            object = @{
                id = $createdPayment.data.provider_reference
            }
        }
    }

    $webhookJson = $webhookPayload | ConvertTo-Json -Depth 10

    $webhookHeaders = @{ Accept = 'application/json' }
    $stripeWebhookSecret = $env:STRIPE_WEBHOOK_SECRET
    if (-not $stripeWebhookSecret) {
        $stripeWebhookSecret = Get-DotEnvValue 'STRIPE_WEBHOOK_SECRET'
    }
    if ($stripeWebhookSecret) {
        $ts = [DateTimeOffset]::UtcNow.ToUnixTimeSeconds()
        $signedPayload = "{0}.{1}" -f $ts, $webhookJson
        $keyBytes = [Text.Encoding]::UTF8.GetBytes($stripeWebhookSecret)
        $hmac = New-Object System.Security.Cryptography.HMACSHA256 -ArgumentList (, $keyBytes)
        $hashBytes = $hmac.ComputeHash([Text.Encoding]::UTF8.GetBytes($signedPayload))
        $sig = ($hashBytes | ForEach-Object { $_.ToString('x2') }) -join ''
        $webhookHeaders['Stripe-Signature'] = "t=$ts,v1=$sig"
    }

    $webhookResp = Invoke-RestMethod -Method Post -Uri "$base/webhooks/stripe" -ContentType 'application/json' -Body $webhookJson -Headers $webhookHeaders
    $webhookResp | ConvertTo-Json -Depth 5

    $shownOrderAfterWebhook = Invoke-RestMethod -Method Get -Uri "$base/orders/$createdOrderId" -Headers @{ Accept = 'application/json'; Authorization = "Bearer $token" }
    $shownOrderAfterWebhook | ConvertTo-Json -Depth 8

    if ($shownOrderAfterWebhook.data.status -ne 'paid') {
        throw "Expected order status=paid after Stripe webhook, got $($shownOrderAfterWebhook.data.status)"
    }
}

if ($createdPayment.data.provider -eq 'cod') {
    if (-not $createdPayment.data.provider_reference -or ($createdPayment.data.provider_reference -notlike 'cod_*')) {
        throw "Expected COD provider_reference to start with cod_, got $($createdPayment.data.provider_reference)"
    }

    if ($createdPayment.data.status -ne 'pending_cod') {
        Write-Host "Warning: COD payment status is $($createdPayment.data.status), expected pending_cod (may be configured differently)" -ForegroundColor Yellow
    }

    $orderAfterCod = Invoke-RestMethod -Method Get -Uri "$base/orders/$createdOrderId" -Headers @{ Accept = 'application/json'; Authorization = "Bearer $token" }
    if ($orderAfterCod.data.status -ne 'pending_cod') {
        Write-Host "Warning: Order status after COD is $($orderAfterCod.data.status), expected pending_cod (may be configured differently)" -ForegroundColor Yellow
    }
}

if ($createdPayment.data.amount -ne $createdOrder.data.totals.grand_total) {
    throw "Expected payment amount=$($createdOrder.data.totals.grand_total), got $($createdPayment.data.amount)"
}

Show-Section 'Orders - List (auth)'
$ordersList = Invoke-RestMethod -Method Get -Uri "$base/orders" -Headers @{ Accept = 'application/json'; Authorization = "Bearer $token" }
$ordersList | ConvertTo-Json -Depth 6

Show-Section 'Orders - Show (auth)'
$shownOrder = Invoke-RestMethod -Method Get -Uri "$base/orders/$createdOrderId" -Headers @{ Accept = 'application/json'; Authorization = "Bearer $token" }
$shownOrder | ConvertTo-Json -Depth 8

Show-Section 'Orders - Get Allowed Transitions (auth)'
$allowedTransitions = Invoke-RestMethod -Method Get -Uri "$base/orders/$createdOrderId/allowed-transitions" -Headers @{ Accept = 'application/json'; Authorization = "Bearer $token" }
$allowedTransitions | ConvertTo-Json -Depth 5

if (-not $allowedTransitions.current_status) {
    throw 'Expected current_status in allowed-transitions response'
}

if (-not $allowedTransitions.allowed_transitions) {
    throw 'Expected allowed_transitions array in response'
}

Show-Section 'Orders - Update Status to Processing (auth - admin only)'
try {
    $statusUpdateBody = @{
        status = 'processing'
        reason = 'Payment confirmed via API test'
    } | ConvertTo-Json

    $updatedOrder = Invoke-RestMethod -Method Patch -Uri "$base/orders/$createdOrderId/status" -Headers @{ Accept = 'application/json'; Authorization = "Bearer $token" } -ContentType 'application/json' -Body $statusUpdateBody
    $updatedOrder | ConvertTo-Json -Depth 8

    if ($updatedOrder.data.status -ne 'processing') {
        throw "Expected order status=processing, got $($updatedOrder.data.status)"
    }

    Write-Host "Order status successfully updated to: $($updatedOrder.data.status)" -ForegroundColor Green
}
catch {
    if ($_.Exception.Response.StatusCode -eq 403) {
        Write-Host "Status update forbidden (expected if user is not admin)" -ForegroundColor Yellow
    }
    else {
        throw
    }
}

Show-Section 'Orders - Cancel Order (auth)'
$cancelBody = @{
    reason = 'Test cancellation via API'
} | ConvertTo-Json

try {
    $cancelledOrder = Invoke-RestMethod -Method Post -Uri "$base/orders/$createdOrderId/cancel" -Headers @{ Accept = 'application/json'; Authorization = "Bearer $token" } -ContentType 'application/json' -Body $cancelBody
    $cancelledOrder | ConvertTo-Json -Depth 8

    if ($cancelledOrder.data.status -ne 'cancelled') {
        throw "Expected order status=cancelled, got $($cancelledOrder.data.status)"
    }

    Write-Host "Order successfully cancelled" -ForegroundColor Green
}
catch {
    if ($_.Exception.Response.StatusCode -eq 403) {
        Write-Host "Cancellation forbidden (order may not be in pending/pending_cod status)" -ForegroundColor Yellow
    }
    else {
        throw
    }
}

Show-Section 'Cart - User - Get'
$userCart = Invoke-RestMethod -Method Get -Uri "$base/cart" -Headers @{ Accept = 'application/json'; Authorization = "Bearer $token" }
$userCart | ConvertTo-Json -Depth 6
$userItemId = $null
if ($userCart.data.items -and $userCart.data.items.Count -gt 0) {
    $userItemId = $userCart.data.items[0].id
}

if ($userItemId) {
    Show-Section 'Cart - User - Remove Item'
    $userCartAfterRemove = Invoke-RestMethod -Method Delete -Uri "$base/cart/items/$userItemId" -Headers @{ Accept = 'application/json'; Authorization = "Bearer $token" }
    $userCartAfterRemove | ConvertTo-Json -Depth 6

    if ($userCartAfterRemove.data.totals.grand_total -ne '0.00') {
        throw "Expected grand_total=0.00 after remove, got $($userCartAfterRemove.data.totals.grand_total)"
    }
}

Show-Section 'Products - Import (auth, CSV) (dry-run)'
$curlCmd = Get-Command curl.exe -ErrorAction SilentlyContinue
if (-not $curlCmd) {
    Write-Host 'curl.exe not found, skipping import smoke tests.' -ForegroundColor Yellow
}
else {
    $tmpProductsCsv = Join-Path $env:TEMP ("products_import_{0}.csv" -f (Get-Random -Maximum 999999))
    $importSku = "IMP-SKU-{0}" -f (Get-Random -Maximum 999999)
    "sku,name,price,stock_quantity,stock_status" | Out-File -FilePath $tmpProductsCsv -Encoding utf8
    "$importSku,Import Test Product,10.50,2,in_stock" | Add-Content -Path $tmpProductsCsv -Encoding utf8

    $importJson = & curl.exe -s -X POST "$base/products/import" `
        -H "Authorization: Bearer $token" `
        -H "Accept: application/json" `
        -F "file=@$tmpProductsCsv" `
        -F "dry_run=true" `
        -F "update_existing=false" `
        -F "delimiter=," 

    $importResult = $importJson | ConvertFrom-Json
    if ($importResult.status -ne 'ok') {
        throw "Products import dry-run failed: $importJson"
    }
    $importResult | ConvertTo-Json -Depth 6

    Remove-Item $tmpProductsCsv -ErrorAction SilentlyContinue
}

Show-Section 'Variants - List (public)'
$variantsList = Invoke-RestMethod -Method Get -Uri "$base/products/$existingProductId/variants" -Headers @{ Accept = 'application/json' }
$variantsList | ConvertTo-Json -Depth 5
$existingVariantId = $null
if ($variantsList -and $variantsList.data -and $variantsList.data.Count -gt 0) {
    $existingVariantId = $variantsList.data[0].id
}

Show-Section 'Variants - List (filters)'
$variantsFiltered = Invoke-RestMethod -Method Get -Uri "$base/products/$existingProductId/variants?has_stock=true&price_min=0&price_max=999999&sort=name&direction=asc" -Headers @{ Accept = 'application/json' }
$variantsFiltered | ConvertTo-Json -Depth 5

Show-Section 'Variants - Export (auth, CSV)'
$variantsExport = Invoke-WebRequestCompat -Uri "$base/products/$existingProductId/variants/export?sort=name&direction=asc" -Headers @{ Authorization = "Bearer $token"; Accept = 'text/csv' }
Write-Host "Export status: $($variantsExport.StatusCode)" -ForegroundColor Yellow
if ($variantsExport.Content) {
    ($variantsExport.Content -split "`n" | Select-Object -First 3) | ForEach-Object { Write-Host $_ }
}

Show-Section 'Variants - Import (auth, CSV) (dry-run)'
if ($curlCmd) {
    $tmpVariantsCsv = Join-Path $env:TEMP ("variants_import_{0}.csv" -f (Get-Random -Maximum 999999))
    $importVarSku = "IMP-VAR-{0}" -f (Get-Random -Maximum 999999)
    "sku,name,price,stock_quantity,stock_status" | Out-File -FilePath $tmpVariantsCsv -Encoding utf8
    "$importVarSku,Import Test Variant,5.99,1,in_stock" | Add-Content -Path $tmpVariantsCsv -Encoding utf8

    $varImportJson = & curl.exe -s -X POST "$base/products/$existingProductId/variants/import" `
        -H "Authorization: Bearer $token" `
        -H "Accept: application/json" `
        -F "file=@$tmpVariantsCsv" `
        -F "dry_run=true" `
        -F "update_existing=false" `
        -F "delimiter=," 

    $varImportResult = $varImportJson | ConvertFrom-Json
    if ($varImportResult.status -ne 'ok') {
        throw "Variants import dry-run failed: $varImportJson"
    }
    $varImportResult | ConvertTo-Json -Depth 6

    Remove-Item $tmpVariantsCsv -ErrorAction SilentlyContinue
}

if ($existingVariantId) {
    Show-Section 'Variants - Show (public)'
    $shownVariant = Invoke-RestMethod -Method Get -Uri "$base/products/$existingProductId/variants/$existingVariantId" -Headers @{ Accept = 'application/json' }
    $shownVariant | ConvertTo-Json -Depth 5
}

Show-Section 'Variants - Create (auth)'
$variantSku = "VAR-TEST-{0}" -f (Get-Random -Maximum 999999)
$newVariantBody = @{
    sku            = $variantSku
    name           = "API Test Variant $(Get-Random -Maximum 9999)"
    price          = 29.99
    stock_quantity = 2
    stock_status   = 'in_stock'
    is_active      = $true
} | ConvertTo-Json
$createdVariant = Invoke-RestMethod -Method Post -Uri "$base/products/$existingProductId/variants" -ContentType 'application/json' -Body $newVariantBody -Headers @{ Authorization = "Bearer $token"; Accept = 'application/json' }
$createdVariant | ConvertTo-Json -Depth 5
$createdVariantId = $null
if ($createdVariant.PSObject.Properties.Name -contains 'id') {
    $createdVariantId = $createdVariant.id
}
elseif ($createdVariant.PSObject.Properties.Name -contains 'data' -and $createdVariant.data.id) {
    $createdVariantId = $createdVariant.data.id
}
if (-not $createdVariantId) {
    throw "Unable to determine created variant id from response: $($createdVariant | ConvertTo-Json -Depth 5)"
}

Show-Section 'Variants - Update (auth)'
$updateVariantBody = @{
    name      = 'API Test Variant Updated'
    price     = 24.99
    is_active = $false
} | ConvertTo-Json
$updatedVariant = Invoke-RestMethod -Method Patch -Uri "$base/products/$existingProductId/variants/$createdVariantId" -ContentType 'application/json' -Body $updateVariantBody -Headers @{ Authorization = "Bearer $token"; Accept = 'application/json' }
$updatedVariant | ConvertTo-Json -Depth 5

Show-Section 'Variants - Delete (auth)'
Invoke-RestMethod -Method Delete -Uri "$base/products/$existingProductId/variants/$createdVariantId" -Headers @{ Authorization = "Bearer $token"; Accept = 'application/json' }
Write-Host "Variant $createdVariantId deleted." -ForegroundColor Yellow

Show-Section 'Shipping - Get Methods'
$shippingMethods = Invoke-RestMethod -Method Get -Uri "$base/shipping/methods" -Headers @{ Accept = 'application/json' }
$shippingMethods | ConvertTo-Json -Depth 5

if (-not $shippingMethods.data -or $shippingMethods.data.Count -eq 0) {
    throw 'Expected shipping methods to be returned'
}

Show-Section 'Shipping - Calculate Options (France)'
$shippingCalcBody = @{
    country_code = 'FR'
    postal_code  = '75001'
    cart_total   = 45.00
    cart_weight  = 2.5
} | ConvertTo-Json

$shippingOptions = Invoke-RestMethod -Method Post -Uri "$base/shipping/calculate" -ContentType 'application/json' -Body $shippingCalcBody -Headers @{ Accept = 'application/json' }
$shippingOptions | ConvertTo-Json -Depth 6

if (-not $shippingOptions.data -or $shippingOptions.data.Count -eq 0) {
    throw 'Expected shipping options to be returned for France'
}

$standardOption = $shippingOptions.data | Where-Object { $_.method_key -eq 'standard' } | Select-Object -First 1
if ($standardOption) {
    if ($standardOption.price -ne 5.90) {
        throw "Expected standard shipping price=5.90 for cart under 50€, got $($standardOption.price)"
    }
    Write-Host "Standard shipping: $($standardOption.price)€" -ForegroundColor Green
}

Show-Section 'Shipping - Calculate with Free Threshold'
$shippingFreeBody = @{
    country_code = 'FR'
    postal_code  = '75001'
    cart_total   = 60.00
} | ConvertTo-Json

$shippingFree = Invoke-RestMethod -Method Post -Uri "$base/shipping/calculate" -ContentType 'application/json' -Body $shippingFreeBody -Headers @{ Accept = 'application/json' }
$shippingFree | ConvertTo-Json -Depth 6

$standardFree = $shippingFree.data | Where-Object { $_.method_key -eq 'standard' } | Select-Object -First 1
if ($standardFree -and $standardFree.price -ne 0.0) {
    throw "Expected free shipping for cart above 50€, got $($standardFree.price)"
}

if ($standardFree -and $standardFree.is_free -ne $true) {
    throw 'Expected is_free=true for free shipping'
}

Write-Host "Free shipping applied for cart above 50€" -ForegroundColor Green

Show-Section 'Shipping - Calculate Specific Method'
$shippingMethodBody = @{
    method_key = 'standard'
    zone_key   = 'france_metro'
    cart_total = 45.00
} | ConvertTo-Json

$shippingMethodCost = Invoke-RestMethod -Method Post -Uri "$base/shipping/calculate-method" -ContentType 'application/json' -Body $shippingMethodBody -Headers @{ Accept = 'application/json' }
$shippingMethodCost | ConvertTo-Json -Depth 5

if ($shippingMethodCost.data.cost -ne 5.90) {
    throw "Expected method cost=5.90, got $($shippingMethodCost.data.cost)"
}

Show-Section 'Coupons - Admin CRUD'
$couponCode = "API-COUPON-{0}" -f (Get-Random -Maximum 999999)
$couponBody = @{
    code                 = $couponCode
    type                 = 'fixed'
    value                = 10.00
    min_order_amount     = 0
    max_discount_amount  = 20.00
    usage_limit          = 10
    usage_limit_per_user = 1
    is_active            = $true
} | ConvertTo-Json
$createdCoupon = Invoke-RestMethod -Method Post -Uri "$base/admin/coupons" -ContentType 'application/json' -Body $couponBody -Headers @{ Authorization = "Bearer $token"; Accept = 'application/json' }
$createdCoupon | ConvertTo-Json -Depth 5
$couponId = $createdCoupon.data.id
if (-not $couponId) {
    throw 'Coupon create did not return an id.'
}

$couponList = Invoke-RestMethod -Method Get -Uri "$base/admin/coupons" -Headers @{ Authorization = "Bearer $token"; Accept = 'application/json' }
$couponList | ConvertTo-Json -Depth 5

$couponUpdateBody = @{
    value = 15.00
} | ConvertTo-Json
$updatedCoupon = Invoke-RestMethod -Method Patch -Uri "$base/admin/coupons/$couponId" -ContentType 'application/json' -Body $couponUpdateBody -Headers @{ Authorization = "Bearer $token"; Accept = 'application/json' }
$updatedCoupon | ConvertTo-Json -Depth 5

Invoke-RestMethod -Method Delete -Uri "$base/admin/coupons/$couponId" -Headers @{ Authorization = "Bearer $token"; Accept = 'application/json' }
Write-Host "Coupon $couponId deleted." -ForegroundColor Yellow

Show-Section 'Promotions - Admin CRUD'
$promotionBody = @{
    name                 = "API Promotion $(Get-Random -Maximum 9999)"
    type                 = 'product'
    discount_type        = 'percentage'
    discount_value       = 20
    applicable_products  = @($existingProductId)
    priority             = 50
    is_active            = $true
} | ConvertTo-Json -Depth 5
$createdPromotion = Invoke-RestMethod -Method Post -Uri "$base/admin/promotions" -ContentType 'application/json' -Body $promotionBody -Headers @{ Authorization = "Bearer $token"; Accept = 'application/json' }
$createdPromotion | ConvertTo-Json -Depth 5
$promotionId = $createdPromotion.data.id
if (-not $promotionId) {
    throw 'Promotion create did not return an id.'
}

$publicPromotions = Invoke-RestMethod -Method Get -Uri "$base/promotions" -Headers @{ Accept = 'application/json' }
$publicPromotions | ConvertTo-Json -Depth 5

$promotionUpdateBody = @{
    discount_value = 30
} | ConvertTo-Json
$updatedPromotion = Invoke-RestMethod -Method Patch -Uri "$base/admin/promotions/$promotionId" -ContentType 'application/json' -Body $promotionUpdateBody -Headers @{ Authorization = "Bearer $token"; Accept = 'application/json' }
$updatedPromotion | ConvertTo-Json -Depth 5

Invoke-RestMethod -Method Delete -Uri "$base/admin/promotions/$promotionId" -Headers @{ Authorization = "Bearer $token"; Accept = 'application/json' }
Write-Host "Promotion $promotionId deleted." -ForegroundColor Yellow

Show-Section 'Pages - Public + Admin CRUD'
$pageSlug = "api-page-{0}" -f (Get-Random -Maximum 999999)
$pageBody = @{
    title            = "API Test Page $(Get-Random -Maximum 9999)"
    slug             = $pageSlug
    content          = '<p>Content generated via invoke_api_tests.ps1</p>'
    meta_description = 'API test page'
    meta_keywords    = @('api','test','page')
    is_published     = $true
    order            = 5
} | ConvertTo-Json -Depth 5
$createdPage = Invoke-RestMethod -Method Post -Uri "$base/admin/pages" -ContentType 'application/json' -Body $pageBody -Headers @{ Authorization = "Bearer $token"; Accept = 'application/json' }
$createdPage | ConvertTo-Json -Depth 5
$pageId = $createdPage.data.id
if (-not $pageId) {
    throw 'Page create did not return an id.'
}

$publicPages = Invoke-RestMethod -Method Get -Uri "$base/pages" -Headers @{ Accept = 'application/json' }
$publicPages | ConvertTo-Json -Depth 5

$shownPage = Invoke-RestMethod -Method Get -Uri "$base/pages/$pageSlug" -Headers @{ Accept = 'application/json' }
$shownPage | ConvertTo-Json -Depth 5

$pageUpdateBody = @{
    title   = 'API Test Page Updated'
    content = '<p>Updated content</p>'
} | ConvertTo-Json
$updatedPage = Invoke-RestMethod -Method Patch -Uri "$base/admin/pages/$pageId" -ContentType 'application/json' -Body $pageUpdateBody -Headers @{ Authorization = "Bearer $token"; Accept = 'application/json' }
$updatedPage | ConvertTo-Json -Depth 5

Invoke-RestMethod -Method Delete -Uri "$base/admin/pages/$pageId" -Headers @{ Authorization = "Bearer $token"; Accept = 'application/json' }
Write-Host "Page $pageId deleted." -ForegroundColor Yellow

Show-Section 'Blocks - Public + Admin CRUD'
$blockKey = "api-block-{0}" -f (Get-Random -Maximum 999999)
$blockBody = @{
    key   = $blockKey
    type  = 'slider'
    title = 'Homepage Slider'
    content = @{
        slides = @(
            @{
                image = 'https://picsum.photos/1200/600'
                title = 'API Slide'
                link  = '/products'
            }
        )
        autoplay = $true
        interval = 5000
    }
    order     = 1
    is_active = $true
} | ConvertTo-Json -Depth 6
$createdBlock = Invoke-RestMethod -Method Post -Uri "$base/admin/blocks" -ContentType 'application/json' -Body $blockBody -Headers @{ Authorization = "Bearer $token"; Accept = 'application/json' }
$createdBlock | ConvertTo-Json -Depth 6
$blockId = $createdBlock.data.id
if (-not $blockId) {
    throw 'Block create did not return an id.'
}

$publicBlocks = Invoke-RestMethod -Method Get -Uri "$base/blocks" -Headers @{ Accept = 'application/json' }
$publicBlocks | ConvertTo-Json -Depth 5

$shownBlock = Invoke-RestMethod -Method Get -Uri "$base/blocks/$blockKey" -Headers @{ Accept = 'application/json' }
$shownBlock | ConvertTo-Json -Depth 5

$blockUpdateBody = @{
    title = 'Homepage Slider Updated'
} | ConvertTo-Json
$updatedBlock = Invoke-RestMethod -Method Patch -Uri "$base/admin/blocks/$blockId" -ContentType 'application/json' -Body $blockUpdateBody -Headers @{ Authorization = "Bearer $token"; Accept = 'application/json' }
$updatedBlock | ConvertTo-Json -Depth 5

Invoke-RestMethod -Method Delete -Uri "$base/admin/blocks/$blockId" -Headers @{ Authorization = "Bearer $token"; Accept = 'application/json' }
Write-Host "Block $blockId deleted." -ForegroundColor Yellow

Show-Section 'Stock - Decrement (auth)'
$stockDecrementBody = @{
    stockable_type = 'product'
    stockable_id   = $createdProductId
    quantity       = 1
    reason         = 'sale'
    description    = 'API test decrement'
    metadata       = @{
        source = 'invoke_api_tests.ps1'
    }
} | ConvertTo-Json -Depth 6
$stockDecrement = Invoke-RestMethod -Method Post -Uri "$base/stock/decrement" -ContentType 'application/json' -Body $stockDecrementBody -Headers @{ Authorization = "Bearer $token"; Accept = 'application/json' }
$stockDecrement | ConvertTo-Json -Depth 6

Show-Section 'Stock - Movements (auth)'
$movements = Invoke-RestMethod -Method Get -Uri "$base/stock-movements?stockable_type=product&stockable_id=$createdProductId" -Headers @{ Authorization = "Bearer $token"; Accept = 'application/json' }
$movements | ConvertTo-Json -Depth 6

Show-Section 'Products - Delete (auth)'
Invoke-RestMethod -Method Delete -Uri "$base/products/$createdProductId" -Headers @{ Authorization = "Bearer $token"; Accept = 'application/json' }
Write-Host "Product $createdProductId deleted." -ForegroundColor Yellow

if ($createdAddressId) {
    Show-Section 'Addresses - Delete (auth)'
    Invoke-RestMethod -Method Delete -Uri "$base/addresses/$createdAddressId" -Headers @{ Authorization = "Bearer $token"; Accept = 'application/json' }
    Write-Host "Address $createdAddressId deleted." -ForegroundColor Yellow
}

Show-Section 'Config - i18n (public)'
$i18nConfig = Invoke-RestMethod -Method Get -Uri "$base/config/i18n" -Headers @{ Accept = 'application/json' }
$i18nConfig | ConvertTo-Json -Depth 5

Show-Section 'Pages - Localized (public, ?lang=en)'
$pagesEn = Invoke-RestMethod -Method Get -Uri "$base/pages?lang=en" -Headers @{ Accept = 'application/json' }
$pagesEn | ConvertTo-Json -Depth 5

Show-Section 'Pages - Localized (public, ?lang=ar)'
$pagesAr = Invoke-RestMethod -Method Get -Uri "$base/pages?lang=ar" -Headers @{ Accept = 'application/json' }
$pagesAr | ConvertTo-Json -Depth 5

Show-Section 'Products - Localized (public, ?lang=en)'
$productsEn = Invoke-RestMethod -Method Get -Uri "$base/products?lang=en&per_page=5" -Headers @{ Accept = 'application/json' }
$productsEn | ConvertTo-Json -Depth 5

Show-Section 'Promotions - Localized (public, ?lang=ar)'
$promotionsAr = Invoke-RestMethod -Method Get -Uri "$base/promotions?lang=ar" -Headers @{ Accept = 'application/json' }
$promotionsAr | ConvertTo-Json -Depth 5

Show-Section 'Logout'
$logout = Invoke-RestMethod -Method Post -Uri "$base/auth/logout" -Headers @{ Authorization = "Bearer $token"; Accept = 'application/json' }
$logout | ConvertTo-Json -Depth 5
