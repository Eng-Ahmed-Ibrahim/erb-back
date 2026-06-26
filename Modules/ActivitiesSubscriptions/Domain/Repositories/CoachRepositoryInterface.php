<?php

namespace Modules\ActivitiesSubscriptions\Domain\Repositories;

use Modules\ActivitiesSubscriptions\Domain\Entities\Coach;

interface CoachRepositoryInterface
{
    public function save(Coach $coach): Coach;
    public function findById(int $id): ?Coach;
    public function findByAcademyId(int $academyId): array;
    public function findAll(): array;
    public function delete(int $id): bool;
}
