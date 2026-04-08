<?php

declare(strict_types=1);

namespace Sammyjo20\LaravelHaystack\Data;

use Sammyjo20\LaravelHaystack\Models\HaystackBale;
use Sammyjo20\LaravelHaystack\Contracts\StackableJob;

class NextJob
{
    /**
     * Constructor
     */
    public function __construct(
        public readonly StackableJob $job,
        public readonly HaystackBale $haystackRow,
    ) {
        //
    }
}
