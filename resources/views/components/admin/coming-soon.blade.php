@props([
    'title' => 'Section',
    'phase' => 'Phase ?',
    'description' => 'This admin module is being built next.',
    'icon' => 'sparkles',
])

<div class="flex h-full w-full flex-1 flex-col gap-6 p-2">
    <div>
        <flux:heading size="xl">{{ $title }}</flux:heading>
        <flux:subheading>{{ $description }}</flux:subheading>
    </div>

    <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
        <flux:icon :name="$icon" class="mx-auto size-10 text-zinc-400" />
        <flux:heading size="lg" class="mt-4">{{ __('Building this module') }}</flux:heading>
        <flux:text class="mt-1 text-zinc-500">{{ __('Will land in') }} <span class="font-semibold text-emerald-500">{{ $phase }}</span>.</flux:text>
        <flux:text size="sm" class="mt-4 text-zinc-400">
            {{ __('The data is already in the database — only the editing UI is pending.') }}
        </flux:text>
        <div class="mt-6">
            <flux:button :href="route('admin.dashboard')" wire:navigate icon="arrow-left">{{ __('Back to dashboard') }}</flux:button>
        </div>
    </div>
</div>
