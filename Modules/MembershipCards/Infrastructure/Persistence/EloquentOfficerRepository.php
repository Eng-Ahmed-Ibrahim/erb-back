<?php

namespace Modules\MembershipCards\Infrastructure\Persistence;

use Modules\MembershipCards\Domain\Entities\Officer;
use Modules\MembershipCards\Domain\Repositories\OfficerRepositoryInterface;
use Modules\MembershipCards\Domain\ValueObjects\MilitaryNumber;
use Illuminate\Support\Facades\DB;

class EloquentOfficerRepository implements OfficerRepositoryInterface
{
    protected string $table = 'mc_officers';

    public function findById(int $id): ?Officer
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

    public function paginate(int $perPage = 15, ?string $search = null): array
    {
        $query = DB::table($this->table)->whereNull('deleted_at');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('national_id', 'like', "%{$search}%")
                  ->orWhere('military_number', 'like', "%{$search}%")
                  ->orWhere('membership_id', 'like', "%{$search}%")
                  ->orWhere('seniority_number', 'like', "%{$search}%");
            });
        }

        $paginator = $query->orderByDesc('id')->paginate($perPage);

        return [
            'data' => collect($paginator->items())->map(fn($item) => $this->mapToEntity($item))->toArray(),
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
        ];
    }
    
    public function findByNationalId(string $nationalId): ?Officer
    {
        $data = DB::table($this->table)->where('national_id', $nationalId)->whereNull('deleted_at')->first();
        
        if (!$data) {
            return null;
        }
        
        return $this->mapToEntity($data);
    }
    
    public function findByMilitaryNumber(string $militaryNumber): ?Officer
    {
        $data = DB::table($this->table)->where('military_number', $militaryNumber)->whereNull('deleted_at')->first();
        
        if (!$data) {
            return null;
        }
        
        return $this->mapToEntity($data);
    }

    public function findByMembershipId(string $membershipId): ?Officer
    {
        $data = DB::table($this->table)->where('membership_id', $membershipId)->whereNull('deleted_at')->first();
        
        if (!$data) {
            return null;
        }
        
        return $this->mapToEntity($data);
    }
    
    public function findByFullName(string $fullName): array
    {
        $data = DB::table($this->table)
            ->where('full_name', 'like', "%{$fullName}%")
            ->whereNull('deleted_at')
            ->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }
    
    public function findByRank(string $rank): array
    {
        $data = DB::table($this->table)->where('rank', $rank)->whereNull('deleted_at')->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }
    
    public function findByWeaponType(string $weaponType): array
    {
        $data = DB::table($this->table)->where('weapon_type', $weaponType)->whereNull('deleted_at')->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }
    
    public function search(string $query): array
    {
        $data = DB::table($this->table)
            ->whereNull('deleted_at')
            ->where(function ($q) use ($query) {
                $q->where('full_name', 'like', "%{$query}%")
                  ->orWhere('national_id', 'like', "%{$query}%")
                  ->orWhere('military_number', 'like', "%{$query}%")
                  ->orWhere('membership_id', 'like', "%{$query}%")
                  ->orWhere('seniority_number', 'like', "%{$query}%");
            })
            ->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }
    
    public function save(Officer $officer): Officer
    {
        $data = [
            'national_id' => $officer->getNationalId(),
            'full_name' => $officer->getFullName(),
            'rank' => $officer->getRank(),
            'weapon_type' => $officer->getWeaponType(),
            'seniority_number' => $officer->getSeniorityNumber(),
            'military_number' => $officer->getMilitaryNumber()->getValue(),
            'membership_id' => $officer->getMembershipId(),
            'age' => $officer->getAge(),
            'notes' => $officer->getNotes(),
            'photo' => $officer->getPhoto(), // Include photo even if null
            'service_status' => $officer->getServiceStatus(),
            'is_staff_officer' => $officer->isStaffOfficer(),
            'updated_at' => now(),
        ];
        
        if ($officer->getId()) {
            DB::table($this->table)->where('id', $officer->getId())->update($data);
            // Reload the entity to ensure it has the latest data
            return $this->findById($officer->getId());
        } else {
            $data['created_at'] = now();
            $id = DB::table($this->table)->insertGetId($data);
            $officer->setId($id);
            return $this->findById($id);
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
    
    public function existsByNationalId(string $nationalId): bool
    {
        return DB::table($this->table)->where('national_id', $nationalId)->whereNull('deleted_at')->exists();
    }
    
    public function existsByMilitaryNumber(string $militaryNumber): bool
    {
        return DB::table($this->table)->where('military_number', $militaryNumber)->whereNull('deleted_at')->exists();
    }
    
    private function mapToEntity($data): Officer
    {
        $officer = new Officer(
            nationalId: $data->national_id,
            fullName: $data->full_name,
            rank: $data->rank,
            weaponType: $data->weapon_type,
            militaryNumber: new MilitaryNumber($data->military_number),
            seniorityNumber: $data->seniority_number,
            membershipId: $data->membership_id ?? null,
            age: $data->age,
            notes: $data->notes,
            photo: $data->photo ?? null,
            serviceStatus: $data->service_status ?? null,
            isStaffOfficer: (bool) ($data->is_staff_officer ?? false)
        );
        
        $officer->setId($data->id);
        
        return $officer;
    }
}
