<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationsLog extends Model
{
    use HasFactory;

    protected $table = 'notifications_log';

    protected $fillable = [
        'type',
        'recipient',
        'subject',
        'message',
        'status',
        'triggered_by',
        'reference_type',
        'reference_id',
    ];
}
