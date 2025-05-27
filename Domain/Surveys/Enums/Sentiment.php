<?php

namespace Domain\Surveys\Enums;

enum Sentiment: string
{
    case POSITIVE = 'positive';
    case NEGATIVE = 'negative';
    case NEUTRAL = 'neutral';

    public function label(): string
    {
        return match ($this) {
            self::POSITIVE => 'Positive',
            self::NEGATIVE => 'Negative',
            self::NEUTRAL => 'Neutral',
        };
    }
}
