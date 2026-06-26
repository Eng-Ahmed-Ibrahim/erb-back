<?php

namespace Modules\ActivitiesSubscriptions\Application\Handlers;

use Modules\ActivitiesSubscriptions\Application\Commands\CreateSubscriberCommand;
use Modules\ActivitiesSubscriptions\Domain\Entities\Subscriber;
use Modules\ActivitiesSubscriptions\Domain\Repositories\SubscriberRepositoryInterface;

class CreateSubscriberHandler
{
    public function __construct(
        private SubscriberRepositoryInterface $subscriberRepository
    ) {}

    public function handle(CreateSubscriberCommand $command): Subscriber
    {
        $dto = $command->subscriberDTO;
        
        $subscriber = new Subscriber(
            fullName: $dto->fullName,
            type: $dto->type,
            nationalId: $dto->nationalId,
            militaryId: $dto->militaryId,
            phone: $dto->phone
        );

        return $this->subscriberRepository->save($subscriber);
    }
}
