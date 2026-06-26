<?php

namespace Modules\ActivitiesSubscriptions\Infrastructure\Persistence;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\ActivitiesSubscriptions\Domain\Entities\Subscription;
use Modules\ActivitiesSubscriptions\Domain\Repositories\SubscriptionRepositoryInterface;

class EloquentSubscriptionRepository implements SubscriptionRepositoryInterface
{
    public function findById(int $id): ?Subscription
    {
        $data = DB::table('subscriptions')->where('id', $id)->first();
        
        if (!$data) {
            return null;
        }
        
        return $this->mapToEntity($data);
    }
    
    public function findAll(): array
    {
        $data = DB::table('subscriptions')->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }
    
    public function findBySubscriberId(int $subscriberId): array
    {
        $data = DB::table('subscriptions')->where('subscriber_id', $subscriberId)->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }
    
    public function findByOfferId(int $offerId): array
    {
        $data = DB::table('subscriptions')->where('offer_id', $offerId)->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }
    
    public function findByAcademyId(int $academyId): array
    {
        $data = DB::table('subscriptions')
            ->join('offers', 'subscriptions.offer_id', '=', 'offers.id')
            ->where('offers.academy_id', $academyId)
            ->select('subscriptions.*')
            ->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }
    
    public function findActive(): array
    {
        $data = DB::table('subscriptions')->where('status', 'active')->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }
    
    public function findActiveBySubscriberId(int $subscriberId): array
    {
        $data = DB::table('subscriptions')
            ->where('subscriber_id', $subscriberId)
            ->where('status', 'active')
            ->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }
    
    public function findByQrCode(string $qrCode): ?Subscription
    {
        $data = DB::table('subscriptions')->where('qr_code', $qrCode)->first();
        
        if (!$data) {
            return null;
        }
        
        return $this->mapToEntity($data);
    }
    
    public function save(Subscription $subscription): Subscription
    {
        $data = [
            'subscriber_id' => $subscription->getSubscriberId(),
            'offer_id' => $subscription->getOfferId(),
            'academy_id' => $subscription->getAcademyId(),
            'created_by' => $subscription->getCreatedBy(),
            'start_date' => $subscription->getDuration()->getStartDate()->toDateString(),
            'end_date' => $subscription->getDuration()->getEndDate()->toDateString(),
            'chosen_days' => json_encode($subscription->getChosenDays()),
            'remaining_classes' => $subscription->getRemainingClasses(),
            'remaining_hours' => $subscription->getRemainingHours(),
            'qr_code' => $subscription->getQrCode()->getValue(),
            'status' => $subscription->getStatus(),
            'updated_at' => now(),
        ];
        
        if ($subscription->getId()) {
            DB::table('subscriptions')->where('id', $subscription->getId())->update($data);
            return $subscription;
        } else {
            $data['created_at'] = now();
            $id = DB::table('subscriptions')->insertGetId($data);
            $subscription->setId($id);
            return $subscription;
        }
    }
    
    public function delete(int $id): bool
    {
        return DB::table('subscriptions')->where('id', $id)->delete() > 0;
    }
    
    public function exists(int $id): bool
    {
        return DB::table('subscriptions')->where('id', $id)->exists();
    }
    
    public function findWithFilters(array $filters = [], int $perPage = 15, int $page = 1): array
    {
        $query = DB::table('subscriptions')
            ->join('subscribers', 'subscriptions.subscriber_id', '=', 'subscribers.id')
            ->join('offers', 'subscriptions.offer_id', '=', 'offers.id')
            ->join('academies', 'subscriptions.academy_id', '=', 'academies.id')
            ->leftJoin('users', 'subscriptions.created_by', '=', 'users.id')
            ->select(
                'subscriptions.*',
                'subscribers.full_name as subscriber_name',
                'subscribers.phone as subscriber_phone',
                'subscribers.type as subscriber_type',
                'subscribers.national_id',
                'subscribers.military_id',
                'offers.name as offer_name',
                'offers.num_classes',
                'offers.num_hours',
                'academies.name as academy_name',
                'users.name as created_by_name'
            );

        // Apply filters
        if (isset($filters['date_from']) && $filters['date_from']) {
            $query->where('subscriptions.created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to']) && $filters['date_to']) {
            $query->where('subscriptions.created_at', '<=', $filters['date_to'] . ' 23:59:59');
        }

