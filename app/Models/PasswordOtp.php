<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordOtp extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected $primaryKey = 'email';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'email',
        'otp',
        'expired_at',
    ];
}
