<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StripeReturnMessage extends Model
{
    protected $table = 'stripe_return_messages';

    protected $fillable = [
        'tipo',
        'entity_id',
        'user_id',
        'session_id',
        'payment_intent_id',
        'status',
        'message',
        'seen',
    ];

    protected $casts = [
        'seen' => 'boolean',
    ];
}
