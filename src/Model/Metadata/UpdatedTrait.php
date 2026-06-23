<?php

declare(strict_types=1);

namespace Core\Model\Metadata;

use DateTimeImmutable;
use ApiPlatform\Metadata\ApiProperty;
use Doctrine\ORM\Mapping as ORM;

/**
 * Trait UpdatedTrait.
 */
trait UpdatedTrait
{
    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable', nullable: true)]
    #[ApiProperty(writable: false)]
    private ?DateTimeImmutable $updatedAt = null;

    #[ORM\Column(name: 'updated_by', type: 'string', length: 128, nullable: true)]
    #[ApiProperty(writable: false)]
    private ?string $updatedBy = null;

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @return $this
     */
    public function setUpdatedAt(?DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getUpdatedBy(): ?string
    {
        return $this->updatedBy;
    }

    /**
     * @return $this
     */
    public function setUpdatedBy(?string $updatedBy): self
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }
}
