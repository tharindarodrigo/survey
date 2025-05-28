<?php

namespace Domain\Surveys\Models;

use Database\Factories\SurveySummaryFactory;
use Domain\Surveys\Enums\Sentiment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveySummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'survey_id',
        'summary_text',
        'sentiment',
        'topics_json',
    ];

    protected $casts = [
        'topics_json' => 'array',
        'sentiment' => Sentiment::class,
    ];

    // Accessor methods for convenience
    public function getSummaryAttribute(): ?string
    {
        return $this->summary_text;
    }

    public function getTopicsAttribute(): ?array
    {
        return $this->topics_json;
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public static function newFactory(): SurveySummaryFactory
    {
        return new SurveySummaryFactory;
    }
}
