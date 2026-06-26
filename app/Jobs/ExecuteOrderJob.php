<?php

namespace App\Jobs;

use App\Repositories\Order\OrderRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExecuteOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $orderId;

    protected OrderRepository $orderRepository;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(string $orderId, OrderRepository $orderRepository)
    {
        $this->orderId = $orderId;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $this->orderRepository->execute($this->orderId);
        } catch (\Exception $e) {
            Log::error("Error executing order {$this->orderId}: ".$e->getMessage());
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::critical("Job failed for order {$this->orderId}: ".$exception->getMessage());
    }
}

// use App\Jobs\ExecuteOrderJob;
// ExecuteOrderJob::dispatch($orderId)->onQueue('order-execution-queue');
