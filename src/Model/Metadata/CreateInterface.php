<?php

declare(strict_types=1);

namespace Core\Model\Metadata;

/**
 * Interface CreateInterface.
 */
interface CreateInterface
{
    /**
     * @return $this
     */
    public function setCreatedAt(?\DateTimeImmutable $createdAt): self;

    /**
     * @return $this
     */
    public function setCreatedBy(?string $createdBy): self;
}
