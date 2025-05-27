<?php

namespace Domain\Surveys\Models;

use Database\Factories\SurveyFactory;
use Domain\Companies\Models\Company;
use Domain\Surveys\Enums\SurveyStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Survey extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'title',
        'description',
        'status',
    ];

    protected $casts = [
        'status' => SurveyStatus::class,
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class);
    }

    public function summary(): HasOne
    {
        return $this->hasOne(SurveySummary::class);
    }

    public static function newFactory(): SurveyFactory
    {
        return new SurveyFactory;
    }
}
