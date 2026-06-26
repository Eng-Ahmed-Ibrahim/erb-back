<?php

namespace Modules\MembershipCards\Domain\Repositories;

use Modules\MembershipCards\Domain\Entities\Subscription;

interface SubscriptionRepositoryInterface
{
    public function findById(int $id): ?Subscription;
    
    public function findAll(): array;

    public function paginate(int $perPage = 15, ?string $status = null): array;
    
    public function findByOfficerId(int $officerId): array;
    
    public function findByBeneficiaryId(int $beneficiaryId): array;
    
    public function findByFeePlanId(int $feePlanId): array;
    
    public function findByStatus(string $status): array;
    
    public function findActiveByOfficerId(int $officerId): array;
    
    public function findActiveByBeneficiaryId(int $beneficiaryId): ?Subscription;
    
    public function findExpiring(int $daysAhead = 30): array;
    
    public function findExpired(): array;
    
    public function save(Subscription $subscription): Subscription;
    
    public function delete(int $id): bool;
    
    public function exists(int $id): bool;
    
    public function hasActiveSubscription(int $officerId, ?int $beneficiaryId = null): bool;
    
    public function findByDateRange(string $fromDate, string $toDate): array;
}

