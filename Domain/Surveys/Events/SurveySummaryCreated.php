<?php

namespace Domain\Surveys\Events;

use Domain\Surveys\Models\SurveySummary;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SurveySummaryCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public SurveySummary $surveySummary
    ) {}
}
