<?php

namespace Modules\MembershipCards\Application\Handlers;

use Carbon\Carbon;
use Modules\MembershipCards\Application\Commands\CreateBeneficiaryCommand;
use Modules\MembershipCards\Domain\Entities\Beneficiary;
use Modules\MembershipCards\Domain\Repositories\BeneficiaryRepositoryInterface;
use Modules\MembershipCards\Domain\Repositories\OfficerRepositoryInterface;

class CreateBeneficiaryHandler
{
    public function __construct(
        private BeneficiaryRepositoryInterface $beneficiaryRepository,
        private OfficerRepositoryInterface $officerRepository
    ) {}

    public function handle(CreateBeneficiaryCommand $command): Beneficiary
    {
        $dto = $command->beneficiaryDTO;
        
        // Check if officer exists
        if (!$this->officerRepository->exists($dto->officerId)) {
            throw new \InvalidArgumentException('الضابط غير موجود');
        }
        
        // Check if national ID already exists (if provided)
        if ($dto->nationalId && $this->beneficiaryRepository->findByNationalId($dto->nationalId)) {
            throw new \InvalidArgumentException('يوجد مستفيد بهذا الرقم القومي بالفعل');
        }
        
        // Use provided family_index or null (no longer auto-generating)
        $familyIndex = $dto->familyIndex;
        
        $beneficiary = new Beneficiary(
            officerId: $dto->officerId,
            fullName: $dto->fullName,
            relationshipType: $dto->relationshipType,
            familyIndex: $familyIndex,
            birthDate: $dto->birthDate ? Carbon::parse($dto->birthDate) : null,
            nationalId: $dto->nationalId,
            notes: $dto->notes,
            photo: $dto->photo
        );

        return $this->beneficiaryRepository->save($beneficiary);
    }
}

