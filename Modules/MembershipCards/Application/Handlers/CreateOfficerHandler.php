<?php

namespace Modules\MembershipCards\Application\Handlers;

use Modules\MembershipCards\Application\Commands\CreateOfficerCommand;
use Modules\MembershipCards\Domain\Entities\Officer;
use Modules\MembershipCards\Domain\Repositories\OfficerRepositoryInterface;
use Modules\MembershipCards\Domain\ValueObjects\MilitaryNumber;

class CreateOfficerHandler
{
    public function __construct(
        private OfficerRepositoryInterface $officerRepository
    ) {}

    public function handle(CreateOfficerCommand $command): Officer
    {
        $dto = $command->officerDTO;
        
        // Check if national ID already exists
        if ($this->officerRepository->existsByNationalId($dto->nationalId)) {
            throw new \InvalidArgumentException('يوجد ضابط بهذا الرقم القومي بالفعل');
        }
        
        // Generate military number if not provided
        $militaryNumber = $dto->militaryNumber 
            ? new MilitaryNumber($dto->militaryNumber)
            : MilitaryNumber::generate('MC');
        
        // Check if military number already exists
        if ($this->officerRepository->existsByMilitaryNumber($militaryNumber->getValue())) {
            throw new \InvalidArgumentException('يوجد ضابط بهذا الرقم العسكري بالفعل');
        }
        
        $officer = new Officer(
            nationalId: $dto->nationalId,
            fullName: $dto->fullName,
            rank: $dto->rank,
            weaponType: $dto->weaponType,
            militaryNumber: $militaryNumber,
            seniorityNumber: $dto->seniorityNumber,
            membershipId: $dto->membershipId,
            age: $dto->age,
            notes: $dto->notes,
            photo: $dto->photo,
            serviceStatus: $dto->serviceStatus,
            isStaffOfficer: $dto->isStaffOfficer
        );

        return $this->officerRepository->save($officer);
    }
}
