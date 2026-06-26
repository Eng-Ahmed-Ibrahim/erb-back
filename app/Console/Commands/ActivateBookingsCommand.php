<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ActivateBookingsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bookings:activate-tomorrow';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Activate confirmed bookings that have check-in date tomorrow';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to activate confirmed bookings for tomorrow...');

        // Get tomorrow's date range (00:00:00 to 23:59:59)
        $tomorrow = Carbon::tomorrow();
        $tomorrowStart = $tomorrow->startOfDay();
        $tomorrowEnd = $tomorrow->endOfDay();

        $this->info("Looking for bookings with arrival date: {$tomorrow->format('Y-m-d')}");

        // Find confirmed bookings with arrival_datetime tomorrow
        $bookingsToActivate = Booking::where('status', Booking::STATUS_CONFIRMED)
            ->whereBetween('arrival_datetime', [$tomorrowStart, $tomorrowEnd])
            ->with(['visitor', 'apartment.building'])
            ->get();

        if ($bookingsToActivate->isEmpty()) {
            $this->info('No confirmed bookings found for tomorrow.');
            Log::info('ActivateBookingsCommand: No confirmed bookings found for tomorrow.', [
                'date' => $tomorrow->format('Y-m-d'),
                'timestamp' => now(),
            ]);
            return;
        }

        $this->info("Found {$bookingsToActivate->count()} confirmed booking(s) to activate.");

        $activatedCount = 0;
        $failedCount = 0;

        foreach ($bookingsToActivate as $booking) {
            try {
                // Activate the booking using the model's activate method
                $booking->activate();

                $this->info("✓ Activated booking #{$booking->id} for {$booking->visitor->name} in apartment {$booking->apartment->apartment_number}");

                // Log the activation
                Log::info('ActivateBookingsCommand: Booking activated successfully', [
                    'booking_id' => $booking->id,
                    'visitor_name' => $booking->visitor->name,
                    'apartment_number' => $booking->apartment->apartment_number,
                    'building_name' => $booking->apartment->building->name ?? 'N/A',
                    'arrival_datetime' => $booking->arrival_datetime->format('Y-m-d H:i:s'),
                    'activated_at' => now(),
                ]);

                $activatedCount++;

            } catch (\Exception $e) {
                $this->error("✗ Failed to activate booking #{$booking->id}: {$e->getMessage()}");

                // Log the error
                Log::error('ActivateBookingsCommand: Failed to activate booking', [
                    'booking_id' => $booking->id,
                    'visitor_name' => $booking->visitor->name ?? 'N/A',
                    'apartment_number' => $booking->apartment->apartment_number ?? 'N/A',
                    'error' => $e->getMessage(),
                    'timestamp' => now(),
                ]);

                $failedCount++;
            }
        }

        // Summary
        $this->info("=== Activation Summary ===");
        $this->info("Successfully activated: {$activatedCount} booking(s)");
        if ($failedCount > 0) {
            $this->error("Failed to activate: {$failedCount} booking(s)");
        }

        // Log the summary
        Log::info('ActivateBookingsCommand: Completed activation process', [
            'total_found' => $bookingsToActivate->count(),
            'successfully_activated' => $activatedCount,
            'failed_activations' => $failedCount,
            'date' => $tomorrow->format('Y-m-d'),
            'completed_at' => now(),
        ]);

        $this->info('Booking activation process completed.');
    }
}