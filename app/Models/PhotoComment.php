<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhotoComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_order_id',
        'photo_type',
        'photo_id',
        'parent_id',
        'user_id',
        'author_name',
        'author_type',
        'comment',
    ];

    public function jobOrder()
    {
        return $this->belongsTo(JobOrder::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function photo()
    {
        return $this->morphTo();
    }

    public function parent()
    {
        return $this->belongsTo(PhotoComment::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(PhotoComment::class, 'parent_id')->orderBy('created_at', 'asc');
    }
}
