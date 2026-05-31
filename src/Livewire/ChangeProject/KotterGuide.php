<?php

namespace Platform\Change\Livewire\ChangeProject;

use Livewire\Component;
use Platform\Change\Enums\ChangePhaseNumber;

class KotterGuide extends Component
{
    public function render()
    {
        return view('change::livewire.change-project.kotter-guide', [
            'phases' => ChangePhaseNumber::cases(),
        ])->layout('platform::layouts.app');
    }
}
