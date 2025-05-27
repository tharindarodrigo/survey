<?php

namespace Domain\Surveys\Enums;

enum SurveyStatus: string
{
    case ACTIVE = 'active';
    case COMPLETED = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::COMPLETED => 'Completed',
        };
    }
}
