<?php

namespace Modules\ActivitiesSubscriptions\Application\Handlers;

use Modules\ActivitiesSubscriptions\Application\Commands\CreateOfferCommand;
use Modules\ActivitiesSubscriptions\Domain\Entities\Offer;
use Modules\ActivitiesSubscriptions\Domain\ValueObjects\Price;
use Modules\ActivitiesSubscriptions\Domain\Repositories\OfferRepositoryInterface;

class CreateOfferHandler
{
    public function __construct(
        private OfferRepositoryInterface $offerRepository
    ) {}

    public function handle(CreateOfferCommand $command): Offer
    {
        $dto = $command->offerDTO;
        
        $offer = new Offer(
            academyId: $dto->academyId,
            name: $dto->name,
            numClasses: $dto->numClasses,
            numHours: $dto->numHours,
            durationDays: $dto->durationDays,
            availableDays: $dto->availableDays,
            priceInfantry: new Price($dto->priceInfantry),
            priceCivilian: new Price($dto->priceCivilian),
            priceOther: new Price($dto->priceOther),
            active: $dto->active
        );

        return $this->offerRepository->save($offer);
    }
}
