@props([
    'tabs'      => [],          // [['name'=>'hero', 'label'=>'Hero', 'icon'=>'bolt', 'badge'=>null, 'disabled'=>false], ...]
    'active'    => '',
    'wireModel' => 'activeTab',
])

<div role="tablist" class="flex flex-wrap items-center gap-1 border-b border-zinc-200 dark:border-zinc-700 mb-2">
    @foreach ($tabs as $tab)
        @php
            $isActive   = $active === ($tab['name'] ?? '');
            $isDisabled = ! empty($tab['disabled']);
        @endphp
        <button
            type="button"
            role="tab"
            @aria-selected="$isActive ? 'true' : 'false'"
            @disabled($isDisabled)
            @if (! $isDisabled) wire:click="$set('{{ $wireModel }}', '{{ $tab['name'] }}')" @endif
            class="-mb-px inline-flex items-center gap-2 border-b-2 px-3 py-2.5 text-sm font-medium transition
                {{ $isActive
                    ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400'
                    : 'border-transparent text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100' }}
                {{ $isDisabled ? 'cursor-not-allowed opacity-40' : 'cursor-pointer' }}"
        >
            @if (! empty($tab['icon']))
                <flux:icon :name="$tab['icon']" class="size-4" />
            @endif
            <span>{{ $tab['label'] ?? $tab['name'] }}</span>
            @if (isset($tab['badge']))
                <flux:badge size="sm" color="zinc">{{ $tab['badge'] }}</flux:badge>
            @endif
        </button>
    @endforeach
</div>
