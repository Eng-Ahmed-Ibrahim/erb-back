<?php

namespace Modules\ActivitiesSubscriptions\Infrastructure\Persistence;

use Illuminate\Support\Facades\DB;
use Modules\ActivitiesSubscriptions\Domain\Entities\Coach;
use Modules\ActivitiesSubscriptions\Domain\Repositories\CoachRepositoryInterface;

class EloquentCoachRepository implements CoachRepositoryInterface
{
    public function save(Coach $coach): Coach
    {
        $data = [
            'academy_id' => $coach->getAcademyId(),
            'name' => $coach->getName(),
            'phone' => $coach->getPhone(),
            'bio' => $coach->getBio(),
            'active' => $coach->isActive(),
            'updated_at' => now(),
        ];

        if ($coach->getId()) {
            DB::table('coaches')->where('id', $coach->getId())->update($data);
            return $coach;
        } else {
            $data['created_at'] = now();
            $id = DB::table('coaches')->insertGetId($data);
            $coach->setId($id);
            return $coach;
        }
    }

    public function findById(int $id): ?Coach
    {
        $data = DB::table('coaches')->where('id', $id)->first();
        
        if (!$data) {
            return null;
        }

        return $this->mapToEntity($data);
    }

    public function findByAcademyId(int $academyId): array
    {
        $data = DB::table('coaches')->where('academy_id', $academyId)->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }

    public function findAll(): array
    {
        $data = DB::table('coaches')->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }

    public function delete(int $id): bool
    {
        return DB::table('coaches')->where('id', $id)->delete() > 0;
    }

    private function mapToEntity($data): Coach
    {
        $coach = new Coach(
            $data->academy_id,
            $data->name,
            $data->phone,
            $data->bio,
            $data->active ?? true
        );
        
        $coach->setId($data->id);
        
        return $coach;
    }
}
