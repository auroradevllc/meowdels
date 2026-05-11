<?php

namespace App\Support\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Description
{
    public string $description;

    public function __construct(string $description)
    {
        $this->description = $description;
    }
}
