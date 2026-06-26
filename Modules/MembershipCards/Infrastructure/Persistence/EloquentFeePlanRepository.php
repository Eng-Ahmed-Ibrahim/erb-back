<?php

namespace Modules\MembershipCards\Infrastructure\Persistence;

use Modules\MembershipCards\Domain\Entities\FeePlan;
use Modules\MembershipCards\Domain\Repositories\FeePlanRepositoryInterface;
use Modules\MembershipCards\Domain\ValueObjects\Price;
use Illuminate\Support\Facades\DB;

class EloquentFeePlanRepository implements FeePlanRepositoryInterface
{
    protected string $table = 'mc_fee_plans';

    public function findById(int $id): ?FeePlan
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
    
    public function findActive(): array
    {
        $data = DB::table($this->table)
            ->where('active', true)
            ->whereNull('deleted_at')
            ->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }
    
    public function findByBeneficiaryType(string $beneficiaryType): array
    {
        $data = DB::table($this->table)
            ->where('beneficiary_type', $beneficiaryType)
            ->whereNull('deleted_at')
            ->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }
    
    public function findByWeaponType(string $weaponType): array
    {
        $data = DB::table($this->table)
            ->where('weapon_type', $weaponType)
            ->whereNull('deleted_at')
            ->orderBy('beneficiary_type')
            ->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }
    
    public function findActiveByWeaponType(string $weaponType): array
    {
        $data = DB::table($this->table)
            ->where('weapon_type', $weaponType)
            ->where('active', true)
            ->whereNull('deleted_at')
            ->orderBy('beneficiary_type')
            ->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }
    
    public function findActiveByBeneficiaryType(string $beneficiaryType): ?FeePlan
    {
        $data = DB::table($this->table)
            ->where('beneficiary_type', $beneficiaryType)
            ->where('active', true)
            ->whereNull('deleted_at')
            ->orderByDesc('version')
            ->first();
        
        if (!$data) {
            return null;
        }
        
        return $this->mapToEntity($data);
    }
    
    public function findActiveByBeneficiaryTypeAndWeaponType(string $beneficiaryType, string $weaponType): ?FeePlan
    {
        $data = DB::table($this->table)
            ->where('beneficiary_type', $beneficiaryType)
            ->where('weapon_type', $weaponType)
            ->where('active', true)
            ->whereNull('deleted_at')
            ->orderByDesc('version')
            ->first();
        
        if (!$data) {
            return null;
        }
        
        return $this->mapToEntity($data);
    }
    
    public function findByVersion(int $version): array
    {
        $data = DB::table($this->table)
            ->where('version', $version)
            ->whereNull('deleted_at')
            ->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }
    
    public function findLatestVersion(string $beneficiaryType): ?FeePlan
    {
        $data = DB::table($this->table)
            ->where('beneficiary_type', $beneficiaryType)
            ->whereNull('deleted_at')
            ->orderByDesc('version')
            ->first();
        
        if (!$data) {
            return null;
        }
        
        return $this->mapToEntity($data);
    }
    
    public function save(FeePlan $feePlan): FeePlan
    {
        $data = [
            'name' => $feePlan->getName(),
            'beneficiary_type' => $feePlan->getBeneficiaryType(),
            'weapon_type' => $feePlan->getWeaponType(),
            'establishment_fee' => $feePlan->getEstablishmentFee()->getAmount(),
            'annual_subscription_fee' => $feePlan->getAnnualSubscriptionFee()->getAmount(),
            'issuance_fee' => $feePlan->getIssuanceFee()->getAmount(),
            'version' => $feePlan->getVersion(),
            'active' => $feePlan->isActive(),
            'description' => $feePlan->getDescription(),
            'age_range' => $feePlan->getAgeRange() ? json_encode($feePlan->getAgeRange()) : null,
            'updated_at' => now(),
        ];
        
        if ($feePlan->getId()) {
            DB::table($this->table)->where('id', $feePlan->getId())->update($data);
            return $feePlan;
        } else {
            $data['created_at'] = now();
            $id = DB::table($this->table)->insertGetId($data);
            $feePlan->setId($id);
            return $feePlan;
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
    
    public function deactivateAllByBeneficiaryTypeAndWeapon(string $beneficiaryType, string $weaponType): void
    {
        DB::table($this->table)
            ->where('beneficiary_type', $beneficiaryType)
            ->where('weapon_type', $weaponType)
            ->whereNull('deleted_at')
            ->update(['active' => false, 'updated_at' => now()]);
    }
    
    private function mapToEntity($data): FeePlan
    {
        $feePlan = new FeePlan(
            name: $data->name,
            beneficiaryType: $data->beneficiary_type,
            establishmentFee: new Price((float) $data->establishment_fee),
            annualSubscriptionFee: new Price((float) $data->annual_subscription_fee),
            issuanceFee: new Price((float) $data->issuance_fee),
            weaponType: $data->weapon_type ?? 'infantry',
            version: $data->version,
            active: (bool) $data->active,
            description: $data->description,
            ageRange: $data->age_range ? json_decode($data->age_range, true) : null
        );
        
        $feePlan->setId($data->id);
        
        return $feePlan;
    }
}

