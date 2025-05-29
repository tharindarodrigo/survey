<?php

namespace App\Console\Commands;

use Domain\Surveys\Enums\SurveyStatus;
use Domain\Surveys\Jobs\ProcessSurveySummary;
use Domain\Surveys\Models\Survey;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class ProcessSurveySummaries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'surveys:process-summaries 
                            {--survey-id= : Process a specific survey by ID}
                            {--force : Force re-processing of existing summaries}
                            {--batch-size=10 : Number of surveys to process in each batch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process completed surveys and generate AI summaries using OpenAI';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting survey summary processing...');

        try {
            $surveys = $this->getSurveysToProcess();

            if ($surveys->isEmpty()) {
                $this->info('No surveys found that need summary processing.');

                return self::SUCCESS;
            }

            $this->info("Found {$surveys->count()} survey(s) to process.");

            // Create jobs for batch processing
            $jobs = $surveys->map(function (Survey $survey) {
                return new ProcessSurveySummary($survey);
            });

            // Process surveys in batches
            $batchSize = (int) $this->option('batch-size');
            $batches = $jobs->chunk($batchSize);

            $totalBatches = $batches->count();
            $this->info("Processing surveys in {$totalBatches} batch(es) of {$batchSize} surveys each.");

            $batchNumber = 1;
            foreach ($batches as $batchJobs) {
                $this->info("Dispatching batch {$batchNumber}/{$totalBatches}...");

                /** @var \Illuminate\Bus\Batch $batchJobs */
                $batch = Bus::batch($batchJobs->toArray())
                    ->name("Survey Summaries Batch {$batchNumber}")
                    ->allowFailures()
                    ->dispatch();

                $batchNumber++;
            }

            $this->info('All survey summary jobs have been dispatched to the queue.');
            $this->info('Monitor the queue workers to see processing progress.');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to process survey summaries: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    /**
     * Get surveys that need summary processing
     */
    private function getSurveysToProcess()
    {
        $query = Survey::query()
            ->where('status', SurveyStatus::COMPLETED)
            ->whereHas('responses'); // Only surveys with responses

        // If specific survey ID is provided
        if ($surveyId = $this->option('survey-id')) {
            $query->where('id', $surveyId);
        }

        // If not forcing re-processing, exclude surveys that already have summaries
        if (! $this->option('force')) {
            $query->whereDoesntHave('summary');
        }

        return $query->with(['responses', 'summary'])->get();
    }
}
