<div>
    {{-- Modul Header --}}
    <x-sidebar-module-header module-name="Change" />

    {{-- Abschnitt: Allgemein --}}
    <div>
        <h4 x-show="!collapsed" class="px-4 py-3 text-xs tracking-wide font-semibold text-gray-400 uppercase">Allgemein</h4>

        {{-- Projekte --}}
        <a href="{{ route('change.projects.index') }}"
           class="relative flex items-center px-3 py-2 my-1 rounded-md font-medium transition"
           :class="[
               window.location.pathname.includes('/projects')
                   ? 'bg-gray-900 text-white shadow'
                   : 'text-gray-900 hover:bg-gray-100',
               collapsed ? 'justify-center' : 'gap-3'
           ]"
           wire:navigate>
            <x-heroicon-o-rectangle-stack class="w-6 h-6 flex-shrink-0"/>
            <span x-show="!collapsed" class="truncate">Change-Projekte</span>
        </a>
    </div>

    {{-- Abschnitt: Letzte Projekte --}}
    <div x-show="!collapsed">
        @if($recentProjects->isNotEmpty())
            <h4 class="px-4 py-3 text-xs tracking-wide font-semibold text-gray-400 uppercase">Letzte Projekte</h4>

            @foreach($recentProjects as $project)
                <a href="{{ route('change.projects.show', ['project' => $project]) }}"
                   class="relative flex items-center px-3 py-2 my-1 rounded-md font-medium transition gap-3"
                   :class="[
                       window.location.pathname.endsWith('/projects/{{ $project->id }}')
                           ? 'bg-gray-900 text-white shadow'
                           : 'text-gray-900 hover:bg-gray-100'
                   ]"
                   wire:navigate>
                    <x-heroicon-o-arrows-right-left class="w-6 h-6 flex-shrink-0"/>
                    <span class="truncate">{{ $project->name }}</span>
                </a>
                {{-- Board-Link (Journey View) --}}
                <a href="{{ route('change.projects.board', ['project' => $project]) }}"
                   class="relative flex items-center px-3 py-2 my-1 ml-4 rounded-md text-sm transition gap-3"
                   :class="[
                       window.location.pathname.endsWith('/projects/{{ $project->id }}/board')
                           ? 'bg-gray-900 text-white shadow font-medium'
                           : 'text-gray-500 hover:bg-gray-100 hover:text-gray-900'
                   ]"
                   wire:navigate>
                    <x-heroicon-o-view-columns class="w-5 h-5 flex-shrink-0"/>
                    <span class="truncate">Board</span>
                </a>
            @endforeach
        @endif
    </div>
</div>
