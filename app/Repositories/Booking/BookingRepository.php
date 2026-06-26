<?php

namespace App\Repositories\Booking;

use App\Repositories\BaseRepository;

interface BookingRepository extends BaseRepository
{
    public function adminCreate($data);

    public function adminUpdate($model, $data);

    public function adminDelete($model);

    public function checkoutBooking($bookingId, $checkoutData = []);

    public function getActiveBookings();

    public function getBookingsByVisitor($visitorId);

    public function getBookingsByApartment($apartmentId);

    public function getBookingsByDateRange($startDate, $endDate);

    public function getDashboardSummary();

    public function filterBookings(array $filters);
}