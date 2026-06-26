<?php

namespace Modules\ActivitiesSubscriptions\Infrastructure\Persistence;

use Modules\ActivitiesSubscriptions\Domain\Entities\Academy;
use Modules\ActivitiesSubscriptions\Domain\ValueObjects\Percentage;
use Modules\ActivitiesSubscriptions\Domain\Repositories\AcademyRepositoryInterface;
use Illuminate\Support\Facades\DB;

class EloquentAcademyRepository implements AcademyRepositoryInterface
{
    public function findById(int $id): ?Academy
    {
        $data = DB::table('academies')->where('id', $id)->first();
        
        if (!$data) {
            return null;
        }
        
        return $this->mapToEntity($data);
    }
    
    public function findAll(): array
    {
        $data = DB::table('academies')->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }
    
    public function findActive(): array
    {
        $data = DB::table('academies')->where('status', 'active')->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }
    
    public function findByName(string $name): ?Academy
    {
        $data = DB::table('academies')->where('name', $name)->first();
        
        if (!$data) {
            return null;
        }
        
        return $this->mapToEntity($data);
    }
    
    public function save(Academy $academy): Academy
    {
        $data = [
            'name' => $academy->getName(),
            'contracted' => $academy->isContracted(),
            'revenue_share_infantry' => $academy->getRevenueShareInfantry()->getValue(),
            'revenue_share_academy' => $academy->getRevenueShareAcademy()->getValue(),
            'working_days' => json_encode($academy->getWorkingDays()),
            'status' => $academy->getStatus(),
            'updated_at' => now(),
        ];
        
        if ($academy->getId()) {
            DB::table('academies')->where('id', $academy->getId())->update($data);
            return $academy;
        } else {
            $data['created_at'] = now();
            $id = DB::table('academies')->insertGetId($data);
            $academy->setId($id);
            return $academy;
        }
    }
    
    public function delete(int $id): bool
    {
        return DB::table('academies')->where('id', $id)->delete() > 0;
    }
    
    public function exists(int $id): bool
    {
        return DB::table('academies')->where('id', $id)->exists();
    }
    
    private function mapToEntity($data): Academy
    {
        $academy = new Academy(
            name: $data->name,
            contracted: $data->contracted,
            revenueShareInfantry: new Percentage($data->revenue_share_infantry),
            revenueShareAcademy: new Percentage($data->revenue_share_academy),
            workingDays: json_decode($data->working_days, true),
            status: $data->status
        );
        
        $academy->setId($data->id);
        
        return $academy;
    }
}
