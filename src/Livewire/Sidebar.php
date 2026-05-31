<?php

namespace Platform\Change\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Change\Models\ChangeProject;

class Sidebar extends Component
{
    public function render()
    {
        $user = Auth::user();
        $baseTeam = $user?->currentTeamRelation;
        $teamId = $baseTeam ? $baseTeam->getRootTeam()->id : null;

        $recentProjects = $teamId
            ? ChangeProject::where('team_id', $teamId)
                ->orderBy('updated_at', 'desc')
                ->take(5)
                ->get()
            : collect();

        return view('change::livewire.sidebar', [
            'recentProjects' => $recentProjects,
        ]);
    }
}
