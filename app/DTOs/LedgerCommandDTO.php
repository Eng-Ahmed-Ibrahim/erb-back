<?php

namespace App\DTOs;

class LedgerCommandDTO
{
    public string $transactionType; // 'in_coming', 'out_going', 'returned', 'transfare', 'tainted', 'consumption', 'adjustment'
    public string $recipeId;
    public string $departmentId;
    public float $quantity;
    public string $entryType; // 'debit' or 'credit'
    public ?string $fromDepartmentId;
    public ?string $toDepartmentId;
    public float $unitPrice;
    public ?string $expireDate;
    public ?string $invoiceId;
    public ?string $orderId;
    public ?string $sourceType; // 'invoice', 'order', 'adjustment'
    public ?string $notes;
    public ?array $metadata;

    /**
     * Create a new LedgerCommandDTO instance
     */
    public function __construct(array $data)
    {
        $this->transactionType = $data['transaction_type'];
        $this->recipeId = $data['recipe_id'];
        $this->departmentId = $data['department_id'];
        $this->quantity = (float) $data['quantity'];
        $this->entryType = $data['entry_type']; // 'debit' or 'credit'
        $this->fromDepartmentId = $data['from_department_id'] ?? null;
        $this->toDepartmentId = $data['to_department_id'] ?? null;
        $this->unitPrice = (float) ($data['unit_price'] ?? 0);
        $this->expireDate = $data['expire_date'] ?? null;
        $this->invoiceId = $data['invoice_id'] ?? null;
        $this->orderId = $data['order_id'] ?? null;
        $this->sourceType = $data['source_type'] ?? 'invoice';
        $this->notes = $data['notes'] ?? null;
        $this->metadata = $data['metadata'] ?? null;
    }

    /**
     * Validate the command
     */
    public function validate(): array
    {
        $errors = [];

        if (empty($this->recipeId)) {
            $errors[] = 'Recipe ID is required';
        }

        if (empty($this->departmentId)) {
            $errors[] = 'Department ID is required';
        }

        if ($this->quantity <= 0) {
            $errors[] = 'Quantity must be greater than 0';
        }

        if (!in_array($this->entryType, ['debit', 'credit'])) {
            $errors[] = 'Entry type must be "debit" or "credit"';
        }

        if ($this->unitPrice < 0) {
            $errors[] = 'Unit price cannot be negative';
        }

        return $errors;
    }

    /**
     * Check if command is valid
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }

    /**
     * Get entry type for ledger (debit/credit)
     */
    public function getEntryType(): string
    {
        return $this->entryType;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'transaction_type' => $this->transactionType,
            'recipe_id' => $this->recipeId,
            'department_id' => $this->departmentId,
            'quantity' => $this->quantity,
            'entry_type' => $this->entryType,
            'from_department_id' => $this->fromDepartmentId,
            'to_department_id' => $this->toDepartmentId,
            'unit_price' => $this->unitPrice,
            'expire_date' => $this->expireDate,
            'invoice_id' => $this->invoiceId,
            'order_id' => $this->orderId,
            'source_type' => $this->sourceType,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Create command for debiting inventory (adding)
     */
    public static function makeDebitCommand(array $data): self
    {
        $data['entry_type'] = 'debit';
        return new self($data);
    }

    /**
     * Create command for crediting inventory (removing)
     */
    public static function makeCreditCommand(array $data): self
    {
        $data['entry_type'] = 'credit';
        return new self($data);
    }
}

