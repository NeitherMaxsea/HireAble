<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicantProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'sex_at_birth',
        'birth_date',
        'academic_level',
        'address_province',
        'address_city',
        'mobile_number',
        'pwd_category',
        'pwd_id_number',
        'preferred_role',
        'preferred_skills',
        'years_experience',
        'preferred_province',
        'preferred_city',
        'work_mode',
        'salary_min',
        'salary_max',
        'profile_summary',
        'profile_picture_url',
        'profile_picture_path',
        'pwd_id_image_url',
        'pwd_id_image_path',
        'raw_payload',
    ];

    protected $casts = [
        'preferred_skills' => 'array',
        'raw_payload' => 'array',
        'birth_date' => 'date',
    ];
}

