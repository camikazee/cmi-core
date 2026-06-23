<?php

declare(strict_types=1);

namespace Core\Model\Metadata;

use DateTimeImmutable;

/**
 * Interface DeleteInterface.
 */
interface DeleteInterface
{
    /**
     * @return $this
     */
    public function setDeletedAt(?DateTimeImmutable $updatedAt): self;

    public function getDeletedAt(): ?DateTimeImmutable;

    /**
     * @return $this
     */
    public function setDeleted(bool $deleted): self;

    public function isDeleted(): bool;
}
