<?php

declare(strict_types=1);

namespace Core\Scheduler;

/**
 * Optional hook called after every executed job (for example to record health state for alerting).
 *
 * Services implementing this interface are tagged `core.scheduler.run_listener` automatically.
 * A failing listener is logged and never stops the remaining jobs.
 */
interface SchedulerRunListenerInterface
{
    public function onJobRun(SchedulerJobRun $run): void;
}
