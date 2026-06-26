<?php

namespace Modules\ActivitiesSubscriptions\Domain\Repositories;

use Modules\ActivitiesSubscriptions\Domain\Entities\Attendance;
use Carbon\Carbon;

interface AttendanceRepositoryInterface
{
    public function findById(int $id): ?Attendance;
    
    public function findAll(): array;
    
    public function findBySubscriptionId(int $subscriptionId): array;
    
    public function findByDateRange(Carbon $startDate, Carbon $endDate): array;
    
    public function findBySubscriptionIdAndDateRange(int $subscriptionId, Carbon $startDate, Carbon $endDate): array;
    
    public function findByAcademyId(int $academyId): array;
    
    public function findByAcademyIdAndDateRange(int $academyId, Carbon $startDate, Carbon $endDate): array;
    
    public function getAttendanceCountBySubscriptionId(int $subscriptionId): int;
    
    public function getAttendanceCountBySubscriptionIdAndDateRange(int $subscriptionId, Carbon $startDate, Carbon $endDate): int;

    public function getAttendanceHoursBySubscriptionId(int $subscriptionId): float;

    public function getAttendanceWithDetails(?Carbon $startDate = null, ?Carbon $endDate = null, ?int $subscriptionId = null, ?int $academyId = null): array;
    
    public function getAttendanceStats(?Carbon $startDate = null, ?Carbon $endDate = null, ?int $subscriptionId = null): array;
    
    public function save(Attendance $attendance): Attendance;
    
    public function delete(int $id): bool;
    
    public function exists(int $id): bool;
}
