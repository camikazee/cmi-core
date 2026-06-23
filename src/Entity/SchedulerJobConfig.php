<?php
declare(strict_types=1);

namespace Core\Entity;

use Doctrine\ORM\Mapping as ORM;
use Core\Model\Metadata\CreatedTrait;
use Core\Model\Metadata\UpdatedTrait;
use Core\Model\Metadata\CreateInterface;
use Core\Model\Metadata\UpdateInterface;

#[ORM\Entity]
#[ORM\Table(name: 'scheduler_job_configs')]
#[ORM\UniqueConstraint(name: 'uniq_scheduler_job_name', columns: ['job_name'])]
class SchedulerJobConfig implements CreateInterface, UpdateInterface
{
    use CreatedTrait;
    use UpdatedTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator('doctrine.uuid_generator')]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(name: 'job_name', type: 'string', length: 120)]
    private string $jobName = '';

    #[ORM\Column(name: 'is_enabled', type: 'boolean', options: ['default' => true])]
    private bool $isEnabled = true;

    #[ORM\Column(name: 'every_minutes', type: 'integer', nullable: true)]
    private ?int $everyMinutes = null;

    /** @var array<string,mixed> */
    #[ORM\Column(type: 'json', options: ['default' => '{}'])]
    private array $options = [];

    public function getId(): string
    {
        return $this->id;
    }

    public function getJobName(): string
    {
        return $this->jobName;
    }

    public function setJobName(string $jobName): self
    {
        $this->jobName = trim($jobName);
        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function setIsEnabled(bool $isEnabled): self
    {
        $this->isEnabled = $isEnabled;
        return $this;
    }

    public function getEveryMinutes(): ?int
    {
        return $this->everyMinutes;
    }

    public function setEveryMinutes(?int $everyMinutes): self
    {
        $this->everyMinutes = $everyMinutes;
        return $this;
    }

    /**
     * @return array<string,mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param array<string,mixed> $options
     */
    public function setOptions(array $options): self
    {
        $this->options = $options;
        return $this;
    }
}
