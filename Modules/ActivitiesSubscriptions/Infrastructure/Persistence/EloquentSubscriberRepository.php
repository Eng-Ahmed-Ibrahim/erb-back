<?php

namespace Modules\ActivitiesSubscriptions\Infrastructure\Persistence;

use Modules\ActivitiesSubscriptions\Domain\Entities\Subscriber;
use Modules\ActivitiesSubscriptions\Domain\Repositories\SubscriberRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EloquentSubscriberRepository implements SubscriberRepositoryInterface
{
    public function findById(int $id): ?Subscriber
    {
        $data = DB::table('subscribers')->where('id', $id)->first();
        
        if (!$data) {
            return null;
        }
        
        return $this->mapToEntity($data);
    }
    
    public function findAll(): array
    {
        $data = DB::table('subscribers')->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }
    
    public function findByFullName(string $fullName): array
    {
        $data = DB::table('subscribers')->where('full_name', 'like', "%{$fullName}%")->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }
    
    public function findByType(string $type): array
    {
        $data = DB::table('subscribers')->where('type', $type)->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }
    
    public function findByNationalId(string $nationalId): ?Subscriber
    {
        $data = DB::table('subscribers')->where('national_id', $nationalId)->first();
        
        if (!$data) {
            return null;
        }
        
        return $this->mapToEntity($data);
    }
    
    public function findByMilitaryId(string $militaryId): ?Subscriber
    {
        $data = DB::table('subscribers')->where('military_id', $militaryId)->first();
        
        if (!$data) {
            return null;
        }
        
        return $this->mapToEntity($data);
    }
    
    public function findByUniqueIdentifier(string $identifier): ?Subscriber
    {
        $data = DB::table('subscribers')
            ->orWhere('id', $identifier)
            ->orWhere('national_id', $identifier)
            ->orWhere('military_id', $identifier)   
            ->orWhere('phone', $identifier)
            ->first();
        
        if (!$data) {
            return null;
        }
        
        return $this->mapToEntity($data);
    }
    
    public function search(string $query): array
    {
        $data = DB::table('subscribers')
            ->where('full_name', 'like', "%{$query}%")
            ->orWhere('phone', 'like', "%{$query}%")
            ->orWhere('national_id', 'like', "%{$query}%")
            ->orWhere('military_id', 'like', "%{$query}%")
            ->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }
    
    public function save(Subscriber $subscriber): Subscriber
    {
        $data = [
            'full_name' => $subscriber->getFullName(),
            'type' => $subscriber->getType(),
            'national_id' => $subscriber->getNationalId(),
            'military_id' => $subscriber->getMilitaryId(),
            'phone' => $subscriber->getPhone(),
            'updated_at' => now(),
        ];
        
        if ($subscriber->getId()) {
            DB::table('subscribers')->where('id', $subscriber->getId())->update($data);
            return $subscriber;
        } else {
            $data['created_at'] = now();
            $id = DB::table('subscribers')->insertGetId($data);
            $subscriber->setId($id);
            return $subscriber;
        }
    }
    
    public function delete(int $id): bool
    {
        return DB::table('subscribers')->where('id', $id)->delete() > 0;
    }
    
    public function exists(int $id): bool
    {
        return DB::table('subscribers')->where('id', $id)->exists();
    }
    
    private function mapToEntity($data): Subscriber
    {
        $subscriber = new Subscriber(
            $data->full_name,
            $data->type,
            $data->national_id,
            $data->military_id,
            $data->phone
        );
        
        $subscriber->setId($data->id);
        
        return $subscriber;
    }
}
