<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Change-Projekte', 'href' => route('change.projects.index')],
            ['label' => $project->name],
        ]">
            <x-slot name="left">
                @if($this->isDirty)
                    <x-ui-button variant="secondary" size="xs" wire:click="loadForm">Abbrechen</x-ui-button>
                    <x-ui-button variant="primary" size="xs" wire:click="save">Speichern</x-ui-button>
                @endif
            </x-slot>

            <x-ui-badge :color="$project->status->color()" size="sm">{{ $project->status->label() }}</x-ui-badge>
        </x-ui-page-actionbar>
    </x-slot>

    <x-slot name="sidebar">
        <div class="px-4 py-4">
            <h3 class="text-xs font-semibold uppercase tracking-wide text-[color:var(--ui-muted)] mb-2">Navigation</h3>
            <nav class="space-y-1">
                @foreach(['board' => 'Board', 'stakeholder' => 'Stakeholder', 'log' => 'Log', 'settings' => 'Einstellungen'] as $tab => $label)
                    <button wire:click="$set('activeTab', '{{ $tab }}')"
                            class="w-full text-left px-3 py-2 rounded-lg text-sm transition-all duration-200 {{ $activeTab === $tab ? 'bg-[rgb(var(--ui-primary-rgb))]/10 text-[rgb(var(--ui-primary-rgb))] font-medium' : 'text-[color:var(--ui-secondary)] hover:bg-white/60' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </nav>
        </div>

        {{-- Progress overview --}}
        <div class="px-4 py-4 border-t border-[color:var(--ui-border)]">
            <h3 class="text-xs font-semibold uppercase tracking-wide text-[color:var(--ui-muted)] mb-2">Fortschritt</h3>
            @php
                $completedPhases = $this->phases->where('status.value', 'completed')->count();
                $totalPhases = $this->phases->count();
                $progress = $totalPhases > 0 ? round(($completedPhases / $totalPhases) * 100) : 0;
            @endphp
            <div class="space-y-2">
                <div class="flex justify-between text-xs text-[color:var(--ui-secondary)]">
                    <span>{{ $completedPhases }}/{{ $totalPhases }} Phasen</span>
                    <span>{{ $progress }}%</span>
                </div>
                <div class="w-full bg-[color:var(--ui-bg-muted)] rounded-full h-2">
                    <div class="bg-[rgb(var(--ui-success-rgb))] h-2 rounded-full transition-all duration-500"
                         style="width: {{ $progress }}%"></div>
                </div>
            </div>

            {{-- Phase status mini-list --}}
            <div class="mt-3 space-y-1">
                @foreach($this->phases as $phase)
                    <div class="flex items-center gap-2 text-xs">
                        @php
                            $statusColor = match($phase->status->value) {
                                'completed' => 'text-[rgb(var(--ui-success-rgb))]',
                                'in_progress' => 'text-[rgb(var(--ui-warning-rgb))]',
                                'blocked' => 'text-[rgb(var(--ui-danger-rgb))]',
                                default => 'text-[color:var(--ui-secondary)]',
                            };
                            $statusIcon = match($phase->status->value) {
                                'completed' => 'heroicon-s-check-circle',
                                'in_progress' => 'heroicon-s-arrow-path',
                                'blocked' => 'heroicon-s-no-symbol',
                                default => 'heroicon-o-circle-stack',
                            };
                        @endphp
                        @svg($statusIcon, 'w-3.5 h-3.5 ' . $statusColor)
                        <span class="truncate {{ $phase->status->value === 'completed' ? 'line-through opacity-60' : '' }}">
                            {{ $phase->phase_number->shortLabel() }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </x-slot>

    <x-slot name="main">

        {{-- ═══════════════════════════════════════════════════════════ --}}
        {{-- TAB: BOARD --}}
        {{-- ═══════════════════════════════════════════════════════════ --}}
        @if($activeTab === 'board')
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($this->phases as $phase)
                    <div class="rounded-xl border border-white/40 bg-white/60 backdrop-blur-sm p-4 shadow-sm
                                {{ $phase->status->value === 'completed' ? 'ring-2 ring-[rgb(var(--ui-success-rgb))]/20' : '' }}
                                {{ $phase->status->value === 'blocked' ? 'ring-2 ring-[rgb(var(--ui-danger-rgb))]/20' : '' }}">

                        {{-- Phase header --}}
                        <div class="flex items-start justify-between gap-2 mb-3">
                            <div>
                                <span class="text-xs font-bold text-[color:var(--ui-secondary)]">Phase {{ $phase->phase_number->value }}</span>
                                <h3 class="text-sm font-semibold text-[color:var(--ui-text)] leading-tight">
                                    {{ $phase->phase_number->shortLabel() }}
                                </h3>
                            </div>
                            <x-ui-badge :color="$phase->status->color()" size="xs">{{ $phase->status->label() }}</x-ui-badge>
                        </div>

                        <p class="text-xs text-[color:var(--ui-secondary)] mb-3 line-clamp-2">
                            {{ $phase->phase_number->description() }}
                        </p>

                        {{-- Inline edit form --}}
                        @if($editingPhaseId === $phase->id)
                            <div class="space-y-2 border-t border-white/40 pt-3">
                                <x-ui-input-select
                                    name="phaseForm.status"
                                    wire:model="phaseForm.status"
                                    :options="['not_started' => 'Nicht gestartet', 'in_progress' => 'In Bearbeitung', 'completed' => 'Abgeschlossen', 'blocked' => 'Blockiert']"
                                    size="xs"
                                />
                                <x-ui-input-text wire:model="phaseForm.responsible" placeholder="Verantwortlich" size="xs" />
                                <x-ui-input-textarea wire:model="phaseForm.notes" placeholder="Notizen..." rows="2" size="xs" />
                                <x-ui-input-textarea wire:model="phaseForm.evidence" placeholder="Nachweis/Dokumentation..." rows="2" size="xs" />
                                <div class="flex gap-1">
                                    <x-ui-button variant="primary" size="xs" wire:click="updatePhase">Speichern</x-ui-button>
                                    <x-ui-button variant="secondary" size="xs" wire:click="cancelPhaseEdit">Abbrechen</x-ui-button>
                                </div>
                            </div>
                        @else
                            {{-- Phase details --}}
                            @if($phase->responsible)
                                <div class="text-xs text-[color:var(--ui-secondary)] mb-1">
                                    @svg('heroicon-o-user', 'w-3 h-3 inline-block')
                                    {{ $phase->responsible }}
                                </div>
                            @endif
                            @if($phase->notes)
                                <div class="text-xs text-[color:var(--ui-secondary)] mb-2 bg-white/40 rounded-lg p-2 line-clamp-3">
                                    {{ $phase->notes }}
                                </div>
                            @endif

                            {{-- Actions for this phase --}}
                            @if($phase->actions_count > 0)
                                <div class="text-xs text-[color:var(--ui-secondary)] mb-2">
                                    @svg('heroicon-o-clipboard-document-list', 'w-3 h-3 inline-block')
                                    {{ $phase->actions_count }} Massnahmen
                                </div>
                            @endif

                            {{-- Quick actions --}}
                            <div class="flex items-center gap-1 border-t border-white/40 pt-2 mt-2">
                                <button wire:click="editPhase({{ $phase->id }})"
                                        class="text-xs text-[color:var(--ui-secondary)] hover:text-[rgb(var(--ui-primary-rgb))] transition-colors">
                                    @svg('heroicon-o-pencil', 'w-3.5 h-3.5')
                                </button>
                                <button wire:click="createAction({{ $phase->id }})"
                                        class="text-xs text-[color:var(--ui-secondary)] hover:text-[rgb(var(--ui-primary-rgb))] transition-colors"
                                        title="Massnahme hinzufügen">
                                    @svg('heroicon-o-plus', 'w-3.5 h-3.5')
                                </button>
                                @if($phase->status->value !== 'completed')
                                    <button wire:click="quickUpdatePhaseStatus({{ $phase->id }}, 'completed')"
                                            class="ml-auto text-xs text-[color:var(--ui-secondary)] hover:text-[rgb(var(--ui-success-rgb))] transition-colors"
                                            title="Als abgeschlossen markieren">
                                        @svg('heroicon-o-check-circle', 'w-3.5 h-3.5')
                                    </button>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Actions overview below the board --}}
            <div class="mt-8">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold text-[color:var(--ui-text)]">Alle Massnahmen</h2>
                    <div class="flex items-center gap-2">
                        <x-ui-input-select
                            name="actionStatusFilter"
                            wire:model.live="actionStatusFilter"
                            :options="['open' => 'Offen', 'in_progress' => 'In Bearbeitung', 'done' => 'Erledigt', 'cancelled' => 'Abgebrochen']"
                            :nullable="true"
                            nullLabel="Alle"
                            size="xs"
                        />
                        <x-ui-button variant="primary" size="xs" wire:click="createAction">
                            @svg('heroicon-o-plus', 'w-3.5 h-3.5')
                            Massnahme
                        </x-ui-button>
                    </div>
                </div>

                @if($this->actions->isEmpty())
                    <p class="text-xs text-[color:var(--ui-secondary)]">Keine Massnahmen vorhanden.</p>
                @else
                    <div class="space-y-2">
                        @foreach($this->actions as $action)
                            <div class="flex items-center gap-3 rounded-lg border border-white/40 bg-white/60 backdrop-blur-sm px-4 py-3">
                                <button wire:click="quickUpdateActionStatus({{ $action->id }}, '{{ $action->status->value === 'done' ? 'open' : 'done' }}')"
                                        class="flex-shrink-0 {{ $action->status->value === 'done' ? 'text-[rgb(var(--ui-success-rgb))]' : 'text-[color:var(--ui-secondary)] hover:text-[rgb(var(--ui-success-rgb))]' }} transition-colors">
                                    @svg($action->status->value === 'done' ? 'heroicon-s-check-circle' : 'heroicon-o-circle-stack', 'w-5 h-5')
                                </button>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-medium {{ $action->status->value === 'done' ? 'line-through opacity-60' : '' }}">{{ $action->title }}</span>
                                        <x-ui-badge :color="$action->status->color()" size="xs">{{ $action->status->label() }}</x-ui-badge>
                                    </div>
                                    <div class="flex items-center gap-3 text-xs text-[color:var(--ui-secondary)] mt-0.5">
                                        @if($action->phase)
                                            <span>Phase {{ $action->phase->phase_number->value }}: {{ $action->phase->phase_number->shortLabel() }}</span>
                                        @endif
                                        @if($action->responsible)
                                            <span>@svg('heroicon-o-user', 'w-3 h-3 inline-block') {{ $action->responsible }}</span>
                                        @endif
                                        @if($action->due_date)
                                            <span class="{{ $action->due_date->isPast() && !in_array($action->status->value, ['done', 'cancelled']) ? 'text-[rgb(var(--ui-danger-rgb))] font-medium' : '' }}">
                                                @svg('heroicon-o-calendar', 'w-3 h-3 inline-block') {{ $action->due_date->format('d.m.Y') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center gap-1">
                                    <button wire:click="editAction({{ $action->id }})" class="text-[color:var(--ui-secondary)] hover:text-[rgb(var(--ui-primary-rgb))] transition-colors">
                                        @svg('heroicon-o-pencil', 'w-4 h-4')
                                    </button>
                                    <button wire:click="deleteAction({{ $action->id }})" wire:confirm="Massnahme wirklich löschen?" class="text-[color:var(--ui-secondary)] hover:text-[rgb(var(--ui-danger-rgb))] transition-colors">
                                        @svg('heroicon-o-trash', 'w-4 h-4')
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>


        {{-- ═══════════════════════════════════════════════════════════ --}}
        {{-- TAB: STAKEHOLDER --}}
        {{-- ═══════════════════════════════════════════════════════════ --}}
        @elseif($activeTab === 'stakeholder')
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-[color:var(--ui-text)]">Stakeholder-Map</h2>
                <x-ui-button variant="primary" size="xs" wire:click="createStakeholder">
                    @svg('heroicon-o-plus', 'w-3.5 h-3.5')
                    Stakeholder
                </x-ui-button>
            </div>

            {{-- Influence/Support Matrix --}}
            @if($this->stakeholders->isNotEmpty())
                <div class="grid grid-cols-5 gap-1 mb-6 text-xs">
                    {{-- Header row --}}
                    <div class="font-medium text-center text-[color:var(--ui-secondary)] p-2"></div>
                    @foreach(\Platform\Change\Enums\StakeholderSupport::cases() as $support)
                        <div class="font-medium text-center text-[color:var(--ui-secondary)] p-2 rounded-t-lg bg-white/30">
                            {{ $support->label() }}
                        </div>
                    @endforeach

                    {{-- Matrix rows --}}
                    @foreach(array_reverse(\Platform\Change\Enums\StakeholderInfluence::cases()) as $influence)
                        <div class="font-medium text-right text-[color:var(--ui-secondary)] p-2 pr-3 bg-white/30 rounded-l-lg">
                            {{ $influence->label() }}
                        </div>
                        @foreach(\Platform\Change\Enums\StakeholderSupport::cases() as $support)
                            @php
                                $cellStakeholders = $this->stakeholders->filter(fn($s) =>
                                    ($s->influence_level->value ?? $s->influence_level) === $influence->value &&
                                    ($s->support_level->value ?? $s->support_level) === $support->value
                                );
                            @endphp
                            <div class="bg-white/40 border border-white/60 rounded p-1 min-h-[3rem]">
                                @foreach($cellStakeholders as $s)
                                    <button wire:click="editStakeholder({{ $s->id }})"
                                            class="block w-full text-left px-1.5 py-0.5 rounded text-[10px] mb-0.5 truncate
                                                   bg-[rgb(var(--ui-primary-rgb))]/10 text-[rgb(var(--ui-primary-rgb))] hover:bg-[rgb(var(--ui-primary-rgb))]/20 transition-colors">
                                        {{ $s->name }}
                                    </button>
                                @endforeach
                            </div>
                        @endforeach
                    @endforeach
                </div>
            @endif

            {{-- Stakeholder list --}}
            @if($this->stakeholders->isEmpty())
                <p class="text-xs text-[color:var(--ui-secondary)]">Keine Stakeholder erfasst.</p>
            @else
                <div class="space-y-2">
                    @foreach($this->stakeholders as $stakeholder)
                        <div class="flex items-center gap-3 rounded-lg border border-white/40 bg-white/60 backdrop-blur-sm px-4 py-3">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium">{{ $stakeholder->name }}</span>
                                    <x-ui-badge :color="$stakeholder->influence_level->color()" size="xs">{{ $stakeholder->influence_level->label() }}</x-ui-badge>
                                    <x-ui-badge :color="$stakeholder->support_level->color()" size="xs">{{ $stakeholder->support_level->label() }}</x-ui-badge>
                                </div>
                                <div class="flex items-center gap-3 text-xs text-[color:var(--ui-secondary)] mt-0.5">
                                    @if($stakeholder->role)
                                        <span>{{ $stakeholder->role }}</span>
                                    @endif
                                    @if($stakeholder->entity)
                                        <span>@svg('heroicon-o-building-office', 'w-3 h-3 inline-block') {{ $stakeholder->entity->name }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-1">
                                <button wire:click="editStakeholder({{ $stakeholder->id }})" class="text-[color:var(--ui-secondary)] hover:text-[rgb(var(--ui-primary-rgb))] transition-colors">
                                    @svg('heroicon-o-pencil', 'w-4 h-4')
                                </button>
                                <button wire:click="deleteStakeholder({{ $stakeholder->id }})" wire:confirm="Stakeholder wirklich löschen?" class="text-[color:var(--ui-secondary)] hover:text-[rgb(var(--ui-danger-rgb))] transition-colors">
                                    @svg('heroicon-o-trash', 'w-4 h-4')
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif


        {{-- ═══════════════════════════════════════════════════════════ --}}
        {{-- TAB: LOG --}}
        {{-- ═══════════════════════════════════════════════════════════ --}}
        @elseif($activeTab === 'log')
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-[color:var(--ui-text)]">Change-Log</h2>
                <div class="flex items-center gap-2">
                    <x-ui-input-select
                        name="logTypeFilter"
                        wire:model.live="logTypeFilter"
                        :options="['note' => 'Notiz', 'milestone' => 'Meilenstein', 'decision' => 'Entscheidung', 'risk' => 'Risiko', 'blocker' => 'Blocker']"
                        :nullable="true"
                        nullLabel="Alle Typen"
                        size="xs"
                    />
                    <x-ui-input-select
                        name="logPhaseFilter"
                        wire:model.live="logPhaseFilter"
                        :options="$this->phases->pluck('phase_number')->mapWithKeys(fn($p) => [$this->phases->firstWhere('phase_number', $p)->id => 'Phase ' . $p->value . ': ' . $p->shortLabel()])->toArray()"
                        :nullable="true"
                        nullLabel="Alle Phasen"
                        size="xs"
                    />
                    <x-ui-button variant="primary" size="xs" wire:click="createLog">
                        @svg('heroicon-o-plus', 'w-3.5 h-3.5')
                        Eintrag
                    </x-ui-button>
                </div>
            </div>

            @if($this->logs->isEmpty())
                <p class="text-xs text-[color:var(--ui-secondary)]">Keine Log-Einträge vorhanden.</p>
            @else
                <div class="space-y-3">
                    @foreach($this->logs as $log)
                        <div class="relative pl-6 border-l-2 border-white/40 pb-4 last:pb-0">
                            {{-- Timeline dot --}}
                            <div class="absolute -left-[7px] top-0 w-3 h-3 rounded-full border-2 border-white
                                        bg-[rgb(var(--ui-{{ $log->type->color() }}-rgb))]"></div>

                            <div class="rounded-lg border border-white/40 bg-white/60 backdrop-blur-sm p-4">
                                <div class="flex items-start justify-between gap-2 mb-1">
                                    <div class="flex items-center gap-2">
                                        @svg($log->type->icon(), 'w-4 h-4 text-[rgb(var(--ui-' . $log->type->color() . '-rgb))]')
                                        <span class="text-sm font-medium">{{ $log->title }}</span>
                                        <x-ui-badge :color="$log->type->color()" size="xs">{{ $log->type->label() }}</x-ui-badge>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <button wire:click="editLog({{ $log->id }})" class="text-[color:var(--ui-secondary)] hover:text-[rgb(var(--ui-primary-rgb))] transition-colors">
                                            @svg('heroicon-o-pencil', 'w-3.5 h-3.5')
                                        </button>
                                        <button wire:click="deleteLog({{ $log->id }})" wire:confirm="Eintrag wirklich löschen?" class="text-[color:var(--ui-secondary)] hover:text-[rgb(var(--ui-danger-rgb))] transition-colors">
                                            @svg('heroicon-o-trash', 'w-3.5 h-3.5')
                                        </button>
                                    </div>
                                </div>

                                @if($log->content)
                                    <p class="text-xs text-[color:var(--ui-secondary)] mt-1 whitespace-pre-line">{{ $log->content }}</p>
                                @endif

                                <div class="flex items-center gap-3 text-[10px] text-[color:var(--ui-secondary)] mt-2">
                                    <span>{{ $log->created_at->format('d.m.Y H:i') }}</span>
                                    @if($log->user)
                                        <span>{{ $log->user->name }}</span>
                                    @endif
                                    @if($log->phase)
                                        <span>Phase {{ $log->phase->phase_number->value }}: {{ $log->phase->phase_number->shortLabel() }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif


        {{-- ═══════════════════════════════════════════════════════════ --}}
        {{-- TAB: EINSTELLUNGEN --}}
        {{-- ═══════════════════════════════════════════════════════════ --}}
        @elseif($activeTab === 'settings')
            <div class="max-w-2xl space-y-6">
                <div class="rounded-xl border border-white/40 bg-white/60 backdrop-blur-sm p-6">
                    <h3 class="text-sm font-semibold mb-4">Projekt-Details</h3>

                    <div class="space-y-4">
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

                        <x-ui-input-textarea wire:model="form.urgency_statement" label="Warum ist die Veränderung nötig?" rows="3" />
                        <x-ui-input-textarea wire:model="form.vision_statement" label="Strategische Vision" rows="3" />
                    </div>

                    @if($this->isDirty)
                        <div class="flex justify-end gap-2 mt-4 pt-4 border-t border-white/40">
                            <x-ui-button variant="secondary" size="sm" wire:click="loadForm">Abbrechen</x-ui-button>
                            <x-ui-button variant="primary" size="sm" wire:click="save">Speichern</x-ui-button>
                        </div>
                    @endif
                </div>

                {{-- Danger zone --}}
                <div class="rounded-xl border border-[rgb(var(--ui-danger-rgb))]/20 bg-[rgb(var(--ui-danger-rgb))]/5 p-6">
                    <h3 class="text-sm font-semibold text-[rgb(var(--ui-danger-rgb))] mb-2">Gefahrenzone</h3>
                    <p class="text-xs text-[color:var(--ui-secondary)] mb-4">Das Löschen eines Projekts entfernt alle Phasen, Stakeholder, Massnahmen und Log-Einträge.</p>
                    <x-ui-button variant="danger" size="sm" wire:click="delete" wire:confirm="Projekt und alle zugehörigen Daten wirklich löschen?">
                        @svg('heroicon-o-trash', 'w-4 h-4')
                        Projekt löschen
                    </x-ui-button>
                </div>
            </div>
        @endif
    </x-slot>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- MODALS --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}

    {{-- Stakeholder Modal --}}
    <x-ui-modal wire:model="stakeholderModalShow" :title="$editingStakeholderId ? 'Stakeholder bearbeiten' : 'Neuer Stakeholder'">
        <form wire:submit="storeStakeholder" class="space-y-4">
            <x-ui-input-text wire:model="stakeholderForm.name" label="Name" required />
            <x-ui-input-text wire:model="stakeholderForm.role" label="Rolle" />
            <div class="grid grid-cols-2 gap-4">
                <x-ui-input-select
                    name="stakeholderForm.influence_level"
                    wire:model="stakeholderForm.influence_level"
                    label="Einfluss"
                    :options="['low' => 'Niedrig', 'medium' => 'Mittel', 'high' => 'Hoch', 'critical' => 'Kritisch']"
                />
                <x-ui-input-select
                    name="stakeholderForm.support_level"
                    wire:model="stakeholderForm.support_level"
                    label="Unterstützung"
                    :options="['champion' => 'Champion', 'supporter' => 'Unterstützer', 'neutral' => 'Neutral', 'resistant' => 'Widerständig', 'blocker' => 'Blocker']"
                />
            </div>
            <x-ui-input-select
                name="stakeholderForm.entity_id"
                wire:model="stakeholderForm.entity_id"
                label="Organisation (optional)"
                :options="$this->availableEntities->pluck('name', 'id')->toArray()"
                :nullable="true"
                nullLabel="Keine Zuordnung"
            />
            <x-ui-input-textarea wire:model="stakeholderForm.notes" label="Notizen" rows="3" />

            <div class="flex justify-end gap-2">
                <x-ui-button variant="secondary" size="sm" wire:click="$set('stakeholderModalShow', false)" type="button">Abbrechen</x-ui-button>
                <x-ui-button variant="primary" size="sm" type="submit">{{ $editingStakeholderId ? 'Speichern' : 'Erstellen' }}</x-ui-button>
            </div>
        </form>
    </x-ui-modal>

    {{-- Action Modal --}}
    <x-ui-modal wire:model="actionModalShow" :title="$editingActionId ? 'Massnahme bearbeiten' : 'Neue Massnahme'">
        <form wire:submit="storeAction" class="space-y-4">
            <x-ui-input-text wire:model="actionForm.title" label="Titel" required />
            <x-ui-input-textarea wire:model="actionForm.description" label="Beschreibung" rows="3" />
            <div class="grid grid-cols-2 gap-4">
                <x-ui-input-select
                    name="actionForm.status"
                    wire:model="actionForm.status"
                    label="Status"
                    :options="['open' => 'Offen', 'in_progress' => 'In Bearbeitung', 'done' => 'Erledigt', 'cancelled' => 'Abgebrochen']"
                />
                <x-ui-input-text wire:model="actionForm.due_date" label="Fällig am" type="date" />
            </div>
            <x-ui-input-text wire:model="actionForm.responsible" label="Verantwortlich" />
            <x-ui-input-select
                name="actionForm.phase_id"
                wire:model="actionForm.phase_id"
                label="Phase (optional)"
                :options="$this->phases->mapWithKeys(fn($p) => [$p->id => 'Phase ' . $p->phase_number->value . ': ' . $p->phase_number->shortLabel()])->toArray()"
                :nullable="true"
                nullLabel="Keine Zuordnung"
            />

            <div class="flex justify-end gap-2">
                <x-ui-button variant="secondary" size="sm" wire:click="$set('actionModalShow', false)" type="button">Abbrechen</x-ui-button>
                <x-ui-button variant="primary" size="sm" type="submit">{{ $editingActionId ? 'Speichern' : 'Erstellen' }}</x-ui-button>
            </div>
        </form>
    </x-ui-modal>

    {{-- Log Modal --}}
    <x-ui-modal wire:model="logModalShow" :title="$editingLogId ? 'Log-Eintrag bearbeiten' : 'Neuer Log-Eintrag'">
        <form wire:submit="storeLog" class="space-y-4">
            <x-ui-input-text wire:model="logForm.title" label="Titel" required />
            <x-ui-input-select
                name="logForm.type"
                wire:model="logForm.type"
                label="Typ"
                :options="['note' => 'Notiz', 'milestone' => 'Meilenstein', 'decision' => 'Entscheidung', 'risk' => 'Risiko', 'blocker' => 'Blocker']"
            />
            <x-ui-input-textarea wire:model="logForm.content" label="Inhalt" rows="4" />
            <x-ui-input-select
                name="logForm.phase_id"
                wire:model="logForm.phase_id"
                label="Phase (optional)"
                :options="$this->phases->mapWithKeys(fn($p) => [$p->id => 'Phase ' . $p->phase_number->value . ': ' . $p->phase_number->shortLabel()])->toArray()"
                :nullable="true"
                nullLabel="Keine Zuordnung"
            />

            <div class="flex justify-end gap-2">
                <x-ui-button variant="secondary" size="sm" wire:click="$set('logModalShow', false)" type="button">Abbrechen</x-ui-button>
                <x-ui-button variant="primary" size="sm" type="submit">{{ $editingLogId ? 'Speichern' : 'Erstellen' }}</x-ui-button>
            </div>
        </form>
    </x-ui-modal>
</x-ui-page>
