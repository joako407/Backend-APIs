<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserRole extends Model
{
    use HasFactory;

    public const SUPERUSER = 1;
    public const ADMIN = 2;
    public const MANAGER = 3;
}
