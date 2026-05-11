<?php

use Livewire\Component;

new class extends Component
{
    public string $group;

    public function mount(string $group)
    {
        $this->group = $group;
    }
};
?>
<div>
    <livewire:settings :group="$group"/>
</div>

