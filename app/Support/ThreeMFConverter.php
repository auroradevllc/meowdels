<?php

namespace App\Support;

class ThreeMFConverter
{
    public function convert(string $input, string $output): bool
    {
        $p = \Process::run([ '3mf', 'convert', $input, $output ]);

        if (! $p->successful()) {
            return false;
        }

        return true;
    }
}
