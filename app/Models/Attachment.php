<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'attachable_type',
        'attachable_id',
        'type',
        'file_path',
        'file_name',
        'file_size',
    ];

    public function attachable()
    {
        return $this->morphTo();
    }

    public function comments()
    {
        return $this->morphMany(PhotoComment::class, 'photo')->whereNull('parent_id')->orderBy('created_at', 'asc');
    }

    public function allComments()
    {
        return $this->morphMany(PhotoComment::class, 'photo');
    }
}
