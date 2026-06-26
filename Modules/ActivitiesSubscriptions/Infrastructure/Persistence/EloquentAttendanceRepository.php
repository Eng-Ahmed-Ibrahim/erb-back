<?php

namespace Modules\ActivitiesSubscriptions\Infrastructure\Persistence;

use Modules\ActivitiesSubscriptions\Domain\Entities\Attendance;
use Modules\ActivitiesSubscriptions\Domain\Repositories\AttendanceRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EloquentAttendanceRepository implements AttendanceRepositoryInterface
{
    public function findById(int $id): ?Attendance
    {
        $data = DB::table('attendance')->where('id', $id)->first();
        
        if (!$data) {
            return null;
        }
        
        return $this->mapToEntity($data);
    }
    
    public function findAll(): array
    {
        $data = DB::table('attendance')->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }
    
    public function findBySubscriptionId(int $subscriptionId): array
    {
        $data = DB::table('attendance')->where('subscription_id', $subscriptionId)->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }
    
    public function findByDateRange(Carbon $startDate, Carbon $endDate): array
    {
        $data = DB::table('attendance')
            ->whereBetween('check_in_date', [$startDate, $endDate])
            ->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }
    
    public function findBySubscriptionIdAndDateRange(int $subscriptionId, Carbon $startDate, Carbon $endDate): array
    {
        $data = DB::table('attendance')
            ->where('subscription_id', $subscriptionId)
            ->whereBetween('check_in_date', [$startDate, $endDate])
            ->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }
    
    public function findByAcademyId(int $academyId): array
    {
        $data = DB::table('attendance')
            ->join('subscriptions', 'attendance.subscription_id', '=', 'subscriptions.id')
            ->join('offers', 'subscriptions.offer_id', '=', 'offers.id')
            ->where('offers.academy_id', $academyId)
            ->select('attendance.*')
            ->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }
    
    public function findByAcademyIdAndDateRange(int $academyId, Carbon $startDate, Carbon $endDate): array
    {
        $data = DB::table('attendance')
            ->join('subscriptions', 'attendance.subscription_id', '=', 'subscriptions.id')
            ->join('offers', 'subscriptions.offer_id', '=', 'offers.id')
            ->where('offers.academy_id', $academyId)
            ->whereBetween('attendance.check_in_date', [$startDate, $endDate])
            ->select('attendance.*')
            ->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }
    
    public function getAttendanceCountBySubscriptionId(int $subscriptionId): int
    {
        return DB::table('attendance')->where('subscription_id', $subscriptionId)->count();
    }
    
    public function getAttendanceCountBySubscriptionIdAndDateRange(int $subscriptionId, Carbon $startDate, Carbon $endDate): int
    {
        return DB::table('attendance')
            ->where('subscription_id', $subscriptionId)
            ->whereBetween('check_in_date', [$startDate, $endDate])
            ->count();
    }
    
    public function save(Attendance $attendance): Attendance
    {
        $data = [
            'subscription_id' => $attendance->getSubscriptionId(),
            'check_in_date' => $attendance->getCheckInDate(),
            'day_of_week' => $attendance->getDayOfWeek(),
            'deducted' => $attendance->getDeducted(),
            'updated_at' => now(),
        ];
        
        if ($attendance->getId()) {
            DB::table('attendance')->where('id', $attendance->getId())->update($data);
            return $attendance;
        } else {
            $data['created_at'] = now();
            $id = DB::table('attendance')->insertGetId($data);
            $attendance->setId($id);
            return $attendance;
        }
    }
    
    public function delete(int $id): bool
    {
        return DB::table('attendance')->where('id', $id)->delete() > 0;
    }
    
    public function exists(int $id): bool
    {
        return DB::table('attendance')->where('id', $id)->exists();
    }
    
    public function getAttendanceWithDetails(?Carbon $startDate = null, ?Carbon $endDate = null, ?int $subscriptionId = null, ?int $academyId = null): array
    {
        $query = DB::table('attendance')
            ->join('subscriptions', 'attendance.subscription_id', '=', 'subscriptions.id')
            ->join('subscribers', 'subscriptions.subscriber_id', '=', 'subscribers.id')
            ->join('offers', 'subscriptions.offer_id', '=', 'offers.id')
            ->join('academies', 'offers.academy_id', '=', 'academies.id')
            ->select(
                'attendance.id',
                'attendance.subscription_id',
                'attendance.check_in_date',
                'attendance.day_of_week',
                'attendance.deducted',
                'attendance.created_at as attendance_created_at',
                'subscriptions.created_at',
                'subscriptions.academy_id',
                'subscriptions.offer_id',
                'subscriptions.subscriber_id',
                'subscribers.full_name as subscriber_name',
                'subscribers.phone as subscriber_phone',
                'offers.name as offer_name',
                'offers.num_classes as classes_count',
                'offers.num_hours',
                'academies.name as academy_name'
            );

        // Apply date filters
        if ($startDate) {
            $query->where('attendance.check_in_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('attendance.check_in_date', '<=', $endDate);
        }
        if ($subscriptionId) {
            $query->where('attendance.subscription_id', $subscriptionId);
        }
        if ($academyId) {
            $query->where('offers.academy_id', $academyId);
        }

        $results = $query->get();

        // Group by subscription to calculate attendance counts
        $groupedData = [];
        foreach ($results as $row) {
            $subscriptionId = $row->subscription_id;
            
            if (!isset($groupedData[$subscriptionId])) {
                $groupedData[$subscriptionId] = [
                    'subscription' => [
                        'id' => $row->subscription_id,
                        'created_at' => $row->created_at,
                        'academy_id' => $row->academy_id,
                        'offer_id' => $row->offer_id,
                        'subscriber_id' => $row->subscriber_id,
                        'subscriber' => [
                            'id' => $row->subscriber_id,
                            'full_name' => $row->subscriber_name,
                            'phone' => $row->subscriber_phone,
                        ],
                        'offer' => [
                            'id' => $row->offer_id,
                            'name' => $row->offer_name,
                            'classes_count' => $row->classes_count,
                            'num_hours' => $row->num_hours,
                            'academy_id' => $row->academy_id,
                            'academy' => [
                                'id' => $row->academy_id,
                                'name' => $row->academy_name,
                            ],
                        ],
                    ],
                    'attendance_count' => 0,
                    'last_attendance_date' => null,
                    'attendance_records' => [],
                ];
            }
            
            $groupedData[$subscriptionId]['attendance_count']++;
            $groupedData[$subscriptionId]['attendance_records'][] = [
                'id' => $row->id,
                'check_in_date' => $row->check_in_date,
                'day_of_week' => $row->day_of_week,
                'deducted' => $row->deducted,
                'created_at' => $row->attendance_created_at,
            ];
            
            // Update last attendance date
            if (!$groupedData[$subscriptionId]['last_attendance_date'] || 
                $row->check_in_date > $groupedData[$subscriptionId]['last_attendance_date']) {
                $groupedData[$subscriptionId]['last_attendance_date'] = $row->check_in_date;
            }
        }

        return array_values($groupedData);
    }
    
    public function getAttendanceStats(?Carbon $startDate = null, ?Carbon $endDate = null, ?int $subscriptionId = null): array
    {
        $query = DB::table('attendance');
        
        if ($startDate) {
            $query->where('check_in_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('check_in_date', '<=', $endDate);
        }
        if ($subscriptionId) {
            $query->where('subscription_id', $subscriptionId);
        }

        $totalAttendance = $query->count();

        // Get total subscriptions
        $subscriptionQuery = DB::table('subscriptions');
        if ($startDate) {
            $subscriptionQuery->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $subscriptionQuery->where('created_at', '<=', $endDate);
        }
        $totalSubscriptions = $subscriptionQuery->count();

        // Calculate average attendance percentage
        $avgAttendanceQuery = DB::table('attendance')
            ->join('subscriptions', 'attendance.subscription_id', '=', 'subscriptions.id')
            ->join('offers', 'subscriptions.offer_id', '=', 'offers.id');
            
        if ($startDate) {
            $avgAttendanceQuery->where('attendance.check_in_date', '>=', $startDate);
        }
        if ($endDate) {
            $avgAttendanceQuery->where('attendance.check_in_date', '<=', $endDate);
        }
        if ($subscriptionId) {
            $avgAttendanceQuery->where('attendance.subscription_id', $subscriptionId);
        }

        $avgAttendanceQuery->selectRaw('
            AVG(
                CASE 
                    WHEN offers.num_classes > 0 THEN (attendance_count.count / offers.num_classes) * 100
                    ELSE 0 
                END
            ) as average_attendance
        ')
        ->joinSub(
            DB::table('attendance')
                ->select('subscription_id')
                ->selectRaw('COUNT(*) as count')
                ->groupBy('subscription_id')
                ->when($startDate, function($q) use ($startDate) { return $q->where('check_in_date', '>=', $startDate); })
                ->when($endDate, function($q) use ($endDate) { return $q->where('check_in_date', '<=', $endDate); })
                ->when($subscriptionId, function($q) use ($subscriptionId) { return $q->where('subscription_id', $subscriptionId); }),
            'attendance_count',
            'attendance.subscription_id',
            '=',
            'attendance_count.subscription_id'
        );

        $avgResult = $avgAttendanceQuery->first();
        $averageAttendance = $avgResult ? round($avgResult->average_attendance, 2) : 0;

        return [
            'totalAttendance' => $totalAttendance,
            'totalSubscriptions' => $totalSubscriptions,
            'averageAttendance' => $averageAttendance,
        ];
    }

    public function getAttendanceHoursBySubscriptionId(int $subscriptionId): float
    {
        $result = DB::table('attendance')
            ->join('subscriptions', 'attendance.subscription_id', '=', 'subscriptions.id')
            ->join('offers', 'subscriptions.offer_id', '=', 'offers.id')
            ->where('attendance.subscription_id', $subscriptionId)
            ->selectRaw('SUM(offers.duration_hours) as total_hours')
            ->first();

        return $result ? (float) $result->total_hours : 0.0;
    }

    private function mapToEntity($data): Attendance
    {
        $attendance = new Attendance(
            $data->subscription_id,
            Carbon::parse($data->check_in_date),
            $data->day_of_week,
            $data->deducted
        );
        
        $attendance->setId($data->id);
        
        return $attendance;
    }
}