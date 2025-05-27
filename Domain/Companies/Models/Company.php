<?php

namespace Domain\Companies\Models;

use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    public static function newFactory(): CompanyFactory
    {
        return new CompanyFactory();
    }
}
