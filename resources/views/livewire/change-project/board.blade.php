<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Change-Projekte', 'href' => route('change.projects.index')],
            ['label' => $project->name, 'href' => route('change.projects.show', $project)],
            ['label' => 'Board'],
        ]">
            <x-ui-badge :color="$project->status->color()" size="sm">{{ $project->status->label() }}</x-ui-badge>
        </x-ui-page-actionbar>
    </x-slot>

    <x-slot name="sidebar">
        <div class="px-4 py-4">
            <h3 class="text-[10px] font-bold uppercase tracking-[0.15em] text-[color:var(--ui-muted)] mb-2" style="font-family: 'JetBrains Mono', monospace;">Navigation</h3>
            <nav class="space-y-1">
                <a href="{{ route('change.projects.show', $project) }}"
                   class="w-full text-left px-3 py-2 rounded-lg text-sm transition-all duration-200 flex items-center gap-2 text-[color:var(--ui-secondary)] hover:bg-white/60">
                    @svg('heroicon-o-arrow-left', 'w-4 h-4')
                    Zurück zum Projekt
                </a>
                <div class="w-full text-left px-3 py-2 rounded-lg text-sm flex items-center gap-2 bg-[rgb(var(--ui-primary-rgb))]/10 text-[rgb(var(--ui-primary-rgb))] font-medium">
                    @svg('heroicon-o-view-columns', 'w-4 h-4')
                    Board
                </div>
            </nav>
        </div>

        {{-- Phase List in Sidebar --}}
        <div class="px-4 py-4 border-t border-[color:var(--ui-border)]">
            <h3 class="text-[10px] font-bold uppercase tracking-[0.15em] text-[color:var(--ui-muted)] mb-3" style="font-family: 'JetBrains Mono', monospace;">Phasen</h3>
            <div class="space-y-1.5">
                @foreach($this->phases as $phase)
                    @php
                        $sideColor = in_array($phase->status->value, ['completed', 'in_progress']) ? $phase->phase_number->color() : '#D1D5DB';
                    @endphp
                    <div class="flex items-center gap-2.5 text-xs">
                        <svg width="14" height="14" viewBox="0 0 16 16" style="color: {{ $sideColor }};">
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
                        <span class="truncate {{ $phase->status->value === 'completed' ? 'line-through opacity-50' : '' }} {{ $phase->status->value === 'in_progress' ? 'font-medium' : '' }}">
                            {{ $phase->phase_number->shortLabel() }}
                        </span>
                        @if($phase->status->value === 'completed')
                            <span class="ml-auto text-[10px]" style="color: {{ $phase->phase_number->color() }};">&#10003;</span>
                        @elseif($phase->status->value === 'in_progress')
                            <span class="ml-auto w-1.5 h-1.5 rounded-full animate-pulse" style="background: {{ $phase->phase_number->color() }};"></span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </x-slot>

    <x-slot name="main">

        {{-- ═══════════════════════════════════════════════════════════ --}}
        {{-- PHASE JOURNEY BAR --}}
        {{-- ═══════════════════════════════════════════════════════════ --}}
        <div class="mb-6">
            <h2 class="text-xs font-bold uppercase tracking-[0.2em] text-[color:var(--ui-text)] mb-5" style="font-family: 'JetBrains Mono', monospace;">KOTTER 8-STEP MODEL</h2>

            <div class="relative px-4">
                {{-- Connection line (background) --}}
                <div class="absolute top-[20px] left-[calc(2rem+20px)] right-[calc(2rem+20px)] h-[2px] bg-[#E5E7EB]"></div>

                {{-- Colored progress line overlay --}}
                @php
                    $lastActiveIndex = -1;
                    foreach ($this->phases as $idx => $p) {
                        if (in_array($p->status->value, ['completed', 'in_progress'])) {
                            $lastActiveIndex = $idx;
                        }
                    }
                    $progressPercent = $lastActiveIndex >= 0 ? ($lastActiveIndex / 7) * 100 : 0;
                @endphp
                @if($lastActiveIndex >= 0)
                    <div class="absolute top-[20px] left-[calc(2rem+20px)] h-[2px] transition-all duration-700 ease-out"
                         style="width: {{ $progressPercent }}%; background: linear-gradient(90deg, #E63946, #F4A261, #E9C46A, #2A9D8F, #457B9D);"></div>
                @endif

                {{-- 8 Phase shapes --}}
                <div class="relative flex items-start justify-between">
                    @foreach($this->phases as $idx => $phase)
                        @php
                            $jColor = $phase->phase_number->color();
                            $jShape = $phase->phase_number->shape();
                            $jStatus = $phase->status->value;
                            $isFilled = in_array($jStatus, ['completed', 'in_progress']);
                            $isActive = $jStatus === 'in_progress';
                            $isBlocked = $jStatus === 'blocked';
                            $fillColor = $isFilled ? $jColor : '#E5E7EB';
                            $strokeColor = $isBlocked ? '#EF4444' : 'none';
                            $strokeWidth = $isBlocked ? '2' : '0';
                        @endphp
                        <div class="flex flex-col items-center" style="width: 60px;">
                            {{-- Shape container with optional glow --}}
                            <div class="relative flex items-center justify-center w-[40px] h-[40px]">
                                @if($isActive)
                                    {{-- Glow ring for active phase --}}
                                    <div class="absolute inset-[-4px] rounded-full opacity-30 animate-ping" style="background: {{ $jColor }}; animation-duration: 2s;"></div>
                                    <div class="absolute inset-[-3px] rounded-full opacity-20" style="background: {{ $jColor }};"></div>
                                @endif
                                <svg width="40" height="40" viewBox="0 0 40 40" class="relative z-10">
                                    @switch($jShape)
                                        @case('triangle')
                                            <polygon points="20,3 37,37 3,37" fill="{{ $fillColor }}" stroke="{{ $strokeColor }}" stroke-width="{{ $strokeWidth }}"/>
                                            @break
                                        @case('diamond')
                                            <polygon points="20,3 37,20 20,37 3,20" fill="{{ $fillColor }}" stroke="{{ $strokeColor }}" stroke-width="{{ $strokeWidth }}"/>
                                            @break
                                        @case('circle')
                                            <circle cx="20" cy="20" r="17" fill="{{ $fillColor }}" stroke="{{ $strokeColor }}" stroke-width="{{ $strokeWidth }}"/>
                                            @break
                                        @case('square')
                                            <rect x="3" y="3" width="34" height="34" fill="{{ $fillColor }}" stroke="{{ $strokeColor }}" stroke-width="{{ $strokeWidth }}"/>
                                            @break
                                        @case('hexagon')
                                            <polygon points="20,3 35,11 35,29 20,37 5,29 5,11" fill="{{ $fillColor }}" stroke="{{ $strokeColor }}" stroke-width="{{ $strokeWidth }}"/>
                                            @break
                                        @case('pentagon')
                                            <polygon points="20,3 37,15 30,37 10,37 3,15" fill="{{ $fillColor }}" stroke="{{ $strokeColor }}" stroke-width="{{ $strokeWidth }}"/>
                                            @break
                                        @case('octagon')
                                            <polygon points="13,3 27,3 37,13 37,27 27,37 13,37 3,27 3,13" fill="{{ $fillColor }}" stroke="{{ $strokeColor }}" stroke-width="{{ $strokeWidth }}"/>
                                            @break
                                    @endswitch
                                    {{-- Checkmark for completed --}}
                                    @if($jStatus === 'completed')
                                        <path d="M14 20l4 4 8-8" stroke="white" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                                    @endif
                                    {{-- Phase number inside shape (not completed) --}}
                                    @if($jStatus !== 'completed')
                                        <text x="20" y="20" text-anchor="middle" dominant-baseline="central"
                                              fill="{{ $isFilled ? 'white' : '#9CA3AF' }}"
                                              font-size="13" font-weight="700" font-family="'JetBrains Mono', monospace">{{ $phase->phase_number->value }}</text>
                                    @endif
                                </svg>
                            </div>
                            {{-- Phase number + short label --}}
                            <span class="mt-1.5 text-[10px] font-bold {{ $isFilled ? 'text-[color:var(--ui-text)]' : 'text-[color:var(--ui-muted)]' }}"
                                  style="font-family: 'JetBrains Mono', monospace;">{{ $phase->phase_number->value }}</span>
                            <span class="text-[9px] text-center leading-tight {{ $isFilled ? 'text-[color:var(--ui-secondary)]' : 'text-[color:var(--ui-muted)]' }} max-w-[60px] truncate">
                                {{ $phase->phase_number->shortLabel() }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════ --}}
        {{-- ACTIVE PHASE SPOTLIGHT --}}
        {{-- ═══════════════════════════════════════════════════════════ --}}
        @php
            $activePhase = $this->phases->firstWhere('status.value', 'in_progress');
        @endphp

        @if($activePhase)
            @php
                $spotColor = $activePhase->phase_number->color();
                $spotShape = $activePhase->phase_number->shape();
                $phaseActions = $activePhase->actions ?? collect();
                $openActions = $phaseActions->whereNotIn('status.value', ['done', 'cancelled'])->count();
                $doneActions = $phaseActions->where('status.value', 'done')->count();
            @endphp
            <div class="mb-8 rounded-xl border border-black/5 p-5 relative overflow-hidden"
                 style="background: {{ $spotColor }}10;">
                {{-- Watermark shape --}}
                <div class="absolute -right-6 -bottom-6 opacity-[0.06] pointer-events-none">
                    <svg width="140" height="140" viewBox="0 0 80 80" style="color: {{ $spotColor }};">
                        @switch($spotShape)
                            @case('triangle')
                                <polygon points="40,5 75,75 5,75" fill="currentColor"/>
                                @break
                            @case('diamond')
                                <polygon points="40,5 75,40 40,75 5,40" fill="currentColor"/>
                                @break
                            @case('circle')
                                <circle cx="40" cy="40" r="35" fill="currentColor"/>
                                @break
                            @case('square')
                                <rect x="5" y="5" width="70" height="70" fill="currentColor"/>
                                @break
                            @case('hexagon')
                                <polygon points="40,5 70,20 70,60 40,75 10,60 10,20" fill="currentColor"/>
                                @break
                            @case('pentagon')
                                <polygon points="40,5 75,30 60,75 20,75 5,30" fill="currentColor"/>
                                @break
                            @case('octagon')
                                <polygon points="25,5 55,5 75,25 75,55 55,75 25,75 5,55 5,25" fill="currentColor"/>
                                @break
                        @endswitch
                    </svg>
                </div>

                <div class="flex items-start gap-6 relative z-10">
                    {{-- Left: Big phase number --}}
                    <div class="flex-shrink-0 hidden sm:block">
                        <span class="text-6xl font-black leading-none" style="font-family: 'JetBrains Mono', monospace; color: {{ $spotColor }}; opacity: 0.10;">
                            {{ $activePhase->phase_number->value }}
                        </span>
                    </div>

                    {{-- Center: Phase info --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <x-ui-badge size="xs" color="primary">In Bearbeitung</x-ui-badge>
                        </div>
                        <h3 class="text-lg font-bold text-[color:var(--ui-text)] mb-1" style="color: {{ $spotColor }};">
                            {{ $activePhase->phase_number->label() }}
                        </h3>
                        <p class="text-xs text-[color:var(--ui-secondary)] mb-2 line-clamp-2">
                            {{ $activePhase->phase_number->description() }}
                        </p>
                        @if($activePhase->responsible)
                            <div class="text-xs text-[color:var(--ui-secondary)]">
                                @svg('heroicon-o-user', 'w-3 h-3 inline-block')
                                {{ $activePhase->responsible }}
                            </div>
                        @endif
                    </div>

                    {{-- Right: Quick stats + CTA --}}
                    <div class="flex-shrink-0 text-right space-y-2">
                        <div class="flex items-center gap-4 text-xs">
                            <div>
                                <span class="block text-lg font-bold" style="font-family: 'JetBrains Mono', monospace; color: {{ $spotColor }};">{{ $openActions }}</span>
                                <span class="text-[color:var(--ui-secondary)]">offen</span>
                            </div>
                            <div>
                                <span class="block text-lg font-bold" style="font-family: 'JetBrains Mono', monospace; color: {{ $spotColor }};">{{ $doneActions }}</span>
                                <span class="text-[color:var(--ui-secondary)]">erledigt</span>
                            </div>
                        </div>
                        <div class="flex gap-1.5">
                            <x-ui-button variant="secondary" size="xs" wire:click="editPhase({{ $activePhase->id }})">
                                @svg('heroicon-o-pencil', 'w-3 h-3')
                            </x-ui-button>
                            <x-ui-button variant="primary" size="xs" wire:click="createAction({{ $activePhase->id }})">
                                @svg('heroicon-o-plus', 'w-3 h-3')
                                Maßnahme
                            </x-ui-button>
                        </div>
                    </div>
                </div>
            </div>
        @else
            {{-- No active phase banner --}}
            <div class="mb-8 rounded-xl border border-dashed border-black/10 bg-white/40 p-4 text-center">
                <p class="text-xs text-[color:var(--ui-secondary)]">
                    @svg('heroicon-o-information-circle', 'w-4 h-4 inline-block mb-0.5')
                    Keine Phase in Bearbeitung &mdash; starten Sie mit Phase 1
                </p>
            </div>
        @endif

        {{-- ═══════════════════════════════════════════════════════════ --}}
        {{-- PHASE CARDS GRID --}}
        {{-- ═══════════════════════════════════════════════════════════ --}}

        {{-- Stage I: Voraussetzungen schaffen (Phases 1-4) --}}
        <div class="mb-2">
            <h3 class="text-[10px] font-bold uppercase tracking-[0.15em] text-[color:var(--ui-muted)] mb-3" style="font-family: 'JetBrains Mono', monospace;">I. Voraussetzungen schaffen</h3>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            @foreach($this->phases->take(4) as $phase)
                @include('change::livewire.change-project._phase-card', ['phase' => $phase])
            @endforeach
        </div>

        {{-- Stage II: Umsetzen & Verankern (Phases 5-8) --}}
        <div class="mb-2">
            <h3 class="text-[10px] font-bold uppercase tracking-[0.15em] text-[color:var(--ui-muted)] mb-3" style="font-family: 'JetBrains Mono', monospace;">II. Umsetzen & Verankern</h3>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            @foreach($this->phases->skip(4) as $phase)
                @include('change::livewire.change-project._phase-card', ['phase' => $phase])
            @endforeach
        </div>

        {{-- ═══════════════════════════════════════════════════════════ --}}
        {{-- ACTIONS LIST --}}
        {{-- ═══════════════════════════════════════════════════════════ --}}
        <div class="mt-8">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xs font-bold uppercase tracking-[0.15em] text-[color:var(--ui-text)]" style="font-family: 'JetBrains Mono', monospace;">Alle Maßnahmen</h2>
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
                <p class="text-xs text-[color:var(--ui-secondary)]">Keine Maßnahmen vorhanden.</p>
            @else
                <div class="space-y-2">
                    @foreach($this->actions as $action)
                        @php
                            $actionPhaseColor = $action->phase ? $action->phase->phase_number->color() : null;
                        @endphp
                        <div class="flex items-center gap-3 rounded-lg border border-black/5 bg-white/60 backdrop-blur-sm px-4 py-3 {{ $actionPhaseColor ? 'border-l-[3px]' : '' }}"
                             @if($actionPhaseColor) style="border-left-color: {{ $actionPhaseColor }};" @endif>
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
                                        <span class="flex items-center gap-1">
                                            <svg width="8" height="8" viewBox="0 0 16 16" style="color: {{ $action->phase->phase_number->color() }};">
                                                @switch($action->phase->phase_number->shape())
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
                                            {{ $action->phase->phase_number->shortLabel() }}
                                        </span>
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
    </x-slot>

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
</x-ui-page>
