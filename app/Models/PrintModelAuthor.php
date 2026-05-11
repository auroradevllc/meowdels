<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'name',
])]
class PrintModelAuthor extends Model
{
    public function model(): BelongsTo {
        return $this->belongsTo(PrintModel::class);
    }
}
