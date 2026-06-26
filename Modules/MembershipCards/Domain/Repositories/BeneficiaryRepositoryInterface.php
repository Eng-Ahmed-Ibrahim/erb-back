<?php

namespace Modules\MembershipCards\Domain\Repositories;

use Modules\MembershipCards\Domain\Entities\Beneficiary;

interface BeneficiaryRepositoryInterface
{
    public function findById(int $id): ?Beneficiary;
    
    public function findAll(): array;

    public function paginate(int $perPage = 15, ?string $search = null): array;
    
    public function findByOfficerId(int $officerId): array;
    
    public function findByNationalId(string $nationalId): ?Beneficiary;
    
    public function findByRelationshipType(string $relationshipType): array;
    
    public function findByOfficerAndFamilyIndex(int $officerId, int $familyIndex): ?Beneficiary;
    
    public function getNextFamilyIndex(int $officerId): int;
    
    public function search(string $query): array;
    
    public function save(Beneficiary $beneficiary): Beneficiary;
    
    public function delete(int $id): bool;
    
    public function exists(int $id): bool;
    
    public function countByOfficerId(int $officerId): int;
}

