<?php

namespace Modules\ActivitiesSubscriptions\Infrastructure\Persistence;

use Modules\ActivitiesSubscriptions\Domain\Entities\Offer;
use Modules\ActivitiesSubscriptions\Domain\Repositories\OfferRepositoryInterface;
use Illuminate\Support\Facades\DB;

class EloquentOfferRepository implements OfferRepositoryInterface
{
    public function findById(int $id): ?Offer
    {
        $data = DB::table('offers')->where('id', $id)->first();
        
        if (!$data) {
            return null;
        }
        
        return $this->mapToEntity($data);
    }
    
    public function findAll(): array
    {
        $data = DB::table('offers')->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }
    
    public function findByAcademyId(int $academyId): array
    {
        $data = DB::table('offers')->where('academy_id', $academyId)->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }
    
    public function findActive(): array
    {
        $data = DB::table('offers')->where('status', 'active')->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }
    
    public function findActiveByAcademyId(int $academyId): array
    {
        $data = DB::table('offers')
            ->where('academy_id', $academyId)
            ->where('status', 'active')
            ->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }
    
    public function save(Offer $offer): Offer
    {
        $data = [
            'academy_id' => $offer->getAcademyId(),
            'name' => $offer->getName(),
            'num_classes' => $offer->getNumClasses(),
            'num_hours' => $offer->getNumHours(),
            'duration_days' => $offer->getDurationDays(),
            'available_days' => json_encode($offer->getAvailableDays()),
            'price_infantry' => $offer->getPriceInfantry()->getAmount(),
            'price_civilian' => $offer->getPriceCivilian()->getAmount(),
            'price_other' => $offer->getPriceOther()->getAmount(),
            'active' => $offer->isActive(),
            'updated_at' => now(),
        ];
        
        if ($offer->getId()) {
            DB::table('offers')->where('id', $offer->getId())->update($data);
            return $offer;
        } else {
            $data['created_at'] = now();
            $id = DB::table('offers')->insertGetId($data);
            $offer->setId($id);
            return $offer;
        }
    }
    
    public function delete(int $id): bool
    {
        return DB::table('offers')->where('id', $id)->delete() > 0;
    }
    
    public function exists(int $id): bool
    {
        return DB::table('offers')->where('id', $id)->exists();
    }
    
    private function mapToEntity($data): Offer
    {
        $offer = new Offer(
            $data->academy_id,
            $data->name,
            $data->num_classes,
            $data->num_hours,
            $data->duration_days,
            json_decode($data->available_days ?? '[]', true),
            new \Modules\ActivitiesSubscriptions\Domain\ValueObjects\Price($data->price_infantry ?? 0),
            new \Modules\ActivitiesSubscriptions\Domain\ValueObjects\Price($data->price_civilian ?? 0),
            new \Modules\ActivitiesSubscriptions\Domain\ValueObjects\Price($data->price_other ?? 0),
            $data->active ?? true
        );
        
        $offer->setId($data->id);
        
        return $offer;
    }
}