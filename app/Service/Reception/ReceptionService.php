<?php

namespace App\Service\Reception;

use App\Models\Apartment;
use App\Models\Booking;
use App\Models\Visitor;
use App\Repositories\Apartment\ApartmentRepository;
use App\Repositories\Booking\BookingRepository;
use App\Repositories\Building\BuildingRepository;
use App\Repositories\Visitor\VisitorRepository;
use Carbon\Carbon;

class ReceptionService
{
    public function __construct(
        private VisitorRepository $visitorRepository,
        private BuildingRepository $buildingRepository,
        private ApartmentRepository $apartmentRepository,
        private BookingRepository $bookingRepository
    ) {
        $this->visitorRepository = $visitorRepository;
        $this->buildingRepository = $buildingRepository;
        $this->apartmentRepository = $apartmentRepository;
        $this->bookingRepository = $bookingRepository;
    }

    /**
     * Register a new visitor and create a booking
     */
    public function registerVisitorAndBookApartment(array $visitorData, array $bookingData): array
    {
        // Create visitor
        $visitor = $this->visitorRepository->adminCreate($visitorData);

        // Add visitor_id to booking data
        $bookingData['visitor_id'] = $visitor->id;

        // Validate apartment availability
        $apartment = $this->apartmentRepository->find($bookingData['apartment_id']);
        if ($apartment->is_occupied) {
            throw new \Exception('Apartment is already occupied');
        }

        // Create booking
        $booking = $this->bookingRepository->adminCreate($bookingData);

        return [
            'visitor' => $visitor,
            'booking' => $booking,
            'apartment' => $apartment->fresh(),
        ];
    }

    /**
     * Quick visitor lookup and booking
     */
    public function quickBooking(string $idNumber, array $bookingData): array
    {
        // Find existing visitor
        $visitor = $this->visitorRepository->findByIdNumber($idNumber);

        if (!$visitor) {
            throw new \Exception('Visitor not found. Please register the visitor first.');
        }

        // Check if visitor already has an active booking
        $activeBooking = $visitor->getCurrentBooking();
        if ($activeBooking) {
            throw new \Exception('Visitor already has an active booking in apartment ' . $activeBooking->apartment->number);
        }

        // Add visitor_id to booking data
        $bookingData['visitor_id'] = $visitor->id;

        // Validate apartment availability
        $apartment = $this->apartmentRepository->find($bookingData['apartment_id']);
        if ($apartment->is_occupied) {
            throw new \Exception('Apartment is already occupied');
        }

        // Create booking
        $booking = $this->bookingRepository->adminCreate($bookingData);

        return [
            'visitor' => $visitor,
            'booking' => $booking,
            'apartment' => $apartment->fresh(),
        ];
    }

    /**
     * Get available apartments with color coding
     */
    public function getAvailableApartmentsWithColorCoding(string $visitorType = null): array
    {
        $buildings = $this->buildingRepository->withAvailableApartments();
        $colorCode = null;

        if ($visitorType) {
            $colorCode = Visitor::VISITOR_TYPE_COLORS[$visitorType] ?? null;
        }

        $result = [];
        foreach ($buildings as $building) {
            $buildingData = [
                'id' => $building->id,
                'name' => $building->name,
                'color' => $building->color,
                'recommended' => $colorCode && $this->isColorMatch($building->color, $colorCode),
                'available_apartments' => [],
            ];

            foreach ($building->apartments as $apartment) {
                if (!$apartment->is_occupied) {
                    $buildingData['available_apartments'][] = [
                        'id' => $apartment->id,
                        'number' => $apartment->number,
                        'room_type' => $apartment->room_type,
                        'building_id' => $apartment->building_id,
                    ];
                }
            }

            $result[] = $buildingData;
        }

        return $result;
    }

    /**
     * Calculate meal pricing
     */
    public function calculateBookingAmount(array $bookingData): float
    {
        $baseAmount = 0;
        $mealPrices = [
            'breakfast' => 25.00,
            'lunch' => 45.00,
            'dinner' => 50.00,
        ];

        // Calculate meal costs
        if (isset($bookingData['meals']) && is_array($bookingData['meals'])) {
            foreach ($bookingData['meals'] as $meal) {
                $baseAmount += $mealPrices[$meal] ?? 0;
            }
        }

        // Multiply by duration
        $durationDays = $bookingData['duration_days'] ?? 1;
        $mealCost = $baseAmount * $durationDays;

        // Add product cost if any
        $productCost = ($bookingData['product_count'] ?? 0) * 10.00; // 10 SAR per product

        return $mealCost + $productCost;
    }

