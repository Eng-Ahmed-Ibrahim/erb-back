<?php

namespace Modules\MembershipCards\Infrastructure\Persistence;

use Carbon\Carbon;
use Modules\MembershipCards\Domain\Entities\Beneficiary;
use Modules\MembershipCards\Domain\Repositories\BeneficiaryRepositoryInterface;
use Illuminate\Support\Facades\DB;

class EloquentBeneficiaryRepository implements BeneficiaryRepositoryInterface
{
    protected string $table = 'mc_beneficiaries';

    public function findById(int $id): ?Beneficiary
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
                  ->orWhere('national_id', 'like', "%{$search}%");
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
    
    public function findByOfficerId(int $officerId): array
    {
        $data = DB::table($this->table)
            ->where('officer_id', $officerId)
            ->whereNull('deleted_at')
            ->orderByRaw('family_index IS NULL, family_index')
            ->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }
    
    public function findByNationalId(string $nationalId): ?Beneficiary
    {
        $data = DB::table($this->table)
            ->where('national_id', $nationalId)
            ->whereNull('deleted_at')
            ->first();
        
        if (!$data) {
            return null;
        }
        
        return $this->mapToEntity($data);
    }
    
    public function findByRelationshipType(string $relationshipType): array
    {
        $data = DB::table($this->table)
            ->where('relationship_type', $relationshipType)
            ->whereNull('deleted_at')
            ->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }
    
    public function findByOfficerAndFamilyIndex(int $officerId, int $familyIndex): ?Beneficiary
    {
        $data = DB::table($this->table)
            ->where('officer_id', $officerId)
            ->where('family_index', $familyIndex)
            ->whereNull('deleted_at')
            ->first();
        
        if (!$data) {
            return null;
        }
        
        return $this->mapToEntity($data);
    }
    
    public function getNextFamilyIndex(int $officerId): int
    {
        $maxIndex = DB::table($this->table)
            ->where('officer_id', $officerId)
            ->whereNull('deleted_at')
            ->max('family_index');
        
        return ($maxIndex ?? 0) + 1;
    }
    
    public function search(string $query): array
    {
        $data = DB::table($this->table)
            ->whereNull('deleted_at')
            ->where(function ($q) use ($query) {
                $q->where('full_name', 'like', "%{$query}%")
                  ->orWhere('national_id', 'like', "%{$query}%");
            })
            ->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }
    
    public function save(Beneficiary $beneficiary): Beneficiary
    {
        $data = [
            'officer_id' => $beneficiary->getOfficerId(),
            'full_name' => $beneficiary->getFullName(),
            'relationship_type' => $beneficiary->getRelationshipType(),
            'birth_date' => $beneficiary->getBirthDate()?->format('Y-m-d'),
            'national_id' => $beneficiary->getNationalId(),
            'family_index' => $beneficiary->getFamilyIndex(),
            'notes' => $beneficiary->getNotes(),
            'photo' => $beneficiary->getPhoto(), // Include photo even if null
            'updated_at' => now(),
        ];
        
        if ($beneficiary->getId()) {
            DB::table($this->table)->where('id', $beneficiary->getId())->update($data);
            // Reload the entity to ensure it has the latest data
            return $this->findById($beneficiary->getId());
        } else {
            $data['created_at'] = now();
            $id = DB::table($this->table)->insertGetId($data);
            $beneficiary->setId($id);
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
    
    public function countByOfficerId(int $officerId): int
    {
        return DB::table($this->table)
            ->where('officer_id', $officerId)
            ->whereNull('deleted_at')
            ->count();
    }
    
    private function mapToEntity($data): Beneficiary
    {
        $beneficiary = new Beneficiary(
            officerId: $data->officer_id,
            fullName: $data->full_name,
            relationshipType: $data->relationship_type,
            familyIndex: $data->family_index ?? null,
            birthDate: $data->birth_date ? Carbon::parse($data->birth_date) : null,
            nationalId: $data->national_id,
            notes: $data->notes,
            photo: $data->photo ?? null
        );
        
        $beneficiary->setId($data->id);
        
        return $beneficiary;
    }
}

