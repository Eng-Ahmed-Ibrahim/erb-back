<?php

namespace Modules\MembershipCards\Infrastructure\Persistence;

use Carbon\Carbon;
use Modules\MembershipCards\Domain\Entities\MembershipCard;
use Modules\MembershipCards\Domain\Repositories\MembershipCardRepositoryInterface;
use Modules\MembershipCards\Domain\ValueObjects\CardUID;
use Illuminate\Support\Facades\DB;

class EloquentMembershipCardRepository implements MembershipCardRepositoryInterface
{
    protected string $table = 'mc_membership_cards';

    public function findById(int $id): ?MembershipCard
    {
        $data = DB::table($this->table)->where('id', $id)->whereNull('deleted_at')->first();
        
        if (!$data) {
            return null;
        }
        
        return $this->mapToEntity($data);
    }
    
    public function findAll(): array
    {
        $data = DB::table($this->table)->whereNull('deleted_at')->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }
    
    public function findBySubscriptionId(int $subscriptionId): ?MembershipCard
    {
        $data = DB::table($this->table)
            ->where('subscription_id', $subscriptionId)
            ->whereNull('deleted_at')
            ->first();
        
        if (!$data) {
            return null;
        }
        
        return $this->mapToEntity($data);
    }
    
    public function findByCardUid(string $cardUid): ?MembershipCard
    {
        $data = DB::table($this->table)
            ->where('card_uid', strtoupper($cardUid))
            ->whereNull('deleted_at')
            ->first();
        
        if (!$data) {
            return null;
        }
        
        return $this->mapToEntity($data);
    }
    
    public function findByStatus(string $status): array
    {
        $data = DB::table($this->table)
            ->where('status', $status)
            ->whereNull('deleted_at')
            ->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }
    
    public function findActive(): array
    {
        $data = DB::table($this->table)
            ->where('status', 'active')
            ->where('expiry_date', '>=', Carbon::now()->format('Y-m-d'))
            ->whereNull('deleted_at')
            ->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }
    
    public function findExpiring(int $daysAhead = 30): array
    {
        $data = DB::table($this->table)
            ->where('status', 'active')
            ->whereBetween('expiry_date', [
                Carbon::now()->format('Y-m-d'),
                Carbon::now()->addDays($daysAhead)->format('Y-m-d')
            ])
            ->whereNull('deleted_at')
            ->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }
    
    public function findExpired(): array
    {
        $data = DB::table($this->table)
            ->where('status', 'active')
            ->where('expiry_date', '<', Carbon::now()->format('Y-m-d'))
            ->whereNull('deleted_at')
            ->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }
    
    public function findNotPrinted(): array
    {
        $data = DB::table($this->table)
            ->whereNull('printed_at')
            ->whereNull('deleted_at')
            ->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }
    
    public function findNotEncoded(): array
    {
        $data = DB::table($this->table)
            ->whereNull('encoded_at')
            ->whereNull('deleted_at')
            ->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }
    
    public function findByOfficerId(int $officerId): array
    {
        $data = DB::table($this->table)
            ->join('mc_subscriptions', 'mc_membership_cards.subscription_id', '=', 'mc_subscriptions.id')
            ->where('mc_subscriptions.officer_id', $officerId)
            ->whereNull('mc_membership_cards.deleted_at')
            ->select('mc_membership_cards.*')
            ->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }
    
    public function save(MembershipCard $card): MembershipCard
    {
        $data = [
            'subscription_id' => $card->getSubscriptionId(),
            'card_uid' => $card->getCardUid()->getValue(),
            'printed_at' => $card->getPrintedAt()?->format('Y-m-d H:i:s'),
            'encoded_at' => $card->getEncodedAt()?->format('Y-m-d H:i:s'),
            'expiry_date' => $card->getExpiryDate()->format('Y-m-d'),
            'status' => $card->getStatus(),
            'encoded_data' => $card->getEncodedData() ? json_encode($card->getEncodedData()) : null,
            'notes' => $card->getNotes(),
            'card_token' => $card->getCardToken(),
            'card_token_hex' => $card->getCardTokenHex(),
            'serial_id' => $card->getSerialId(),
            'is_replacement' => $card->isReplacement(),
            'show_expiry_date' => $card->getShowExpiryDate(),
            'updated_at' => now(),
        ];
        
        if ($card->getId()) {
            DB::table($this->table)->where('id', $card->getId())->update($data);
            return $card;
        } else {
            $data['created_at'] = now();
            $id = DB::table($this->table)->insertGetId($data);
            $card->setId($id);
            return $card;
        }
    }
    
    public function delete(int $id): bool
    {
        return DB::table($this->table)->where('id', $id)->update(['deleted_at' => now()]) > 0;
    }
    
    public function exists(int $id): bool
    {
        return DB::table($this->table)->where('id', $id)->whereNull('deleted_at')->exists();
    }
    
    public function existsByCardUid(string $cardUid): bool
    {
        return DB::table($this->table)
            ->where('card_uid', strtoupper($cardUid))
            ->whereNull('deleted_at')
            ->exists();
    }
    
    public function findByToken(string $token): ?MembershipCard
    {
        $data = DB::table($this->table)
            ->where('card_token', $token)
            ->whereNull('deleted_at')
            ->first();
        
        if (!$data) {
            return null;
        }
        
        return $this->mapToEntity($data);
    }
    
    public function findByTokenHex(string $tokenHex): ?MembershipCard
    {
        $data = DB::table($this->table)
            ->where('card_token_hex', strtoupper($tokenHex))
            ->whereNull('deleted_at')
            ->first();
        
        if (!$data) {
            return null;
        }
        
        return $this->mapToEntity($data);
    }
    
    private function mapToEntity($data): MembershipCard
    {
        $card = new MembershipCard(
            subscriptionId: $data->subscription_id,
            cardUid: new CardUID($data->card_uid),
            expiryDate: Carbon::parse($data->expiry_date),
            status: $data->status,
            printedAt: $data->printed_at ? Carbon::parse($data->printed_at) : null,
            encodedAt: $data->encoded_at ? Carbon::parse($data->encoded_at) : null,
            encodedData: $data->encoded_data ? json_decode($data->encoded_data, true) : null,
            notes: $data->notes,
            cardToken: $data->card_token ?? null,
            cardTokenHex: $data->card_token_hex ?? null,
            serialId: $data->serial_id ?? null,
            isReplacement: (bool) ($data->is_replacement ?? false),
            showExpiryDate: isset($data->show_expiry_date) ? (bool) $data->show_expiry_date : true
        );
        
        $card->setId($data->id);
        
        return $card;
    }
}