    /**
     * Check out a visitor
     */
    public function checkoutVisitor(string $bookingId): array
    {
        $booking = $this->bookingRepository->find($bookingId);

        if (!$booking->isActive()) {
            throw new \Exception('Booking is not active');
        }

        // Calculate final amount if not set
        if ($booking->total_amount == 0) {
            $bookingData = [
                'meals' => $booking->meals,
                'duration_days' => $booking->duration_days,
                'product_count' => $booking->product_count,
            ];
            $finalAmount = $this->calculateBookingAmount($bookingData);
            $booking->total_amount = $finalAmount;
            $booking->save();
        }

        // Complete the booking
        $booking->complete();

        return [
            'booking' => $booking->fresh(),
            'final_amount' => $booking->total_amount,
            'checkout_datetime' => $booking->checkout_datetime,
        ];
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats(): array
    {
        $summary = $this->bookingRepository->getDashboardSummary();
        $occupancyStats = $this->buildingRepository->getOccupancyStats();

        // Additional calculations
        $visitorTypeBreakdown = $this->getVisitorTypeBreakdownWithColors();
        $upcomingCheckouts = $this->getUpcomingCheckouts();

        return [
            'summary' => $summary,
            'buildings' => $occupancyStats,
            'visitor_breakdown' => $visitorTypeBreakdown,
            'upcoming_checkouts' => $upcomingCheckouts,
        ];
    }

    /**
     * Filter bookings with advanced options
     */
    public function advancedBookingFilter(array $filters): array
    {
        // Process date filters
        if (isset($filters['date_range'])) {
            $dateRange = $filters['date_range'];
            if ($dateRange === 'today') {
                $filters['arrival_date_from'] = Carbon::today()->format('Y-m-d');
                $filters['arrival_date_to'] = Carbon::today()->format('Y-m-d');
            } elseif ($dateRange === 'week') {
                $filters['arrival_date_from'] = Carbon::now()->startOfWeek()->format('Y-m-d');
                $filters['arrival_date_to'] = Carbon::now()->endOfWeek()->format('Y-m-d');
            } elseif ($dateRange === 'month') {
                $filters['arrival_date_from'] = Carbon::now()->startOfMonth()->format('Y-m-d');
                $filters['arrival_date_to'] = Carbon::now()->endOfMonth()->format('Y-m-d');
            }
            unset($filters['date_range']);
        }

        return $this->bookingRepository->filterBookings($filters);
    }

    /**
     * Helper method to check color matching
     */
    private function isColorMatch(string $buildingColor, string $visitorColor): bool
    {
        // Simple color matching logic - can be enhanced
        $colorMap = [
            'navy' => ['#000080', '#003366', '#1e3a8a'],
            'brown' => ['#8B4513', '#A0522D', '#964B00'],
            'orange' => ['#FF5733', '#FFA500', '#FF6347'],
        ];

        if (isset($colorMap[$visitorColor])) {
            return in_array($buildingColor, $colorMap[$visitorColor]);
        }

        return false;
    }

    /**
     * Get visitor type breakdown with colors
     */
    private function getVisitorTypeBreakdownWithColors(): array
    {
        $breakdown = $this->bookingRepository->getDashboardSummary()['visitor_types_breakdown'] ?? [];

        $result = [];
        foreach ($breakdown as $type => $count) {
            $result[] = [
                'type' => $type,
                'count' => $count,
                'color' => Visitor::VISITOR_TYPE_COLORS[$type] ?? 'gray',
            ];
        }

        return $result;
    }

    /**
     * Get upcoming checkouts (next 24 hours)
     */
    private function getUpcomingCheckouts(): array
    {
        $tomorrow = Carbon::tomorrow()->format('Y-m-d');

        return $this->bookingRepository->filterBookings([
            'status' => Booking::STATUS_ACTIVE,
            'arrival_date_to' => $tomorrow,
        ]);
    }
}