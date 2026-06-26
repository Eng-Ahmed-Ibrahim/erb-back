<?php

namespace Modules\MembershipCards\Infrastructure\Persistence;

use Carbon\Carbon;
use Modules\MembershipCards\Domain\Entities\Subscription;
use Modules\MembershipCards\Domain\Repositories\SubscriptionRepositoryInterface;
use Modules\MembershipCards\Domain\ValueObjects\Duration;
use Modules\MembershipCards\Domain\ValueObjects\Price;
use Illuminate\Support\Facades\DB;

class EloquentSubscriptionRepository implements SubscriptionRepositoryInterface
{
    protected string $table = 'mc_subscriptions';

    public function findById(int $id): ?Subscription
    {
        $data = DB::table($this->table)->where('id', $id)->whereNull('deleted_at')->first();
        
        if (!$data) {
            return null;
        }
        
        return $this->mapToEntity($data);
    }
    
    public function findAll(): array
    {
        $data = DB::table($this->table)->whereNull('deleted_at')->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }

    public function paginate(int $perPage = 15, ?string $status = null): array
    {
        $query = DB::table($this->table)->whereNull('deleted_at');

        if ($status) {
            $query->where('status', $status);
        }

        $paginator = $query->orderByDesc('created_at')->paginate($perPage);

        return [
            'data' => collect($paginator->items())->map(fn($item) => $this->mapToEntity($item))->toArray(),
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
        ];
    }
    
    public function findByOfficerId(int $officerId): array
    {
        $data = DB::table($this->table)
            ->where('officer_id', $officerId)
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }
    
    public function findByBeneficiaryId(int $beneficiaryId): array
    {
        $data = DB::table($this->table)
            ->where('beneficiary_id', $beneficiaryId)
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }
    
    public function findByFeePlanId(int $feePlanId): array
    {
        $data = DB::table($this->table)
            ->where('fee_plan_id', $feePlanId)
            ->whereNull('deleted_at')
            ->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }
    
    public function findByStatus(string $status): array
    {
        $data = DB::table($this->table)
            ->where('status', $status)
            ->whereNull('deleted_at')
            ->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }
    
    public function findActiveByOfficerId(int $officerId): array
    {
        $data = DB::table($this->table)
            ->where('officer_id', $officerId)
            ->where('status', 'active')
            ->where('end_date', '>=', Carbon::now()->format('Y-m-d'))
            ->whereNull('deleted_at')
            ->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }
    
    public function findActiveByBeneficiaryId(int $beneficiaryId): ?Subscription
    {
        $data = DB::table($this->table)
            ->where('beneficiary_id', $beneficiaryId)
            ->where('status', 'active')
            ->where('end_date', '>=', Carbon::now()->format('Y-m-d'))
            ->whereNull('deleted_at')
            ->first();
        
        if (!$data) {
            return null;
        }
        
        return $this->mapToEntity($data);
    }
    
    public function findExpiring(int $daysAhead = 30): array
    {
        $data = DB::table($this->table)
            ->where('status', 'active')
            ->whereBetween('end_date', [
                Carbon::now()->format('Y-m-d'),
                Carbon::now()->addDays($daysAhead)->format('Y-m-d')
            ])
            ->whereNull('deleted_at')
            ->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }
    
    public function findExpired(): array
    {
        $data = DB::table($this->table)
            ->where('status', 'active')
            ->where('end_date', '<', Carbon::now()->format('Y-m-d'))
            ->whereNull('deleted_at')
            ->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }
    
    public function save(Subscription $subscription): Subscription
    {
        $data = [
            'officer_id' => $subscription->getOfficerId(),
            'beneficiary_id' => $subscription->getBeneficiaryId(),
            'fee_plan_id' => $subscription->getFeePlanId(),
            'start_date' => $subscription->getStartDate()->format('Y-m-d'),
            'end_date' => $subscription->getEndDate()->format('Y-m-d'),
            'status' => $subscription->getStatus(),
            'paid_establishment_fee' => $subscription->getPaidEstablishmentFee()->getAmount(),
            'paid_annual_fee' => $subscription->getPaidAnnualFee()->getAmount(),
            'paid_issuance_fee' => $subscription->getPaidIssuanceFee()->getAmount(),
            'created_by' => $subscription->getCreatedBy(),
            'notes' => $subscription->getNotes(),
            'is_honorary_membership' => $subscription->isHonoraryMembership(),
            'updated_at' => now(),
        ];
        
        if ($subscription->getId()) {
            DB::table($this->table)->where('id', $subscription->getId())->update($data);
            return $subscription;
        } else {
            $data['created_at'] = now();
            $id = DB::table($this->table)->insertGetId($data);
            $subscription->setId($id);
            return $subscription;
        }
    }
    
    public function delete(int $id): bool
    {
        return DB::table($this->table)->where('id', $id)->update(['deleted_at' => now()]) > 0;
    }
    
    public function exists(int $id): bool
    {
        return DB::table($this->table)->where('id', $id)->whereNull('deleted_at')->exists();
    }
    
    public function hasActiveSubscription(int $officerId, ?int $beneficiaryId = null): bool
    {
        $query = DB::table($this->table)
            ->where('officer_id', $officerId)
            ->where('status', 'active')
            ->where('end_date', '>=', Carbon::now()->format('Y-m-d'))
            ->whereNull('deleted_at');
        
        if ($beneficiaryId !== null) {
            $query->where('beneficiary_id', $beneficiaryId);
        } else {
            $query->whereNull('beneficiary_id');
        }
        
        return $query->exists();
    }
    
    private function mapToEntity($data): Subscription
    {
        $subscription = new Subscription(
            officerId: $data->officer_id,
            feePlanId: $data->fee_plan_id,
            duration: new Duration(
                Carbon::parse($data->start_date),
                Carbon::parse($data->end_date)
            ),
            createdBy: $data->created_by,
            paidEstablishmentFee: new Price((float) $data->paid_establishment_fee),
            paidAnnualFee: new Price((float) $data->paid_annual_fee),
            paidIssuanceFee: new Price((float) $data->paid_issuance_fee),
            beneficiaryId: $data->beneficiary_id,
            status: $data->status,
            notes: $data->notes,
            isHonoraryMembership: isset($data->is_honorary_membership) ? (bool) $data->is_honorary_membership : false
        );
        
        $subscription->setId($data->id);
        
        return $subscription;
    }
    
    public function findByDateRange(string $fromDate, string $toDate): array
    {
        $data = DB::table($this->table)
            ->whereBetween('created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59'])
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }
}

