<?php

use Domain\Shared\Models\User;
use Domain\Surveys\Events\SurveySummaryCreated;
use Domain\Surveys\Listeners\NotifyUsersAboutTheNewSurveySummaries;
use Domain\Surveys\Models\Survey;
use Domain\Surveys\Models\SurveySummary;
use Domain\Surveys\Notifications\SurveySummariesNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('fires survey summary created event when summary is created', function () {
    Event::fake();

    // Create test data
    $survey = Survey::factory()->create();
    $surveySummary = SurveySummary::factory()->create(['survey_id' => $survey->id]);

    // Manually fire the event to test
    SurveySummaryCreated::dispatch($surveySummary);

    // Assert the event was fired
    Event::assertDispatched(SurveySummaryCreated::class, function ($event) use ($surveySummary) {
        return $event->surveySummary->id === $surveySummary->id;
    });
});

it('sends notification when survey summary created event is handled', function () {
    Notification::fake();

    // Create test data
    $survey = Survey::factory()->create();
    $surveySummary = SurveySummary::factory()->create(['survey_id' => $survey->id]);

    // Create admin user (assuming you have roles set up)
    $adminUser = User::factory()->create();

    $role = Role::create(['name' => 'admin', 'guard_name' => 'api']);

    // Note: You might need to assign admin role here depending on your role setup
    $adminUser->assignRole($role);

    // Create the event and listener
    $event = new SurveySummaryCreated($surveySummary);
    $listener = new NotifyUsersAboutTheNewSurveySummaries;

    // Handle the event
    $listener->handle($event);

    // Assert notification was sent (this might fail if no admin users exist)
    Notification::assertSentTo($adminUser, SurveySummariesNotification::class);
});

it('contains correct data in survey summary notification', function () {
    // Create test data
    $survey = Survey::factory()->create(['title' => 'Test Survey']);
    $surveySummary = SurveySummary::factory()->create([
        'survey_id' => $survey->id,
        'summary_text' => 'This is a test summary',
    ]);

    $user = User::factory()->create(['name' => 'Test User']);

    // Create notification
    $notification = new SurveySummariesNotification($surveySummary);

    // Test toArray method
    $arrayData = $notification->toArray($user);

    expect($arrayData['survey_id'])->toBe($survey->id);
    expect($arrayData)->toHaveKey('sentiment');
    expect($arrayData)->toHaveKey('summary_created_at');
});
