<?php

namespace Modules\MembershipCards\Application\Commands;

class RenewSubscriptionCommand
{
    public function __construct(
        public int $subscriptionId,
        public string $newEndDate,
        public float $paidAnnualFee,
        public float $paidIssuanceFee,
        public ?string $notes = null
    ) {}
}

