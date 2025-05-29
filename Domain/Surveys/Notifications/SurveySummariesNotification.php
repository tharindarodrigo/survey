<?php

namespace Domain\Surveys\Notifications;

use Domain\Surveys\Models\Survey;
use Domain\Surveys\Models\SurveySummary;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SurveySummariesNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public SurveySummary $surveySummary;

    /**
     * Create a new notification instance.
     */
    public function __construct(SurveySummary $surveySummary)
    {
        $this->surveySummary = $surveySummary;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        /** @var Survey $survey */
        $survey = $this->surveySummary->survey;
        $sentiment = $this->surveySummary->sentiment->value;
        $topics = $this->surveySummary->topics_json;

        $sentimentDisplay = ucfirst($sentiment);

        $topicsText = is_array($topics) ? implode(', ', $topics) : 'No topics identified';

        return (new MailMessage)
            ->subject("New Survey Summary Available: {$survey->title}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("A new survey summary has been generated for **{$survey->title}**.")
            ->line('Here are the key insights:')
            ->line("**Overall Sentiment:** {$sentimentDisplay}")
            ->line("**Key Topics:** {$topicsText}")
            ->line('**Summary:**')
            ->line($this->surveySummary->summary_text)
            ->action('View Full Survey Details', url("/surveys/{$survey->id}"))
            ->line('This summary was automatically generated based on the survey responses received.')
            ->line('Thank you for using our survey platform!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'survey_id' => $this->surveySummary->survey_id,
            'survey_title' => $this->surveySummary->survey->title ?? 'Unknown Survey',
            'sentiment' => $this->surveySummary->sentiment->value,
            'summary_created_at' => $this->surveySummary->created_at,
        ];
    }
}
