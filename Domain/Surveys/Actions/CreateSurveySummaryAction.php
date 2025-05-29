<?php

namespace Domain\Surveys\Actions;

use Domain\Surveys\Enums\Sentiment;
use Domain\Surveys\Events\SurveySummaryCreated;
use Domain\Surveys\Models\Survey;
use Domain\Surveys\Models\SurveySummary;
use OpenAI\Laravel\Facades\OpenAI;

class CreateSurveySummaryAction
{
    public function execute(Survey $survey): SurveySummary
    {
        // Get all responses for the survey
        $responses = $survey->responses()->get();

        if ($responses->isEmpty()) {
            throw new \Exception("Survey {$survey->id} has no responses to summarize.");
        }

        // Prepare the prompt for OpenAI
        $prompt = $this->buildPrompt($survey, $responses);

        // Call OpenAI API
        $result = OpenAI::chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an expert survey analyst. Analyze the survey responses and provide insights in the exact JSON format requested.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'response_format' => [
                'type' => 'json_object',
            ],
            'temperature' => 0.3,
            'max_tokens' => 2000,
        ]);

        // Parse the response
        $analysis = json_decode($result->choices[0]->message->content, true);

        if (! $analysis || ! isset($analysis['summary']) || ! isset($analysis['sentiment']) || ! isset($analysis['topics'])) {
            throw new \Exception('Invalid response format from OpenAI API.');
        }

        // Validate and normalize sentiment
        $sentiment = $this->normalizeSentiment($analysis['sentiment']);

        // Create or update the survey summary
        $summarySummary = SurveySummary::updateOrCreate(
            ['survey_id' => $survey->id],
            [
                'summary_text' => $analysis['summary'],
                'sentiment' => $sentiment,
                'topics_json' => $analysis['topics'],
            ]
        );

        // Fire the event after creating the summary
        SurveySummaryCreated::dispatch($summarySummary);

        return $summarySummary;
    }

    private function buildPrompt(Survey $survey, $responses): string
    {
        $responseTexts = $responses->pluck('response_text')->toArray();
        $responseCount = $responses->count();

        $prompt = "Please analyze the following survey responses and provide insights in JSON format.\n\n";
        $prompt .= "Survey Title: {$survey->title}\n";
        $prompt .= "Survey Description: {$survey->description}\n";
        $prompt .= "Number of Responses: {$responseCount}\n\n";
        $prompt .= "Responses:\n";

        foreach ($responseTexts as $index => $responseText) {
            $prompt .= ($index + 1) . '. ' . $responseText . "\n";
        }

        $prompt .= "\nPlease provide your analysis in the following JSON format:\n";
        $prompt .= "{\n";
        $prompt .= '  "summary": "A comprehensive summary of the survey responses highlighting key insights, trends, and patterns (200-400 words)",' . "\n";
        $prompt .= '  "sentiment": "positive|negative|neutral - The overall sentiment of the responses",' . "\n";
        $prompt .= '  "topics": ["topic1", "topic2", "topic3"] - Array of 3-8 key topics/themes identified in the responses' . "\n";
        $prompt .= "}\n\n";
        $prompt .= "Focus on:\n";
        $prompt .= "- Common themes and patterns\n";
        $prompt .= "- Areas of satisfaction or concern\n";
        $prompt .= "- Actionable insights\n";
        $prompt .= "- Frequency of mentioned topics\n";
        $prompt .= "- Overall tone and sentiment\n";

        return $prompt;
    }

    private function normalizeSentiment(string $sentiment): Sentiment
    {
        $sentiment = strtolower(trim($sentiment));

        return match ($sentiment) {
            'positive' => Sentiment::POSITIVE,
            'negative' => Sentiment::NEGATIVE,
            'neutral' => Sentiment::NEUTRAL,
            default => Sentiment::NEUTRAL,
        };
    }
}
