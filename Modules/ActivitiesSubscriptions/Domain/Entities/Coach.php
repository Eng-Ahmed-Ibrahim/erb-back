<?php

namespace Modules\ActivitiesSubscriptions\Domain\Entities;

class Coach
{
    private ?int $id = null;
    private int $academyId;
    private string $name;
    private ?string $phone;
    private ?string $bio;
    private bool $active;

    public function __construct(
        int $academyId,
        string $name,
        ?string $phone = null,
        ?string $bio = null,
        bool $active = true
    ) {
        $this->academyId = $academyId;
        $this->name = $name;
        $this->phone = $phone;
        $this->bio = $bio;
        $this->active = $active;
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

    public function setAcademyId(int $academyId): void
    {
        $this->academyId = $academyId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): void
    {
        $this->phone = $phone;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): void
    {
        $this->bio = $bio;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }
}
