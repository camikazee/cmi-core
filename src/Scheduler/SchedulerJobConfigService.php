<?php
declare(strict_types=1);

namespace Core\Scheduler;

use Core\Entity\SchedulerJobConfig;
use Doctrine\ORM\EntityManagerInterface;

final class SchedulerJobConfigService
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    /**
     * @return array{enabled:bool,everyMinutes:int,options:array<string,mixed>,source:string}
     */
    public function resolve(string $jobName, int $defaultEveryMinutes): array
    {
        $defaultEveryMinutes = max(1, $defaultEveryMinutes);
        /** @var SchedulerJobConfig|null $config */
        $config = $this->em->getRepository(SchedulerJobConfig::class)->findOneBy(['jobName' => $jobName]);
        if (!$config instanceof SchedulerJobConfig) {
            return [
                'enabled' => true,
                'everyMinutes' => $defaultEveryMinutes,
                'options' => [],
                'source' => 'default',
            ];
        }

        $every = $config->getEveryMinutes();
        return [
            'enabled' => $config->isEnabled(),
            'everyMinutes' => $every !== null ? max(1, $every) : $defaultEveryMinutes,
            'options' => $config->getOptions(),
            'source' => 'database',
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function options(string $jobName): array
    {
        /** @var SchedulerJobConfig|null $config */
        $config = $this->em->getRepository(SchedulerJobConfig::class)->findOneBy(['jobName' => $jobName]);
        if (!$config instanceof SchedulerJobConfig) {
            return [];
        }
        return $config->getOptions();
    }

    public function upsert(string $jobName, ?bool $enabled, ?int $everyMinutes, ?array $options): SchedulerJobConfig
    {
        /** @var SchedulerJobConfig|null $config */
        $config = $this->em->getRepository(SchedulerJobConfig::class)->findOneBy(['jobName' => $jobName]);
        if (!$config instanceof SchedulerJobConfig) {
            $config = (new SchedulerJobConfig())->setJobName($jobName);
            $this->em->persist($config);
        }
        if ($enabled !== null) {
            $config->setIsEnabled($enabled);
        }
        if ($everyMinutes !== null) {
            $config->setEveryMinutes(max(1, $everyMinutes));
        }
        if ($options !== null) {
            $config->setOptions($options);
        }
        $this->em->flush();
        return $config;
    }
}
