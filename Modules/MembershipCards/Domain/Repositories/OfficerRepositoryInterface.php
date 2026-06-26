<?php

namespace Modules\MembershipCards\Domain\Repositories;

use Modules\MembershipCards\Domain\Entities\Officer;

interface OfficerRepositoryInterface
{
    public function findById(int $id): ?Officer;
    
    public function findAll(): array;

    public function paginate(int $perPage = 15, ?string $search = null): array;
    
    public function findByNationalId(string $nationalId): ?Officer;
    
    public function findByMilitaryNumber(string $militaryNumber): ?Officer;

    public function findByMembershipId(string $membershipId): ?Officer;
    
    public function findByFullName(string $fullName): array;
    
    public function findByRank(string $rank): array;
    
    public function findByWeaponType(string $weaponType): array;
    
    public function search(string $query): array;
    
    public function save(Officer $officer): Officer;
    
    public function delete(int $id): bool;
    
    public function exists(int $id): bool;
    
    public function existsByNationalId(string $nationalId): bool;
    
    public function existsByMilitaryNumber(string $militaryNumber): bool;
}
