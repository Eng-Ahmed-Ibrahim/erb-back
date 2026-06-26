<?php

namespace Modules\ActivitiesSubscriptions\Domain\Repositories;

use Modules\ActivitiesSubscriptions\Domain\Entities\Subscriber;

interface SubscriberRepositoryInterface
{
    public function findById(int $id): ?Subscriber;
    
    public function findAll(): array;
    
    public function findByFullName(string $fullName): array;
    
    public function findByType(string $type): array;
    
    public function findByNationalId(string $nationalId): ?Subscriber;
    
    public function findByMilitaryId(string $militaryId): ?Subscriber;
    
    public function findByUniqueIdentifier(string $identifier): ?Subscriber;
    
    public function search(string $query): array;
    
    public function save(Subscriber $subscriber): Subscriber;
    
    public function delete(int $id): bool;
    
    public function exists(int $id): bool;
}
