<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class ModelHasModel extends Model implements Auditable
{
    use HasFactory , \OwenIt\Auditing\Auditable;

    protected $table = 'model_has_model';

    protected $fillable = [
        'source_model',
        'source_model_id',
        'operation',
        'target_model',
        'target_model_id',
    ];

    const Review_Operation = 'review';
    const ORDERS_CLIENT_TYPE_FILTER_OPERATION = 'orders_client_type_filter';
    const ORDERS_PAYMENT_METHOD_FILTER_OPERATION = 'orders_payment_method_filter';

    /**
     * All distinct target IDs for this user and operation (multiple rows allowed).
     *
     * @return array<int, string>
     */
    public static function targetIdsForOperation(string $sourceModelId, string $operation): array
    {
        return self::query()
            ->where('source_model_id', $sourceModelId)
            ->where('operation', $operation)
            ->pluck('target_model_id')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Distinct client-type and payment-method targets for forced order filters (multiple rows each allowed).
     *
     * @return array{client_type_ids: array<int, string>, payment_method_ids: array<int, string>, apply: bool}
     */
    public static function getForcedOrdersFilterIds(string $sourceModelId): array
    {
        $clientTypeIds = self::targetIdsForOperation($sourceModelId, self::ORDERS_CLIENT_TYPE_FILTER_OPERATION);
        $paymentMethodIds = self::targetIdsForOperation($sourceModelId, self::ORDERS_PAYMENT_METHOD_FILTER_OPERATION);

        return [
            'client_type_ids' => $clientTypeIds,
            'payment_method_ids' => $paymentMethodIds,
            'apply' => $clientTypeIds !== [] && $paymentMethodIds !== [],
        ];
    }

    /**
     * Whether forced order filters apply: at least one client-type mapping and one payment-method mapping.
     */
    public static function hasForcedOrdersFilters(string $sourceModelId): bool
    {
        return self::getForcedOrdersFilterIds($sourceModelId)['apply'];
    }
}
