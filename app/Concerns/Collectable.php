<?php

namespace App\Concerns;

use App\Models\Collection;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait Collectable
{
    public function collections(): MorphToMany
    {
        return $this->morphToMany(
            Collection::class,      // The final model you want
            'collectable',          // The prefix used in your pivot table (collectable_id/type)
            'collection_items',     // The name of your pivot table
            'collectable_id',       // The foreign key on the pivot table
            'collection_id'         // The associated key on the pivot table
        );
    }
}
