<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistrarStudent extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id_number',
        'campus_id',
        'student_name',
        'course',
        'year_level',
        'campus',
        'enrollment_status',
        'academic_year',
        'semester',
    ];
}
