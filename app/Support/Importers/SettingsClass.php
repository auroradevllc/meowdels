<?php

namespace App\Support\Importers;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class SettingsClass
{
    public string $className;

    public function __construct(string $className)
    {
        $this->className = $className;
    }
}
