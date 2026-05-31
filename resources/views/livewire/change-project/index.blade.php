<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Change-Projekte" />
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
        <x-ui-page-sidebar title="Suche" width="w-72" :defaultOpen="true" side="left">
            <div class="p-4">
                <x-ui-input-text wire:model.live.debounce.300ms="search" placeholder="Name, Code, Beschreibung..." size="sm" />
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    {{-- Main content (default slot) --}}
    <x-ui-page-container>
        @if($this->projects->isEmpty())
            <div class="flex flex-col items-center justify-center py-16 text-center">
                @svg('heroicon-o-arrows-right-left', 'w-12 h-12 text-gray-300 mb-4')
                <h3 class="text-sm font-semibold text-gray-900 mb-1">Keine Change-Projekte</h3>
                <p class="text-xs text-gray-500 mb-4">Erstellen Sie ein neues Change-Projekt, um den Kotter 8-Stufen-Prozess zu starten.</p>
                <x-ui-button variant="primary" size="sm" wire:click="create">
                    @svg('heroicon-o-plus', 'w-4 h-4')
                    Neues Projekt
                </x-ui-button>
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($this->projects as $project)
                    @php
                        $activePhase = $project->phases->firstWhere('status.value', 'in_progress');
                        $borderColor = $activePhase ? $activePhase->phase_number->color() : '#D1D5DB';
                    @endphp
                    <a href="{{ route('change.projects.show', $project) }}"
                       class="group block rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:shadow-md hover:border-blue-300 transition-all duration-200 border-l-[4px]"
                       style="border-left-color: {{ $borderColor }};">

                        {{-- Header --}}
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <div class="min-w-0">
                                <h3 class="font-semibold text-sm text-gray-900 truncate">{{ $project->name }}</h3>
                                @if($project->code)
                                    <span class="text-xs text-gray-500" style="font-family: 'JetBrains Mono', monospace;">{{ $project->code }}</span>
                                @endif
                            </div>
                            <x-ui-badge :color="$project->status->color()" size="xs">{{ $project->status->label() }}</x-ui-badge>
                        </div>

                        @if($project->description)
                            <p class="text-xs text-gray-500 line-clamp-2 mb-3">{{ $project->description }}</p>
                        @endif

                        {{-- Mini Bauhaus Shapes + Progress --}}
                        <div class="mb-3">
                            <div class="flex items-center gap-1 mb-2">
                                @foreach($project->phases->sortBy('phase_number.value') as $phase)
                                    @php
                                        $miniStatus = $phase->status->value;
                                        $miniIsFilled = in_array($miniStatus, ['completed', 'in_progress']);
                                        $miniColor = $miniIsFilled ? $phase->phase_number->color() : '#D1D5DB';
                                        $miniIsActive = $miniStatus === 'in_progress';
                                    @endphp
                                    <div class="relative">
                                        <svg width="12" height="12" viewBox="0 0 16 16" style="color: {{ $miniColor }};">
                                            @switch($phase->phase_number->shape())
                                                @case('triangle')
                                                    <polygon points="8,1 15,15 1,15" fill="currentColor"/>
                                                    @break
                                                @case('diamond')
                                                    <polygon points="8,1 15,8 8,15 1,8" fill="currentColor"/>
                                                    @break
                                                @case('circle')
                                                    <circle cx="8" cy="8" r="7" fill="currentColor"/>
                                                    @break
                                                @case('square')
                                                    <rect x="1" y="1" width="14" height="14" fill="currentColor"/>
                                                    @break
                                                @case('hexagon')
                                                    <polygon points="8,1 14,4 14,12 8,15 2,12 2,4" fill="currentColor"/>
                                                    @break
                                                @case('pentagon')
                                                    <polygon points="8,1 15,6 12,15 4,15 1,6" fill="currentColor"/>
                                                    @break
                                                @case('octagon')
                                                    <polygon points="5,1 11,1 15,5 15,11 11,15 5,15 1,11 1,5" fill="currentColor"/>
                                                    @break
                                            @endswitch
                                        </svg>
                                        @if($miniIsActive)
                                            <span class="absolute -top-0.5 -right-0.5 w-1.5 h-1.5 rounded-full animate-pulse" style="background: {{ $miniColor }};"></span>
                                        @endif
                                    </div>
                                @endforeach
                                <span class="ml-auto text-gray-500 text-[10px]" style="font-family: 'JetBrains Mono', monospace;">{{ $project->completed_phases_count }}/{{ $project->phases_count }}</span>
                            </div>

                            <div class="flex gap-0.5">
                                @foreach($project->phases->sortBy('phase_number.value') as $phase)
                                    @php
                                        $segColor = match($phase->status->value) {
                                            'completed' => $phase->phase_number->color(),
                                            'in_progress' => $phase->phase_number->color() . '80',
                                            default => '#E5E7EB',
                                        };
                                    @endphp
                                    <div class="flex-1 h-1.5 rounded-full transition-all duration-500"
                                         style="background: {{ $segColor }};"></div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Footer --}}
                        <div class="flex items-center justify-between text-xs text-gray-500">
                            <span>{{ $project->actions_count }} Maßnahmen</span>
                            @if($project->target_date)
                                <span style="font-family: 'JetBrains Mono', monospace;">{{ $project->target_date->format('d.m.Y') }}</span>
                            @endif
                        </div>

                        @if($project->ownerEntity)
                            <div class="mt-2 text-xs text-gray-500">
                                @svg('heroicon-o-user-circle', 'w-3.5 h-3.5 inline-block')
                                {{ $project->ownerEntity->name }}
                            </div>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif
    </x-ui-page-container>

    {{-- Create/Edit Modal --}}
    <x-ui-modal wire:model="modalShow" :title="$editingId ? 'Projekt bearbeiten' : 'Neues Change-Projekt'">
        <form wire:submit="store" class="space-y-4">
            <x-ui-input-text wire:model="form.name" label="Name" required />
            <x-ui-input-text wire:model="form.code" label="Code" placeholder="z.B. CP-001" />
            <x-ui-input-textarea wire:model="form.description" label="Beschreibung" rows="3" />

            <div class="grid grid-cols-2 gap-4">
                <x-ui-input-select
                    name="form.status"
                    wire:model="form.status"
                    label="Status"
                    :options="['draft' => 'Entwurf', 'active' => 'Aktiv', 'paused' => 'Pausiert', 'completed' => 'Abgeschlossen', 'cancelled' => 'Abgebrochen']"
                />
                <x-ui-input-text wire:model="form.target_date" label="Zieldatum" type="date" />
            </div>

            <x-ui-input-select
                name="form.owner_entity_id"
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