        if (isset($filters['created_by']) && $filters['created_by']) {
            $query->where('subscriptions.created_by', $filters['created_by']);
        }

        if (isset($filters['academy_id']) && $filters['academy_id']) {
            $query->where('subscriptions.academy_id', $filters['academy_id']);
        }

        // Apply pagination
        $subscriptions = $query->paginate($perPage, ['*'], 'page', $page);

        // Transform the paginated data
        $transformedSubscriptions = collect($subscriptions->items())->map(function ($subscription) {
            return [
                'id' => $subscription->id,
                'subscriber_id' => $subscription->subscriber_id,
                'subscriber_name' => $subscription->subscriber_name,
                'subscriber_phone' => $subscription->subscriber_phone,
                'subscriber_type' => $subscription->subscriber_type,
                'subscriber_national_id' => $subscription->national_id,
                'subscriber_military_id' => $subscription->military_id,
                'offer_id' => $subscription->offer_id,
                'offer_name' => $subscription->offer_name,
                'offer_classes_count' => $subscription->num_classes,
                'offer_hours_count' => $subscription->num_hours,
                'academy_id' => $subscription->academy_id,
                'academy_name' => $subscription->academy_name,
                'created_by' => $subscription->created_by,
                'created_by_name' => $subscription->created_by_name,
                'start_date' => $subscription->start_date,
                'end_date' => $subscription->end_date,
                'chosen_days' => json_decode($subscription->chosen_days, true),
                'remaining_classes' => $subscription->remaining_classes,
                'remaining_hours' => $subscription->remaining_hours,
                'qr_code' => $subscription->qr_code,
                'status' => $subscription->status,
                'created_at' => $subscription->created_at,
                'updated_at' => $subscription->updated_at,
            ];
        });

        return [
            'data' => $transformedSubscriptions,
            'pagination' => [
                'current_page' => $subscriptions->currentPage(),
                'last_page' => $subscriptions->lastPage(),
                'per_page' => $subscriptions->perPage(),
                'total' => $subscriptions->total(),
                'from' => $subscriptions->firstItem(),
                'to' => $subscriptions->lastItem(),
            ]
        ];
    }
    
    private function mapToEntity($data): Subscription
    {
        $offerData = DB::table('offers')->where('id', $data->offer_id)->first();
        $offerDurationDays = $offerData?->duration_days;
        $offerAcademyId = $offerData?->academy_id;
        $offerNumClasses = $offerData?->num_classes;
        $offerNumHours = $offerData?->num_hours;

        $startDate = Carbon::parse($data->start_date ?? now());
        $endDate = $data->end_date
            ? Carbon::parse($data->end_date)
            : ($offerDurationDays !== null
                ? $startDate->copy()->addDays($offerDurationDays)
                : $startDate->copy());
        $duration = new \Modules\ActivitiesSubscriptions\Domain\ValueObjects\Duration($startDate, $endDate);

        $academyId = $data->academy_id ?? $offerAcademyId;

        if ($academyId === null) {
            throw new \RuntimeException('Unable to determine academy ID for subscription: ' . $data->id);
        }
        
        $subscription = new Subscription(
            $data->subscriber_id,
            $data->offer_id,
            (int) $academyId,
            $data->created_by,
            $duration,
            json_decode($data->chosen_days ?? '[]', true),
            $data->remaining_classes ?? ($offerNumClasses ?? 0),
            $data->remaining_hours ?? ($offerNumHours ?? 0),
            new \Modules\ActivitiesSubscriptions\Domain\ValueObjects\QRCode($data->qr_code),
            $data->status ?? 'active'
        );
        
        $subscription->setId($data->id);
        
        return $subscription;
    }
}