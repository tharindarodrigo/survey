<?php

namespace Domain\Surveys\Jobs;

use Domain\Surveys\Actions\CreateSurveySummaryAction;
use Domain\Surveys\Models\Survey;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessSurveySummary implements ShouldQueue
{
    use Batchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Survey $survey
    ) {
        $this->onQueue('survey-summaries');
    }

    /**
     * Execute the job.
     */
    public function handle(CreateSurveySummaryAction $action): void
    {
        try {
            Log::info("Starting survey summary processing for survey ID: {$this->survey->id}");

            $summary = $action->execute($this->survey);

            Log::info("Successfully created summary for survey ID: {$this->survey->id}", [
                'summary_id' => $summary->id,
                'sentiment' => $summary->sentiment->value,
                'topics_count' => count($summary->topics_json),
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to process survey summary for survey ID: {$this->survey->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Survey summary job failed permanently for survey ID: {$this->survey->id}", [
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }
}
