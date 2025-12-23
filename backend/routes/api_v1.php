<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AddressController;
use App\Http\Controllers\Api\V1\BlockController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CouponController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\OrderReturnController;
use App\Http\Controllers\Api\V1\OrderStatusController;
use App\Http\Controllers\Api\V1\OrderTrackingController;
use App\Http\Controllers\Api\V1\PageController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PromotionController;
use App\Http\Controllers\Api\V1\ShippingController;
use App\Http\Controllers\Api\V1\StripeWebhookController;
use App\Http\Controllers\Api\V1\ProductImportExportController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ProductVariantController;
use App\Http\Controllers\Api\V1\StockController;
use App\Http\Controllers\Api\V1\StockMovementController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'version' => 'v1']);
});

Route::get('/config/i18n', [App\Http\Controllers\Api\V1\ConfigController::class, 'i18n']);

Route::post('webhooks/stripe', StripeWebhookController::class);

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('categories', [CategoryController::class, 'index']);
Route::get('categories/{category}', [CategoryController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('addresses', [AddressController::class, 'index']);
    Route::post('addresses', [AddressController::class, 'store']);
    Route::get('addresses/{address}', [AddressController::class, 'show']);
    Route::patch('addresses/{address}', [AddressController::class, 'update']);
    Route::delete('addresses/{address}', [AddressController::class, 'destroy']);

    Route::get('orders', [OrderController::class, 'index']);
    Route::post('orders', [OrderController::class, 'store']);
    Route::get('orders/{order}', [OrderController::class, 'show']);
    Route::post('orders/{order}/payments', [PaymentController::class, 'store']);
    Route::get('orders/{order}/allowed-transitions', [OrderStatusController::class, 'allowedTransitions']);
    Route::patch('orders/{order}/status', [OrderStatusController::class, 'update']);
    Route::post('orders/{order}/cancel', [OrderStatusController::class, 'cancel']);
    
    Route::get('orders/{order}/tracking', [OrderTrackingController::class, 'show']);
    Route::patch('orders/{order}/tracking', [OrderTrackingController::class, 'update']);
    Route::post('orders/{order}/mark-delivered', [OrderTrackingController::class, 'markDelivered']);
    
    Route::get('returns', [OrderReturnController::class, 'index']);
    Route::get('returns/{orderReturn}', [OrderReturnController::class, 'show']);
    Route::post('orders/{order}/returns', [OrderReturnController::class, 'store']);
    Route::post('returns/{orderReturn}/approve', [OrderReturnController::class, 'approve']);
    Route::post('returns/{orderReturn}/reject', [OrderReturnController::class, 'reject']);
    Route::post('returns/{orderReturn}/mark-received', [OrderReturnController::class, 'markReceived']);
    Route::post('returns/{orderReturn}/mark-refunded', [OrderReturnController::class, 'markRefunded']);
    Route::patch('returns/{orderReturn}/tracking', [OrderReturnController::class, 'addTracking']);

    Route::post('cart/merge', [CartController::class, 'merge']);
    
    Route::post('coupons/validate', [CouponController::class, 'validate']);

    Route::middleware(['admin.audit', 'auth:sanctum'])->group(function () {
        Route::get('admin/coupons', [CouponController::class, 'index']);
        Route::post('admin/coupons', [CouponController::class, 'store']);
        Route::get('admin/coupons/{coupon}', [CouponController::class, 'show']);
        Route::patch('admin/coupons/{coupon}', [CouponController::class, 'update']);
        Route::delete('admin/coupons/{coupon}', [CouponController::class, 'destroy']);
        
        Route::get('admin/promotions', [PromotionController::class, 'indexAdmin']);
        Route::post('admin/promotions', [PromotionController::class, 'store']);
        Route::get('admin/promotions/{promotion}', [PromotionController::class, 'show']);
        Route::patch('admin/promotions/{promotion}', [PromotionController::class, 'update']);
        Route::delete('admin/promotions/{promotion}', [PromotionController::class, 'destroy']);
        
        Route::get('admin/pages', [PageController::class, 'indexAdmin']);
        Route::post('admin/pages', [PageController::class, 'store']);
        Route::patch('admin/pages/{page}', [PageController::class, 'update']);
        Route::delete('admin/pages/{page}', [PageController::class, 'destroy']);
        
        Route::get('admin/blocks', [BlockController::class, 'indexAdmin']);
        Route::post('admin/blocks', [BlockController::class, 'store']);
        Route::patch('admin/blocks/{block}', [BlockController::class, 'update']);
        Route::delete('admin/blocks/{block}', [BlockController::class, 'destroy']);
        Route::post('categories', [CategoryController::class, 'store']);
        Route::patch('categories/{category}', [CategoryController::class, 'update']);
        Route::delete('categories/{category}', [CategoryController::class, 'destroy']);

        Route::get('products/export', [ProductImportExportController::class, 'export']);
        Route::post('products/import', [ProductImportExportController::class, 'import']);
        Route::get('products/{product}/variants/export', [ProductImportExportController::class, 'exportVariants']);
        Route::post('products/{product}/variants/import', [ProductImportExportController::class, 'importVariants']);

        Route::post('products', [ProductController::class, 'store']);
        Route::patch('products/{product}', [ProductController::class, 'update']);
        Route::delete('products/{product}', [ProductController::class, 'destroy']);
        Route::post('products/{product}/variants', [ProductVariantController::class, 'store']);
        Route::patch('products/{product}/variants/{variant}', [ProductVariantController::class, 'update']);
        Route::delete('products/{product}/variants/{variant}', [ProductVariantController::class, 'destroy']);

        Route::get('stock-movements', [StockMovementController::class, 'index']);
        Route::post('stock/decrement', [StockController::class, 'decrement']);
    });
});

Route::get('products', [ProductController::class, 'index']);
Route::get('products/{product}', [ProductController::class, 'show']);
Route::get('products/{product}/variants', [ProductVariantController::class, 'index']);
Route::get('products/{product}/variants/{variant}', [ProductVariantController::class, 'show']);

Route::get('cart', [CartController::class, 'show']);
Route::post('cart/items', [CartController::class, 'addItem']);
Route::patch('cart/items/{item}', [CartController::class, 'updateItem']);
Route::delete('cart/items/{item}', [CartController::class, 'removeItem']);

Route::get('shipping/methods', [ShippingController::class, 'methods']);
Route::post('shipping/calculate', [ShippingController::class, 'calculate']);
Route::post('shipping/calculate-method', [ShippingController::class, 'calculateMethod']);

Route::get('pages', [PageController::class, 'index']);
Route::get('pages/{slug}', [PageController::class, 'show']);

Route::get('blocks', [BlockController::class, 'index']);
Route::get('blocks/{key}', [BlockController::class, 'show']);

Route::get('promotions', [PromotionController::class, 'index']);
