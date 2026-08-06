<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepairProgressPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'repair_progress_update_id',
        'file_path',
        'thumbnail_path',
    ];

    public function progressUpdate()
    {
        return $this->belongsTo(RepairProgressUpdate::class, 'repair_progress_update_id');
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
