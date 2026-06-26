<?php

namespace Modules\ActivitiesSubscriptions\Domain\Entities;

use Modules\ActivitiesSubscriptions\Domain\ValueObjects\Price;

class Offer
{
    private ?int $id = null;
    private int $academyId;
    private string $name;
    private ?int $numClasses;
    private ?int $numHours;
    private int $durationDays;
    private bool $active;
    private array $availableDays;
    private Price $priceInfantry;
    private Price $priceCivilian;
    private Price $priceOther;

    public function __construct(
        int $academyId,
        string $name,
        ?int $numClasses,
        ?int $numHours,
        int $durationDays,
        array $availableDays,
        Price $priceInfantry,
        Price $priceCivilian,
        Price $priceOther,
        bool $active = true
    ) {
        $this->validateOfferType($numClasses, $numHours);
        $this->validateAvailableDays($availableDays);
        
        $this->academyId = $academyId;
        $this->name = $name;
        $this->numClasses = $numClasses;
        $this->numHours = $numHours;
        $this->durationDays = $durationDays;
        $this->active = $active;
        $this->availableDays = $availableDays;
        $this->priceInfantry = $priceInfantry;
        $this->priceCivilian = $priceCivilian;
        $this->priceOther = $priceOther;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getAcademyId(): int
    {
        return $this->academyId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getNumClasses(): ?int
    {
        return $this->numClasses;
    }

    public function getNumHours(): ?int
    {
        return $this->numHours;
    }

    public function getDurationDays(): int
    {
        return $this->durationDays;
    }

    public function setDurationDays(int $durationDays): void
    {
        $this->durationDays = $durationDays;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function getAvailableDays(): array
    {
        return $this->availableDays;
    }

    public function setAvailableDays(array $availableDays): void
    {
        $this->validateAvailableDays($availableDays);
        $this->availableDays = $availableDays;
    }

    public function getPriceInfantry(): Price
    {
        return $this->priceInfantry;
    }

    public function getPriceCivilian(): Price
    {
        return $this->priceCivilian;
    }

    public function getPriceOther(): Price
    {
        return $this->priceOther;
    }
    
    
    public function setPriceInfantry(Price $priceInfantry): void
    {
        $this->priceInfantry = $priceInfantry;
    }
    
    public function setPriceCivilian(Price $priceCivilian): void
    {
        $this->priceCivilian = $priceCivilian;
    }
    
    public function setPriceOther(Price $priceOther): void
    {
        $this->priceOther = $priceOther;
    }
    
    public function setNumClasses(int $numClasses): void
    {
        $this->numClasses = $numClasses;
         }

    public function setNumHours(int $numHours): void
    {
        $this->numHours = $numHours;
    }

    

    public function getPriceBySubscriberType(string $subscriberType): Price
    {
        return match ($subscriberType) {
            'infantry' => $this->priceInfantry,
            'civilian' => $this->priceCivilian,
            'other' => $this->priceOther,
            default => throw new \InvalidArgumentException("Invalid subscriber type: {$subscriberType}")
        };
    }

    public function isClassBased(): bool
    {
        return $this->numClasses !== null;
    }

    public function isHourly(): bool
    {
        return $this->numHours !== null;
    }

    public function getInitialValue(): int|float
    {
        return $this->isClassBased() ? $this->numClasses : $this->numHours;
    }

    public function activate(): void
    {
        $this->active = true;
    }

    public function deactivate(): void
    {
        $this->active = false;
    }

    private function validateOfferType(?int $numClasses, ?int $numHours): void
    {
        if ($numClasses === null && $numHours === null) {
            throw new \InvalidArgumentException('Offer must specify either number of classes or hours');
        }
        
        // if ($numClasses !== null && $numHours !== null) {
        //     throw new \InvalidArgumentException('Offer cannot specify both classes and hours');
        // }
        
        if ($numClasses !== null && $numClasses <= 0) {
            throw new \InvalidArgumentException('Number of classes must be positive');
        }
        
        if ($numHours !== null && $numHours <= 0) {
            throw new \InvalidArgumentException('Number of hours must be positive');
        }
    }

    private function validateAvailableDays(array $availableDays): void
    {
        $validDays = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        
        foreach ($availableDays as $day) {
            if (!in_array($day, $validDays)) {
                throw new \InvalidArgumentException("Invalid available day: {$day}");
            }
        }
    }
}
