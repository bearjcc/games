<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserGameScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'game_slug',
        'best_score',
    ];
}


