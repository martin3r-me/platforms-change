<?php

namespace Platform\Change\Livewire\ChangeProject;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Platform\Change\Enums\ChangeActionStatus;
use Platform\Change\Enums\ChangePhaseStatus;
use Platform\Change\Models\ChangeAction;
use Platform\Change\Models\ChangePhase;
use Platform\Change\Models\ChangeProject;

class Board extends Component
{
    public ChangeProject $project;

    // Phase inline editing
    public ?int $editingPhaseId = null;
    public array $phaseForm = [
        'status' => '',
        'responsible' => '',
        'notes' => '',
        'evidence' => '',
    ];

    // Action CRUD
    public bool $actionModalShow = false;
    public ?int $editingActionId = null;
    public array $actionForm = [
        'title' => '',
        'description' => '',
        'status' => 'open',
        'due_date' => null,
        'responsible' => '',
        'phase_id' => '',
    ];

    // Filters
    public string $actionStatusFilter = '';

    public function mount(ChangeProject $project): void
    {
        $this->project = $project;
    }

    #[Computed]
    public function phases()
    {
        return $this->project->phases()
            ->with('actions')
            ->withCount('actions')
            ->orderBy('phase_number')
            ->get();
    }

    #[Computed]
    public function actions()
    {
        $q = $this->project->actions()->with('phase');
        if ($this->actionStatusFilter !== '') $q->where('status', $this->actionStatusFilter);
        return $q->orderByDesc('created_at')->get();
    }

    #[Computed]
    public function openActionsCount(): int
    {
        return $this->project->actions()
            ->whereNotIn('status', ['done', 'cancelled'])
            ->count();
    }

    // ── Phase inline update ─────────────────────────────────────

    public function editPhase(int $id): void
    {
        $phase = $this->project->phases()->find($id);
        if (! $phase) return;

        $this->editingPhaseId = $phase->id;
        $this->phaseForm = [
            'status'      => $phase->status?->value ?? 'not_started',
            'responsible' => $phase->responsible ?? '',
            'notes'       => $phase->notes ?? '',
            'evidence'    => $phase->evidence ?? '',
        ];
    }

    public function updatePhase(): void
    {
        $phase = $this->project->phases()->find($this->editingPhaseId);
        if (! $phase) return;

        $update = [
            'status'      => $this->phaseForm['status'],
            'responsible' => $this->phaseForm['responsible'] !== '' ? $this->phaseForm['responsible'] : null,
            'notes'       => $this->phaseForm['notes'] !== '' ? $this->phaseForm['notes'] : null,
            'evidence'    => $this->phaseForm['evidence'] !== '' ? $this->phaseForm['evidence'] : null,
        ];

        if ($this->phaseForm['status'] === 'in_progress' && ! $phase->started_at) {
            $update['started_at'] = now();
        }
        if ($this->phaseForm['status'] === 'completed' && ! $phase->completed_at) {
            $update['completed_at'] = now();
        }
        if ($this->phaseForm['status'] !== 'completed') {
            $update['completed_at'] = null;
        }

        $phase->update($update);
        $this->editingPhaseId = null;
        unset($this->phases);
        $this->dispatch('toast', message: 'Phase aktualisiert');
    }

    public function cancelPhaseEdit(): void
    {
        $this->editingPhaseId = null;
    }

    public function quickUpdatePhaseStatus(int $phaseId, string $status): void
    {
        $phase = $this->project->phases()->find($phaseId);
        if (! $phase) return;

        $update = ['status' => $status];
        if ($status === 'in_progress' && ! $phase->started_at) $update['started_at'] = now();
        if ($status === 'completed' && ! $phase->completed_at) $update['completed_at'] = now();
        if ($status !== 'completed') $update['completed_at'] = null;

        $phase->update($update);
        unset($this->phases);
        $this->dispatch('toast', message: 'Phase-Status aktualisiert');
    }

    // ── Action CRUD ─────────────────────────────────────────────

    public function createAction(?int $phaseId = null): void
    {
        $this->resetValidation();
        $this->editingActionId = null;
        $this->actionForm = [
            'title' => '', 'description' => '', 'status' => 'open',
            'due_date' => null, 'responsible' => '',
            'phase_id' => $phaseId ? (string) $phaseId : '',
        ];
        $this->actionModalShow = true;
    }

    public function editAction(int $id): void
    {
        $action = $this->project->actions()->find($id);
        if (! $action) return;

        $this->resetValidation();
        $this->editingActionId = $action->id;
        $this->actionForm = [
            'title'       => $action->title,
            'description' => $action->description ?? '',
            'status'      => $action->status?->value ?? 'open',
            'due_date'    => $action->due_date?->format('Y-m-d'),
            'responsible' => $action->responsible ?? '',
            'phase_id'    => (string) ($action->change_phase_id ?? ''),
        ];
        $this->actionModalShow = true;
    }

    public function storeAction(): void
    {
        $this->validate([
            'actionForm.title'       => 'required|string|max:255',
            'actionForm.description' => 'nullable|string',
            'actionForm.status'      => 'required|in:' . implode(',', ChangeActionStatus::values()),
            'actionForm.due_date'    => 'nullable|date',
            'actionForm.responsible' => 'nullable|string|max:255',
            'actionForm.phase_id'    => 'nullable|integer|exists:change_phases,id',
        ]);

        $payload = [
            'title'          => $this->actionForm['title'],
            'description'    => $this->actionForm['description'] !== '' ? $this->actionForm['description'] : null,
            'status'         => $this->actionForm['status'],
            'due_date'       => $this->actionForm['due_date'] ?: null,
            'responsible'    => $this->actionForm['responsible'] !== '' ? $this->actionForm['responsible'] : null,
            'change_phase_id' => $this->actionForm['phase_id'] !== '' ? (int) $this->actionForm['phase_id'] : null,
        ];

        if ($this->actionForm['status'] === 'done') {
            $payload['completed_at'] = now();
        }

        if ($this->editingActionId) {
            $action = $this->project->actions()->find($this->editingActionId);
            if ($action) {
                if ($this->actionForm['status'] === 'done' && ! $action->completed_at) {
                    $payload['completed_at'] = now();
                } elseif ($this->actionForm['status'] !== 'done') {
                    $payload['completed_at'] = null;
                }
                $action->update($payload);
            }
            $this->dispatch('toast', message: 'Massnahme aktualisiert');
        } else {
            $this->project->actions()->create(array_merge($payload, [
                'team_id' => Auth::user()->currentTeamRelation?->getRootTeam()?->id,
                'user_id' => Auth::id(),
            ]));
            $this->dispatch('toast', message: 'Massnahme erstellt');
        }

        $this->actionModalShow = false;
        unset($this->actions, $this->phases);
    }

    public function deleteAction(int $id): void
    {
        $this->project->actions()->where('id', $id)->delete();
        unset($this->actions, $this->phases);
        $this->dispatch('toast', message: 'Massnahme gelöscht');
    }

    public function quickUpdateActionStatus(int $actionId, string $status): void
    {
        $action = $this->project->actions()->find($actionId);
        if (! $action) return;

        $update = ['status' => $status];
        if ($status === 'done' && ! $action->completed_at) $update['completed_at'] = now();
        if ($status !== 'done') $update['completed_at'] = null;

        $action->update($update);
        unset($this->actions, $this->phases);
    }

    public function render()
    {
        return view('change::livewire.change-project.board')
            ->layout('platform::layouts.app');
    }
}
