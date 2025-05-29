<?php

namespace Domain\Surveys\Listeners;

use Domain\Shared\Models\User;
use Domain\Surveys\Events\SurveySummaryCreated;
use Domain\Surveys\Notifications\SurveySummariesNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyUsersAboutTheNewSurveySummaries implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(SurveySummaryCreated $event): void
    {
        $surveySummary = $event->surveySummary;
        $survey = $surveySummary->survey;

        // Get users who should be notified about survey summaries
        // For now, we'll notify all users
        $usersToNotify = $this->getUsersToNotify($survey);

        foreach ($usersToNotify as $user) {
            $user->notify(new SurveySummariesNotification($surveySummary));
        }
    }

    private function getUsersToNotify($survey)
    {
        // This should be replaced with filtered logic to get users based on the application's requirements.
        return User::all();
    }
}
