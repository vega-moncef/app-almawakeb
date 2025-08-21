<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestSubject extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_id',
        'subject_id',
        'marks',
        'order'
    ];

    protected $casts = [
        'marks' => 'decimal:2',
        'order' => 'integer'
    ];

    // Relationships
    public function test()
    {
        return $this->belongsTo(Test::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
