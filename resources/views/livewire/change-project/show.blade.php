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
            <h3 class="text-[10px] font-bold uppercase tracking-[0.15em] text-[color:var(--ui-muted)] mb-2" style="font-family: 'JetBrains Mono', monospace;">Navigation</h3>
            <nav class="space-y-1">
                @php
                    $tabConfig = [
                        'board' => ['icon' => 'heroicon-o-view-columns', 'label' => 'Board'],
                        'stakeholder' => ['icon' => 'heroicon-o-user-group', 'label' => 'Stakeholder'],
                        'log' => ['icon' => 'heroicon-o-document-text', 'label' => 'Log'],
                        'settings' => ['icon' => 'heroicon-o-cog-6-tooth', 'label' => 'Einstellungen'],
                    ];
                @endphp
                @foreach($tabConfig as $tab => $cfg)
                    <button wire:click="$set('activeTab', '{{ $tab }}')"
                            class="w-full text-left px-3 py-2 rounded-lg text-sm transition-all duration-200 flex items-center gap-2 {{ $activeTab === $tab ? 'bg-[rgb(var(--ui-primary-rgb))]/10 text-[rgb(var(--ui-primary-rgb))] font-medium' : 'text-[color:var(--ui-secondary)] hover:bg-white/60' }}">
                        @svg($cfg['icon'], 'w-4 h-4')
                        {{ $cfg['label'] }}
                    </button>
                @endforeach
            </nav>
        </div>

        {{-- SVG Circle Progress --}}
        <div class="px-4 py-4 border-t border-[color:var(--ui-border)]">
            <h3 class="text-[10px] font-bold uppercase tracking-[0.15em] text-[color:var(--ui-muted)] mb-3" style="font-family: 'JetBrains Mono', monospace;">Fortschritt</h3>
            @php
                $completedPhases = $this->phases->where('status.value', 'completed')->count();
                $totalPhases = $this->phases->count();
                $progress = $totalPhases > 0 ? round(($completedPhases / $totalPhases) * 100) : 0;
                $circumference = 2 * M_PI * 36;
                $dashOffset = $circumference - ($circumference * $progress / 100);
            @endphp
            <div class="flex justify-center mb-3">
                <div class="relative">
                    <svg width="96" height="96" viewBox="0 0 96 96">
                        <circle cx="48" cy="48" r="36" fill="none" stroke="currentColor" stroke-width="6" class="text-black/5" />
                        <circle cx="48" cy="48" r="36" fill="none" stroke="currentColor" stroke-width="6"
                                class="text-[rgb(var(--ui-primary-rgb))]"
                                stroke-dasharray="{{ $circumference }}"
                                stroke-dashoffset="{{ $dashOffset }}"
                                stroke-linecap="round"
                                transform="rotate(-90 48 48)"
                                style="transition: stroke-dashoffset 0.5s ease;" />
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-lg font-bold text-[color:var(--ui-text)]" style="font-family: 'JetBrains Mono', monospace;">{{ $progress }}%</span>
                    </div>
                </div>
            </div>
            <div class="text-center text-xs text-[color:var(--ui-secondary)] mb-4">
                {{ $completedPhases }}/{{ $totalPhases }} Phasen
            </div>

            {{-- 8 Mini Bauhaus shapes --}}
            <div class="space-y-1.5">
                @foreach($this->phases as $phase)
                    @php
                        $phaseColor = $phase->phase_number->color();
                        $isActive = in_array($phase->status->value, ['completed', 'in_progress']);
                        $shapeColor = $isActive ? $phaseColor : '#D1D5DB';
                    @endphp
                    <div class="flex items-center gap-2.5 text-xs">
                        <svg width="14" height="14" viewBox="0 0 16 16" style="color: {{ $shapeColor }};">
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
                            <span class="ml-auto text-[10px]" style="color: {{ $phaseColor }};">&#10003;</span>
                        @elseif($phase->status->value === 'in_progress')
                            <span class="ml-auto w-1.5 h-1.5 rounded-full animate-pulse" style="background: {{ $phaseColor }};"></span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Key Metrics --}}
        <div class="px-4 py-4 border-t border-[color:var(--ui-border)]">
            <h3 class="text-[10px] font-bold uppercase tracking-[0.15em] text-[color:var(--ui-muted)] mb-2" style="font-family: 'JetBrains Mono', monospace;">Kennzahlen</h3>
            <div class="space-y-2 text-xs">
                <div class="flex justify-between">
                    <span class="text-[color:var(--ui-secondary)]">Maßnahmen offen</span>
                    <span class="font-medium" style="font-family: 'JetBrains Mono', monospace;">{{ $this->openActionsCount }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-[color:var(--ui-secondary)]">Stakeholder</span>
                    <span class="font-medium" style="font-family: 'JetBrains Mono', monospace;">{{ $this->stakeholders->count() }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-[color:var(--ui-secondary)]">Log-Einträge</span>
                    <span class="font-medium" style="font-family: 'JetBrains Mono', monospace;">{{ $this->totalLogsCount }}</span>
                </div>
                @if($project->target_date)
                    <div class="flex justify-between">
                        <span class="text-[color:var(--ui-secondary)]">Zieldatum</span>
                        <span class="font-medium" style="font-family: 'JetBrains Mono', monospace;">{{ $project->target_date->format('d.m.Y') }}</span>
                    </div>
                @endif
            </div>
        </div>
    </x-slot>

    <x-slot name="main">

        {{-- ═══════════════════════════════════════════════════════════ --}}
        {{-- TAB: BOARD --}}
        {{-- ═══════════════════════════════════════════════════════════ --}}
        @if($activeTab === 'board')

            {{-- ═══════════════════════════════════════════════════════ --}}
            {{-- PHASE JOURNEY BAR --}}
            {{-- ═══════════════════════════════════════════════════════ --}}
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
                                <div class="relative flex items-center justify-center w-[40px] h-[40px] {{ $isActive ? 'animate-pulse' : '' }}">
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

            {{-- ═══════════════════════════════════════════════════════ --}}
            {{-- ACTIVE PHASE SPOTLIGHT --}}
            {{-- ═══════════════════════════════════════════════════════ --}}
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

            {{-- Stage I: Voraussetzungen schaffen (Phases 1-4, warm colors) --}}
            <div class="mb-2">
                <h3 class="text-[10px] font-bold uppercase tracking-[0.15em] text-[color:var(--ui-muted)] mb-3" style="font-family: 'JetBrains Mono', monospace;">I. Voraussetzungen schaffen</h3>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                @foreach($this->phases->take(4) as $phase)
                    @include('change::livewire.change-project._phase-card', ['phase' => $phase])
                @endforeach
            </div>

            {{-- Stage II: Umsetzen & Verankern (Phases 5-8, cool colors) --}}
            <div class="mb-2">
                <h3 class="text-[10px] font-bold uppercase tracking-[0.15em] text-[color:var(--ui-muted)] mb-3" style="font-family: 'JetBrains Mono', monospace;">II. Umsetzen & Verankern</h3>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                @foreach($this->phases->skip(4) as $phase)
                    @include('change::livewire.change-project._phase-card', ['phase' => $phase])
                @endforeach
            </div>

            {{-- Actions overview below the board --}}
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


        {{-- ═══════════════════════════════════════════════════════════ --}}
        {{-- TAB: STAKEHOLDER --}}
        {{-- ═══════════════════════════════════════════════════════════ --}}
        @elseif($activeTab === 'stakeholder')
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xs font-bold uppercase tracking-[0.15em] text-[color:var(--ui-text)]" style="font-family: 'JetBrains Mono', monospace;">Stakeholder-Map</h2>
                <x-ui-button variant="primary" size="xs" wire:click="createStakeholder">
                    @svg('heroicon-o-plus', 'w-3.5 h-3.5')
                    Stakeholder
                </x-ui-button>
            </div>

            {{-- Bauhaus Influence/Support Matrix --}}
            @if($this->stakeholders->isNotEmpty())
                <div class="grid grid-cols-5 gap-px bg-black/5 rounded-lg overflow-hidden mb-6 text-xs">
                    {{-- Header row --}}
                    <div class="bg-white/80 p-2"></div>
                    @foreach(\Platform\Change\Enums\StakeholderSupport::cases() as $support)
                        <div class="bg-white/80 p-2 text-center">
                            <span class="font-bold uppercase tracking-wider text-[10px] text-[color:var(--ui-secondary)]" style="font-family: 'JetBrains Mono', monospace;">
                                {{ $support->label() }}
                            </span>
                        </div>
                    @endforeach

                    {{-- Matrix rows --}}
                    @foreach(array_reverse(\Platform\Change\Enums\StakeholderInfluence::cases()) as $influence)
                        <div class="bg-white/80 p-2 flex items-center justify-end pr-3">
                            <span class="font-bold uppercase tracking-wider text-[10px] text-[color:var(--ui-secondary)]" style="font-family: 'JetBrains Mono', monospace;">
                                {{ $influence->label() }}
                            </span>
                        </div>
                        @foreach(\Platform\Change\Enums\StakeholderSupport::cases() as $support)
                            @php
                                $cellStakeholders = $this->stakeholders->filter(fn($s) =>
                                    ($s->influence_level->value ?? $s->influence_level) === $influence->value &&
                                    ($s->support_level->value ?? $s->support_level) === $support->value
                                );
                                // Zone coloring: red for high-influence+resistant/blocker, green for high-influence+champion/supporter
                                $isHighInfluence = in_array($influence->value, ['high', 'critical']);
                                $isResistant = in_array($support->value, ['resistant', 'blocker']);
                                $isChampion = in_array($support->value, ['champion', 'supporter']);
                                $zoneBg = 'bg-white/60';
                                if ($isHighInfluence && $isResistant) {
                                    $zoneBg = 'bg-red-50/80';
                                } elseif ($isHighInfluence && $isChampion) {
                                    $zoneBg = 'bg-green-50/80';
                                }
                            @endphp
                            <div class="{{ $zoneBg }} p-1.5 min-h-[3rem]">
                                @foreach($cellStakeholders as $s)
                                    <button wire:click="editStakeholder({{ $s->id }})"
                                            class="block w-full text-left px-1.5 py-0.5 rounded text-[10px] mb-0.5 truncate
                                                   bg-black/5 hover:bg-black/10 transition-colors font-medium">
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
                        <div class="flex items-center gap-3 rounded-lg border border-black/5 bg-white/60 backdrop-blur-sm px-4 py-3">
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
                <h2 class="text-xs font-bold uppercase tracking-[0.15em] text-[color:var(--ui-text)]" style="font-family: 'JetBrains Mono', monospace;">Change-Log</h2>
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
                        <div class="relative pl-6 border-l-2 border-black/10 pb-4 last:pb-0">
                            {{-- Bauhaus timeline shape --}}
                            <div class="absolute -left-[8px] top-0.5">
                                @if($log->phase)
                                    <svg width="14" height="14" viewBox="0 0 16 16" style="color: {{ $log->phase->phase_number->color() }};">
                                        @switch($log->phase->phase_number->shape())
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
                                @else
                                    <div class="w-3 h-3 rounded-full border-2 border-white bg-[rgb(var(--ui-{{ $log->type->color() }}-rgb))]"></div>
                                @endif
                            </div>

                            <div class="rounded-lg border border-black/5 bg-white/60 backdrop-blur-sm p-4">
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
                                    <span style="font-family: 'JetBrains Mono', monospace;">{{ $log->created_at->format('d.m.Y H:i') }}</span>
                                    @if($log->user)
                                        <span>{{ $log->user->name }}</span>
                                    @endif
                                    @if($log->phase)
                                        <span class="flex items-center gap-1">
                                            <svg width="8" height="8" viewBox="0 0 16 16" style="color: {{ $log->phase->phase_number->color() }};">
                                                @switch($log->phase->phase_number->shape())
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
                                            {{ $log->phase->phase_number->shortLabel() }}
                                        </span>
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
                    <p class="text-xs text-[color:var(--ui-secondary)] mb-4">Das Löschen eines Projekts entfernt alle Phasen, Stakeholder, Maßnahmen und Log-Einträge.</p>
                    <x-ui-button variant="danger" size="sm" wire:click="delete" wire:confirm="Projekt und alle zugehoerigen Daten wirklich löschen?">
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
