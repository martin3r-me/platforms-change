<?php

namespace Platform\Change\Livewire\ChangeProject;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Platform\Change\Enums\ChangeProjectStatus;
use Platform\Change\Models\ChangeProject;
use Platform\Organization\Models\OrganizationEntity;

class Index extends Component
{
    public string $search = '';
    public string $statusFilter = '';

    public bool $modalShow = false;
    public ?int $editingId = null;

    public array $form = [
        'name' => '',
        'code' => '',
        'description' => '',
        'status' => 'draft',
        'target_date' => null,
        'owner_entity_id' => '',
        'urgency_statement' => '',
        'vision_statement' => '',
    ];

    protected $queryString = [
        'search'       => ['except' => ''],
        'statusFilter' => ['except' => ''],
    ];

    public function updatedSearch(): void { unset($this->projects); }
    public function updatedStatusFilter(): void { unset($this->projects); }

    protected function rules(): array
    {
        return [
            'form.name'              => ['required', 'string', 'max:255'],
            'form.code'              => ['nullable', 'string', 'max:100'],
            'form.description'       => ['nullable', 'string'],
            'form.status'            => ['required', 'in:' . implode(',', ChangeProjectStatus::values())],
            'form.target_date'       => ['nullable', 'date'],
            'form.owner_entity_id'   => ['nullable', 'integer', 'exists:organization_entities,id'],
            'form.urgency_statement' => ['nullable', 'string'],
            'form.vision_statement'  => ['nullable', 'string'],
        ];
    }

    #[Computed]
    public function projects()
    {
        $q = ChangeProject::query()
            ->withCount(['phases as completed_phases_count' => fn ($pq) => $pq->where('status', 'completed')])
            ->withCount('phases')
            ->withCount('actions')
            ->where('team_id', Auth::user()->currentTeam->id);

        if ($this->search !== '') {
            $term = '%'.$this->search.'%';
            $q->where(function ($qq) use ($term) {
                $qq->where('name', 'like', $term)
                    ->orWhere('code', 'like', $term)
                    ->orWhere('description', 'like', $term);
            });
        }

        if ($this->statusFilter !== '') {
            $q->where('status', $this->statusFilter);
        }

        return $q->with(['ownerEntity', 'phases'])
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function availableEntities()
    {
        return OrganizationEntity::where('team_id', Auth::user()->currentTeam->id)
            ->orderBy('name')
            ->get();
    }

    public function create(): void
    {
        $this->resetValidation();
        $this->reset('form');
        $this->form['status'] = 'draft';
        $this->editingId = null;
        $this->modalShow = true;
    }

    public function edit(int $id): void
    {
        $project = ChangeProject::where('team_id', Auth::user()->currentTeam->id)->find($id);
        if (! $project) return;

        $this->resetValidation();
        $this->editingId = $project->id;
        $this->form = [
            'name'              => (string) $project->name,
            'code'              => (string) ($project->code ?? ''),
            'description'       => (string) ($project->description ?? ''),
            'status'            => $project->status?->value ?? 'draft',
            'target_date'       => $project->target_date?->format('Y-m-d'),
            'owner_entity_id'   => (string) ($project->owner_entity_id ?? ''),
            'urgency_statement' => (string) ($project->urgency_statement ?? ''),
            'vision_statement'  => (string) ($project->vision_statement ?? ''),
        ];
        $this->modalShow = true;
    }

    public function store(): void
    {
        $data = $this->validate()['form'];

        $payload = [
            'name'              => trim($data['name']),
            'code'              => $data['code'] !== '' ? $data['code'] : null,
            'description'       => $data['description'] !== '' ? $data['description'] : null,
            'status'            => $data['status'],
            'target_date'       => $data['target_date'] ?: null,
            'owner_entity_id'   => $data['owner_entity_id'] !== '' ? (int) $data['owner_entity_id'] : null,
            'urgency_statement' => $data['urgency_statement'] !== '' ? $data['urgency_statement'] : null,
            'vision_statement'  => $data['vision_statement'] !== '' ? $data['vision_statement'] : null,
        ];

        if ($this->editingId) {
            $project = ChangeProject::where('team_id', Auth::user()->currentTeam->id)->find($this->editingId);
            if ($project) {
                $project->update($payload);
                $this->dispatch('toast', message: 'Projekt aktualisiert');
            }
        } else {
            $project = ChangeProject::create(array_merge($payload, [
                'team_id' => Auth::user()->currentTeam->id,
                'user_id' => Auth::id(),
            ]));
            $project->createDefaultPhases();
            $this->dispatch('toast', message: 'Projekt mit 8 Kotter-Phasen erstellt');
        }

        $this->modalShow = false;
        $this->editingId = null;
    }

    public function delete(int $id): void
    {
        $project = ChangeProject::where('team_id', Auth::user()->currentTeam->id)->find($id);
        if (! $project) return;

        $project->delete();
        $this->dispatch('toast', message: 'Projekt gelöscht');
    }

    public function render()
    {
        return view('change::livewire.change-project.index')
            ->layout('platform::layouts.app');
    }
}
