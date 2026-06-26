<?php

namespace Modules\ActivitiesSubscriptions\Domain\Repositories;

use Modules\ActivitiesSubscriptions\Domain\Entities\Offer;

interface OfferRepositoryInterface
{
    public function findById(int $id): ?Offer;
    
    public function findAll(): array;
    
    public function findByAcademyId(int $academyId): array;
    
    public function findActive(): array;
    
    public function findActiveByAcademyId(int $academyId): array;
    
    public function save(Offer $offer): Offer;
    
    public function delete(int $id): bool;
    
    public function exists(int $id): bool;
}
