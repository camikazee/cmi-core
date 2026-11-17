<?php

declare(strict_types=1);

namespace Core\Deployment;

interface DeploymentCheckProviderInterface
{
    /** @return iterable<string,\Closure():array{status:string,detail:string,data?:array<string,mixed>}> */
    public function checks(): iterable;
}
