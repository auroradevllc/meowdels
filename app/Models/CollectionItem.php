<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable('collection_id', 'collectable_id', 'collectable_type')]
class CollectionItem extends Model
{

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    public function collectable(): MorphTo
    {
        return $this->morphTo();
    }
}
