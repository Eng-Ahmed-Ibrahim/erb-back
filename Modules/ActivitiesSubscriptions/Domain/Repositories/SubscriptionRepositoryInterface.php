<?php

namespace Modules\ActivitiesSubscriptions\Domain\Repositories;

use Modules\ActivitiesSubscriptions\Domain\Entities\Subscription;

interface SubscriptionRepositoryInterface
{
    public function findById(int $id): ?Subscription;
    
    public function findAll(): array;
    
    public function findBySubscriberId(int $subscriberId): array;
    
    public function findByOfferId(int $offerId): array;
    
    public function findByAcademyId(int $academyId): array;
    
    public function findActive(): array;
    
    public function findActiveBySubscriberId(int $subscriberId): array;
    
    public function findByQrCode(string $qrCode): ?Subscription;
    
    public function save(Subscription $subscription): Subscription;
    
    public function delete(int $id): bool;
    
    public function exists(int $id): bool;
    
    public function findWithFilters(array $filters = [], int $perPage = 15, int $page = 1): array;
}
