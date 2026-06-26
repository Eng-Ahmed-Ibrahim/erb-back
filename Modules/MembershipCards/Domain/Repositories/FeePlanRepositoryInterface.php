<?php

namespace Modules\MembershipCards\Domain\Repositories;

use Modules\MembershipCards\Domain\Entities\FeePlan;

interface FeePlanRepositoryInterface
{
    public function findById(int $id): ?FeePlan;
    
    public function findAll(): array;
    
    public function findActive(): array;
    
    public function findByBeneficiaryType(string $beneficiaryType): array;
    
    public function findActiveByBeneficiaryType(string $beneficiaryType): ?FeePlan;

    public function findByWeaponType(string $weaponType): array;

    public function findActiveByWeaponType(string $weaponType): array;

    public function findByVersion(int $version): array;
    
    public function findLatestVersion(string $beneficiaryType): ?FeePlan;
    
    public function save(FeePlan $feePlan): FeePlan;
    
    public function delete(int $id): bool;
    
    public function exists(int $id): bool;
    
    public function deactivateAllByBeneficiaryTypeAndWeapon(string $beneficiaryType, string $weaponType): void;
}

