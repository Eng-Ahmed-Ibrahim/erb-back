<?php

namespace Modules\ActivitiesSubscriptions\Application\Handlers;

use Modules\ActivitiesSubscriptions\Application\Commands\CreateAcademyCommand;
use Modules\ActivitiesSubscriptions\Domain\Entities\Academy;
use Modules\ActivitiesSubscriptions\Domain\ValueObjects\Percentage;
use Modules\ActivitiesSubscriptions\Domain\Repositories\AcademyRepositoryInterface;

class CreateAcademyHandler
{
    public function __construct(
        private AcademyRepositoryInterface $academyRepository
    ) {}

    public function handle(CreateAcademyCommand $command): Academy
    {
        $dto = $command->academyDTO;
        
        $academy = new Academy(
            name: $dto->name,
            contracted: $dto->contracted,
            revenueShareInfantry: new Percentage($dto->revenueShareInfantry),
            revenueShareAcademy: new Percentage($dto->revenueShareAcademy),
            workingDays: $dto->workingDays,
            status: $dto->status
        );

        return $this->academyRepository->save($academy);
    }
}
