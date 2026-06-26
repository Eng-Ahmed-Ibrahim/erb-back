<?php

namespace Modules\ActivitiesSubscriptions\Domain\Repositories;

use Modules\ActivitiesSubscriptions\Domain\Entities\Academy;

interface AcademyRepositoryInterface
{
    public function findById(int $id): ?Academy;
    
    public function findAll(): array;
    
    public function findActive(): array;
    
    public function findByName(string $name): ?Academy;
    
    public function save(Academy $academy): Academy;
    
    public function delete(int $id): bool;
    
    public function exists(int $id): bool;
}
