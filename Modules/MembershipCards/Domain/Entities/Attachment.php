<?php

namespace Modules\MembershipCards\Domain\Entities;

class Attachment
{
    private ?int $id = null;
    private string $attachableType;
    private int $attachableId;
    private string $originalName;
    private string $filePath;
    private ?string $mimeType;
    private ?int $fileSize;
    private ?string $description;

    public function __construct(
        string $attachableType,
        int $attachableId,
        string $originalName,
        string $filePath,
        ?string $mimeType = null,
        ?int $fileSize = null,
        ?string $description = null
    ) {
        $this->attachableType = $attachableType;
        $this->attachableId = $attachableId;
        $this->originalName = $originalName;
        $this->filePath = $filePath;
        $this->mimeType = $mimeType;
        $this->fileSize = $fileSize;
        $this->description = $description;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getAttachableType(): string
    {
        return $this->attachableType;
    }

    public function getAttachableId(): int
    {
        return $this->attachableId;
    }

    public function getOriginalName(): string
    {
        return $this->originalName;
    }

    public function setOriginalName(string $originalName): void
    {
        $this->originalName = $originalName;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function setFilePath(string $filePath): void
    {
        $this->filePath = $filePath;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(?string $mimeType): void
    {
        $this->mimeType = $mimeType;
    }

    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    public function setFileSize(?int $fileSize): void
    {
        $this->fileSize = $fileSize;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function isForOfficer(): bool
    {
        return $this->attachableType === 'officer';
    }

    public function isForBeneficiary(): bool
    {
        return $this->attachableType === 'beneficiary';
    }

    public function getFileSizeFormatted(): string
    {
        if ($this->fileSize === null) {
            return 'Unknown';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->fileSize;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }
}
