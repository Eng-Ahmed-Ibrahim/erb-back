<?php

namespace Modules\ActivitiesSubscriptions\Application\Handlers;

use Modules\ActivitiesSubscriptions\Application\Commands\CreateCoachCommand;
use Modules\ActivitiesSubscriptions\Domain\Entities\Coach;
use Modules\ActivitiesSubscriptions\Domain\Repositories\CoachRepositoryInterface;

class CreateCoachHandler
{
    public function __construct(
        private CoachRepositoryInterface $coachRepository
    ) {}

    public function handle(CreateCoachCommand $command): Coach
    {
        $coach = new Coach(
            academyId: $command->dto->academyId,
            name: $command->dto->name,
            phone: $command->dto->phone,
            bio: $command->dto->bio,
            active: $command->dto->active
        );

        return $this->coachRepository->save($coach);
    }
}
