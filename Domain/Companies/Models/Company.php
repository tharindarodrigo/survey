<?php

namespace Domain\Companies\Models;

use Database\Factories\CompanyFactory;
use Domain\Surveys\Models\Survey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function surveys(): HasMany
    {
        return $this->hasMany(Survey::class);
    }

    public static function newFactory(): CompanyFactory
    {
        return new CompanyFactory;
    }
}
