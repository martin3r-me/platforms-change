<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[['label' => 'Change-Projekte']]">
            <x-slot name="left">
                <x-ui-input-select
                    wire:key="filter-status"
                    name="statusFilter"
                    :options="['draft' => 'Entwurf', 'active' => 'Aktiv', 'paused' => 'Pausiert', 'completed' => 'Abgeschlossen', 'cancelled' => 'Abgebrochen']"
                    wire:model.live="statusFilter"
                    :nullable="true"
                    nullLabel="Alle Status"
                    size="xs"
                />
            </x-slot>

            <x-ui-button variant="primary" size="sm" wire:click="create">
                @svg('heroicon-o-plus', 'w-4 h-4')
                <span>Neues Projekt</span>
            </x-ui-button>
        </x-ui-page-actionbar>
    </x-slot>

    <x-slot name="sidebar">
        <div class="px-4 py-4">
            <h3 class="text-xs font-semibold uppercase tracking-wide text-[color:var(--ui-muted)] mb-2">Suche</h3>
            <x-ui-input-text wire:model.live.debounce.300ms="search" placeholder="Name, Code, Beschreibung..." size="sm" />
        </div>
    </x-slot>

    <x-slot name="main">
        @if($this->projects->isEmpty())
            <div class="flex flex-col items-center justify-center py-16 text-center">
                @svg('heroicon-o-arrows-right-left', 'w-12 h-12 text-[color:var(--ui-muted)] mb-4')
                <h3 class="text-sm font-semibold text-[color:var(--ui-text)] mb-1">Keine Change-Projekte</h3>
                <p class="text-xs text-[color:var(--ui-secondary)] mb-4">Erstellen Sie ein neues Change-Projekt, um den Kotter 8-Stufen-Prozess zu starten.</p>
                <x-ui-button variant="primary" size="sm" wire:click="create">
                    @svg('heroicon-o-plus', 'w-4 h-4')
                    Neues Projekt
                </x-ui-button>
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($this->projects as $project)
                    <a href="{{ route('change.projects.show', $project) }}"
                       class="group block rounded-xl border border-white/40 bg-white/60 backdrop-blur-sm p-5 shadow-sm hover:shadow-md hover:border-[rgb(var(--ui-primary-rgb))]/30 transition-all duration-200">

                        {{-- Header --}}
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <div class="min-w-0">
                                <h3 class="font-semibold text-sm text-[color:var(--ui-text)] truncate">{{ $project->name }}</h3>
                                @if($project->code)
                                    <span class="text-xs text-[color:var(--ui-secondary)]">{{ $project->code }}</span>
                                @endif
                            </div>
                            <x-ui-badge :color="$project->status->color()" size="xs">{{ $project->status->label() }}</x-ui-badge>
                        </div>

                        @if($project->description)
                            <p class="text-xs text-[color:var(--ui-secondary)] line-clamp-2 mb-3">{{ $project->description }}</p>
                        @endif

                        {{-- Progress bar --}}
                        @php
                            $progress = $project->phases_count > 0
                                ? round(($project->completed_phases_count / $project->phases_count) * 100)
                                : 0;
                        @endphp
                        <div class="mb-3">
                            <div class="flex items-center justify-between text-xs text-[color:var(--ui-secondary)] mb-1">
                                <span>Fortschritt</span>
                                <span>{{ $project->completed_phases_count }}/{{ $project->phases_count }} Phasen</span>
                            </div>
                            <div class="w-full bg-[color:var(--ui-bg-muted)] rounded-full h-1.5">
                                <div class="bg-[rgb(var(--ui-success-rgb))] h-1.5 rounded-full transition-all duration-500"
                                     style="width: {{ $progress }}%"></div>
                            </div>
                        </div>

                        {{-- Footer --}}
                        <div class="flex items-center justify-between text-xs text-[color:var(--ui-secondary)]">
                            <span>{{ $project->actions_count }} Massnahmen</span>
                            @if($project->target_date)
                                <span>Ziel: {{ $project->target_date->format('d.m.Y') }}</span>
                            @endif
                        </div>

                        @if($project->ownerEntity)
                            <div class="mt-2 text-xs text-[color:var(--ui-secondary)]">
                                @svg('heroicon-o-user-circle', 'w-3.5 h-3.5 inline-block')
                                {{ $project->ownerEntity->name }}
                            </div>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif
    </x-slot>

    {{-- Create/Edit Modal --}}
    <x-ui-modal wire:model="modalShow" :title="$editingId ? 'Projekt bearbeiten' : 'Neues Change-Projekt'">
        <form wire:submit="store" class="space-y-4">
            <x-ui-input-text wire:model="form.name" label="Name" required />
            <x-ui-input-text wire:model="form.code" label="Code" placeholder="z.B. CP-001" />
            <x-ui-input-textarea wire:model="form.description" label="Beschreibung" rows="3" />

            <div class="grid grid-cols-2 gap-4">
                <x-ui-input-select
                    wire:model="form.status"
                    label="Status"
                    :options="['draft' => 'Entwurf', 'active' => 'Aktiv', 'paused' => 'Pausiert', 'completed' => 'Abgeschlossen', 'cancelled' => 'Abgebrochen']"
                />
                <x-ui-input-text wire:model="form.target_date" label="Zieldatum" type="date" />
            </div>

            <x-ui-input-select
                wire:model="form.owner_entity_id"
                label="Owner (Organisation)"
                :options="$this->availableEntities->pluck('name', 'id')->toArray()"
                :nullable="true"
                nullLabel="Kein Owner"
            />

            <x-ui-input-textarea wire:model="form.urgency_statement" label="Warum ist die Veränderung nötig?" rows="2" />
            <x-ui-input-textarea wire:model="form.vision_statement" label="Strategische Vision" rows="2" />

            <div class="flex justify-end gap-2">
                <x-ui-button variant="secondary" size="sm" wire:click="$set('modalShow', false)" type="button">Abbrechen</x-ui-button>
                <x-ui-button variant="primary" size="sm" type="submit">
                    {{ $editingId ? 'Speichern' : 'Erstellen' }}
                </x-ui-button>
            </div>
        </form>
    </x-ui-modal>
</x-ui-page>
