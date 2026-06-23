<?php

declare(strict_types=1);

namespace Core\Model\Metadata;

use DateTimeImmutable;
use ApiPlatform\Metadata\ApiProperty;
use Doctrine\ORM\Mapping as ORM;

/**
 * Trait CreatedTrait.
 */
trait CreatedTrait
{
    #[ORM\Column(name: 'created_at', type: 'datetime_immutable', nullable: true)]
    #[ApiProperty(writable: false)]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(name: 'created_by', type: 'string', length: 128, nullable: true)]
    #[ApiProperty(writable: false)]
    private ?string $createdBy = null;

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return $this
     */
    public function setCreatedAt(?DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCreatedBy(): ?string
    {
        return $this->createdBy;
    }

    /**
     * @return $this
     */
    public function setCreatedBy(?string $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }
}
