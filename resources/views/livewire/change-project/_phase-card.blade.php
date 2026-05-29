@php
    $phaseColor = $phase->phase_number->color();
    $phaseShape = $phase->phase_number->shape();
    $isInProgress = $phase->status->value === 'in_progress';
    $isCompleted = $phase->status->value === 'completed';
    $isBlocked = $phase->status->value === 'blocked';
@endphp

<div class="relative rounded-xl border bg-white/60 backdrop-blur-sm p-4 shadow-sm overflow-hidden border-l-[4px] transition-all duration-200
            {{ $isInProgress ? 'ring-2 ring-offset-1' : '' }}
            {{ $isCompleted ? 'ring-2 ring-offset-1' : '' }}
            {{ $isBlocked ? 'ring-2 ring-[rgb(var(--ui-danger-rgb))]/30' : '' }}
            {{ !$isInProgress && !$isCompleted && !$isBlocked ? 'border-black/5' : '' }}"
     style="border-left-color: {{ $phaseColor }};
            {{ $isInProgress ? 'ring-color: ' . $phaseColor . '40;' : '' }}
            {{ $isCompleted ? 'ring-color: ' . $phaseColor . '30;' : '' }}">

    {{-- Watermark shape (large, background) --}}
    <div class="absolute -right-4 -bottom-4 opacity-[0.06] pointer-events-none">
        <svg width="80" height="80" viewBox="0 0 80 80" style="color: {{ $phaseColor }};">
            @switch($phaseShape)
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

    {{-- Completed check overlay --}}
    @if($isCompleted)
        <div class="absolute top-2 right-2">
            <svg width="20" height="20" viewBox="0 0 20 20" style="color: {{ $phaseColor }};">
                <circle cx="10" cy="10" r="9" fill="currentColor" opacity="0.15"/>
                <path d="M6 10l3 3 5-6" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
    @endif

    {{-- In-progress pulse indicator --}}
    @if($isInProgress)
        <div class="absolute top-2 right-2">
            <span class="relative flex h-2.5 w-2.5">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75" style="background: {{ $phaseColor }};"></span>
                <span class="relative inline-flex rounded-full h-2.5 w-2.5" style="background: {{ $phaseColor }};"></span>
            </span>
        </div>
    @endif

    {{-- Phase header --}}
    <div class="flex items-start gap-3 mb-3 relative z-10">
        <span class="text-4xl font-black leading-none" style="font-family: 'JetBrains Mono', monospace; color: {{ $phaseColor }}; opacity: 0.25;">{{ $phase->phase_number->value }}</span>
        <div class="pt-1 min-w-0 flex-1">
            <div class="flex items-center gap-1.5 mb-0.5">
                @svg($phase->phase_number->icon(), 'w-3.5 h-3.5 flex-shrink-0')
                <h3 class="text-sm font-semibold text-[color:var(--ui-text)] leading-tight truncate"
                    style="color: {{ $phaseColor }};">
                    {{ $phase->phase_number->shortLabel() }}
                </h3>
            </div>
            <x-ui-badge :color="$phase->status->color()" size="xs">{{ $phase->status->label() }}</x-ui-badge>
        </div>
    </div>

    <p class="text-xs text-[color:var(--ui-secondary)] mb-3 line-clamp-2 relative z-10">
        {{ $phase->phase_number->description() }}
    </p>

    {{-- Inline edit form --}}
    @if($editingPhaseId === $phase->id)
        <div class="space-y-2 border-t border-black/5 pt-3 relative z-10">
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
        <div class="relative z-10">
            @if($phase->responsible)
                <div class="text-xs text-[color:var(--ui-secondary)] mb-1">
                    @svg('heroicon-o-user', 'w-3 h-3 inline-block')
                    {{ $phase->responsible }}
                </div>
            @endif
            @if($phase->notes)
                <div class="text-xs text-[color:var(--ui-secondary)] mb-2 bg-black/[0.03] rounded-lg p-2 line-clamp-3">
                    {{ $phase->notes }}
                </div>
            @endif

            {{-- Actions count --}}
            @if($phase->actions_count > 0)
                <div class="text-xs text-[color:var(--ui-secondary)] mb-2">
                    @svg('heroicon-o-clipboard-document-list', 'w-3 h-3 inline-block')
                    {{ $phase->actions_count }} Massnahmen
                </div>
            @endif

            {{-- Quick actions --}}
            <div class="flex items-center gap-1 border-t border-black/5 pt-2 mt-2">
                <button wire:click="editPhase({{ $phase->id }})"
                        class="text-xs text-[color:var(--ui-secondary)] hover:text-[rgb(var(--ui-primary-rgb))] transition-colors">
                    @svg('heroicon-o-pencil', 'w-3.5 h-3.5')
                </button>
                <button wire:click="createAction({{ $phase->id }})"
                        class="text-xs text-[color:var(--ui-secondary)] hover:text-[rgb(var(--ui-primary-rgb))] transition-colors"
                        title="Massnahme hinzufuegen">
                    @svg('heroicon-o-plus', 'w-3.5 h-3.5')
                </button>
                @if(!$isCompleted)
                    <button wire:click="quickUpdatePhaseStatus({{ $phase->id }}, 'completed')"
                            class="ml-auto text-xs text-[color:var(--ui-secondary)] hover:text-[rgb(var(--ui-success-rgb))] transition-colors"
                            title="Als abgeschlossen markieren">
                        @svg('heroicon-o-check-circle', 'w-3.5 h-3.5')
                    </button>
                @endif
            </div>
        </div>
    @endif
</div>
