<?php

declare(strict_types=1);

namespace Core\Model\Metadata;

/**
 * Interface UpdateInterface.
 */
interface UpdateInterface
{
    /**
     * @return $this
     */
    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): self;

    /**
     * @return $this
     */
    public function setUpdatedBy(?string $updatedBy): self;
}
