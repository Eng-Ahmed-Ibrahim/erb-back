<?php

namespace Modules\MembershipCards\Domain\Repositories;

use Modules\MembershipCards\Domain\Entities\MembershipCard;

interface MembershipCardRepositoryInterface
{
    public function findById(int $id): ?MembershipCard;
    
    public function findAll(): array;
    
    public function findBySubscriptionId(int $subscriptionId): ?MembershipCard;
    
    public function findByCardUid(string $cardUid): ?MembershipCard;
    
    public function findByStatus(string $status): array;
    
    public function findActive(): array;
    
    public function findExpiring(int $daysAhead = 30): array;
    
    public function findExpired(): array;
    
    public function findNotPrinted(): array;
    
    public function findNotEncoded(): array;
    
    public function findByOfficerId(int $officerId): array;
    
    public function save(MembershipCard $card): MembershipCard;
    
    public function delete(int $id): bool;
    
    public function exists(int $id): bool;
    
    public function existsByCardUid(string $cardUid): bool;
    
    public function findByToken(string $token): ?MembershipCard;
    
    public function findByTokenHex(string $tokenHex): ?MembershipCard;
}

