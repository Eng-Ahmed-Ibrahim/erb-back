<?php

use Illuminate\Support\Facades\Route;
use Modules\MembershipCards\UI\Http\Controllers\OfficerController;
use Modules\MembershipCards\UI\Http\Controllers\BeneficiaryController;
use Modules\MembershipCards\UI\Http\Controllers\FeePlanController;
use Modules\MembershipCards\UI\Http\Controllers\SubscriptionController;
use Modules\MembershipCards\UI\Http\Controllers\MembershipCardController;
use Modules\MembershipCards\UI\Http\Controllers\AttachmentController;

/*
|--------------------------------------------------------------------------
| Membership Cards API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for the Membership Cards module.
| These routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('membership-cards')->group(function () {
    
    /*
    |--------------------------------------------------------------------------
    | Officer Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('officers')->group(function () {
        Route::get('/', [OfficerController::class, 'index']);
        Route::post('/', [OfficerController::class, 'store']);
        Route::get('/find', [OfficerController::class, 'findByIdentifier']);
        Route::get('/{id}', [OfficerController::class, 'show']);
        Route::put('/{id}', [OfficerController::class, 'update']);
        Route::post('/{id}', [OfficerController::class, 'update']); // POST with _method=PUT for FormData
        Route::delete('/{id}', [OfficerController::class, 'destroy']);
        
        // Nested beneficiaries routes
        Route::get('/{officerId}/beneficiaries', [BeneficiaryController::class, 'index']);
        Route::post('/{officerId}/beneficiaries', [BeneficiaryController::class, 'store']);
        Route::get('/{officerId}/beneficiaries/{id}', [BeneficiaryController::class, 'show']);
        Route::put('/{officerId}/beneficiaries/{id}', [BeneficiaryController::class, 'update']);
        Route::post('/{officerId}/beneficiaries/{id}', [BeneficiaryController::class, 'update']); // POST with _method=PUT for FormData
        Route::delete('/{officerId}/beneficiaries/{id}', [BeneficiaryController::class, 'destroy']);
        
        // Officer subscriptions
        Route::get('/{officerId}/subscriptions', [SubscriptionController::class, 'getByOfficer']);
        
        // Officer cards
        Route::get('/{officerId}/cards', [MembershipCardController::class, 'getByOfficer']);

        // Officer attachments
        Route::get('/{officerId}/attachments', [AttachmentController::class, 'getOfficerAttachments']);
        Route::post('/{officerId}/attachments', [AttachmentController::class, 'uploadForOfficer']);

        // Beneficiary attachments
        Route::get('/{officerId}/beneficiaries/{beneficiaryId}/attachments', [AttachmentController::class, 'getBeneficiaryAttachments']);
        Route::post('/{officerId}/beneficiaries/{beneficiaryId}/attachments', [AttachmentController::class, 'uploadForBeneficiary']);
    });
    
    /*
    |--------------------------------------------------------------------------
    | Fee Plan Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('fee-plans')->group(function () {
        Route::get('/', [FeePlanController::class, 'index']);
        Route::post('/', [FeePlanController::class, 'store']);
        Route::get('/weapon-type/{weaponType}', [FeePlanController::class, 'getByWeaponType']);
        Route::get('/type/{beneficiaryType}', [FeePlanController::class, 'getByBeneficiaryType']);
        Route::get('/{id}', [FeePlanController::class, 'show']);
        Route::put('/{id}', [FeePlanController::class, 'update']);
        Route::delete('/{id}', [FeePlanController::class, 'destroy']);
    });
    
    /*
    |--------------------------------------------------------------------------
    | Subscription Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('subscriptions')->group(function () {
        Route::get('/', [SubscriptionController::class, 'index']);
        Route::post('/', [SubscriptionController::class, 'store']);
        Route::get('/report', [SubscriptionController::class, 'report']);
        Route::get('/expiring', [SubscriptionController::class, 'expiring']);
        Route::post('/calculate-fees', [SubscriptionController::class, 'calculateFees']);
        Route::get('/{id}', [SubscriptionController::class, 'show']);
        Route::delete('/{id}', [SubscriptionController::class, 'destroy']);
        Route::post('/{id}/suspend', [SubscriptionController::class, 'suspend']);
        Route::post('/{id}/activate', [SubscriptionController::class, 'activate']);
        Route::post('/{id}/renew', [SubscriptionController::class, 'renew']);
    });
    
    /*
    |--------------------------------------------------------------------------
    | Membership Card Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('cards')->group(function () {
        Route::get('/', [MembershipCardController::class, 'index']);
        Route::post('/scan', [MembershipCardController::class, 'testScan']);
        Route::post('/', [MembershipCardController::class, 'store']);
        Route::post('/validate', [MembershipCardController::class, 'validate']);
        Route::post('/serial-id', [MembershipCardController::class, 'getCardSerialId']);
        Route::post('/read-membership-id', [MembershipCardController::class, 'readCardAndGetMembershipId']);
        Route::post('/write', [MembershipCardController::class, 'writeCardData']);
        Route::get('/replacement-fee', [MembershipCardController::class, 'getReplacementFee']);
        Route::put('/replacement-fee', [MembershipCardController::class, 'updateReplacementFee']);
        Route::post('/replacement', [MembershipCardController::class, 'issueReplacement']);
        Route::get('/expiring', [MembershipCardController::class, 'expiring']);
        Route::get('/not-printed', [MembershipCardController::class, 'notPrinted']);
        Route::get('/not-encoded', [MembershipCardController::class, 'notEncoded']);
        Route::get('/subscription/{subscriptionId}', [MembershipCardController::class, 'getBySubscription']);
        Route::get('/token/{token}', [MembershipCardController::class, 'findByToken']);
        Route::get('/token-hex/{tokenHex}', [MembershipCardController::class, 'findByTokenHex']);
        Route::get('/{id}', [MembershipCardController::class, 'show']);
        Route::post('/{id}/print', [MembershipCardController::class, 'markPrinted']);
        Route::post('/{id}/encode', [MembershipCardController::class, 'markEncoded']);
        Route::post('/{id}/revoke', [MembershipCardController::class, 'revoke']);
    });

    /*
    |--------------------------------------------------------------------------
    | Attachment Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('attachments')->group(function () {
        Route::get('/{id}', [AttachmentController::class, 'show']);
        Route::put('/{id}', [AttachmentController::class, 'update']);
        Route::post('/{id}', [AttachmentController::class, 'update']); // POST with _method=PUT for FormData
        Route::delete('/{id}', [AttachmentController::class, 'destroy']);
    });

    /*
    |--------------------------------------------------------------------------
    | Reports Routes (Future Implementation)
    |--------------------------------------------------------------------------
    */
    Route::prefix('reports')->group(function () {
        Route::get('/membership', function () {
            return response()->json([
                'success' => false,
                'message' => 'Reports feature coming soon'
            ], 501);
        });
        
        Route::get('/financial', function () {
            return response()->json([
                'success' => false,
                'message' => 'Reports feature coming soon'
            ], 501);
        });
        
        Route::get('/operational', function () {
            return response()->json([
                'success' => false,
                'message' => 'Reports feature coming soon'
            ], 501);
        });
    });
});

