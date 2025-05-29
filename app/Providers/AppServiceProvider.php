<?php

namespace App\Providers;

use Domain\Surveys\Events\SurveySummaryCreated;
use Domain\Surveys\Listeners\NotifyUsersAboutTheNewSurveySummaries;
use Domain\Surveys\Models\Survey;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
        Gate::policy(Survey::class, \Domain\Surveys\Policies\SurveyPolicy::class);

        Event::listen(
            SurveySummaryCreated::class,
            NotifyUsersAboutTheNewSurveySummaries::class
        );
    }
}
