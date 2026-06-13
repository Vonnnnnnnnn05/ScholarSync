<?php

namespace App\Models;

use Database\Factories\ScholarshipProgramFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScholarshipProgram extends Model
{
    /** @use HasFactory<ScholarshipProgramFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'fund_source',
        'agency_name',
        'status',
    ];
}
