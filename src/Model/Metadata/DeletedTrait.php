<?php

declare(strict_types=1);

namespace Core\Model\Metadata;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * Trait DeletedTrait.
 */
trait DeletedTrait
{
    #[ORM\Column(name: 'deleted', type: 'boolean', nullable: false, options: ['default' => 0])]
    private bool $deleted = false;

    #[ORM\Column(name: 'deleted_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $deletedAt = null;

    /**
     * @return $this
     */
    public function setDeletedAt(?DateTimeImmutable $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    public function getDeletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    /**
     * @return $this
     */
    public function setDeleted(bool $deleted): self
    {
        $this->deleted = $deleted;

        return $this;
    }
}
