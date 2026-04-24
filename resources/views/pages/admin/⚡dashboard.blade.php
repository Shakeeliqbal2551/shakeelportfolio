<?php

use App\Models\ContactMessage;
use App\Models\Post;
use App\Models\Project;
use App\Models\Service;
use App\Models\Skill;
use App\Models\Testimonial;
use App\Models\VisitorLog;
use App\Support\PortfolioContext;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.app')]
#[Title('Admin · Dashboard')]
class extends Component
{
    #[Computed]
    public function portfolio()
    {
        return PortfolioContext::current();
    }

    #[Computed]
    public function stats(): array
    {
        $pid = $this->portfolio?->id;

        return [
            ['label' => 'Projects',       'value' => Project::where('portfolio_id', $pid)->count(),       'icon' => 'briefcase',     'href' => route('admin.projects.index')],
            ['label' => 'Skills',         'value' => Skill::where('portfolio_id', $pid)->count(),         'icon' => 'academic-cap',  'href' => route('admin.skills.index')],
            ['label' => 'Services',       'value' => Service::where('portfolio_id', $pid)->count(),       'icon' => 'wrench-screwdriver', 'href' => route('admin.services')],
            ['label' => 'Testimonials',   'value' => Testimonial::where('portfolio_id', $pid)->count(),   'icon' => 'star',          'href' => route('admin.testimonials')],
            ['label' => 'Blog Posts',     'value' => Post::where('portfolio_id', $pid)->count(),          'icon' => 'document-text', 'href' => route('admin.blog.posts')],
            ['label' => 'Unread Messages','value' => ContactMessage::where('portfolio_id', $pid)->orWhereNull('portfolio_id')->where('is_read', false)->count(), 'icon' => 'envelope',  'href' => route('admin.contact-messages')],
            ['label' => 'Visitors (30d)', 'value' => VisitorLog::where('visit_time', '>=', now()->subDays(30))->count(), 'icon' => 'chart-bar', 'href' => route('admin.visitor-logs')],
            ['label' => 'Visitors (today)','value' => VisitorLog::whereDate('visit_time', today())->count(), 'icon' => 'sparkles',     'href' => route('admin.visitor-logs')],
        ];
    }

    #[Computed]
    public function recentMessages()
    {
        return ContactMessage::latest()->limit(6)->get();
    }

    #[Computed]
    public function recentVisitors()
    {
        return VisitorLog::latest('visit_time')->limit(8)->get();
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-2">

    <!-- Hero greeting -->
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Welcome back,') }} {{ auth()->user()->name }}</flux:heading>
            <flux:subheading>
                {{ __('Managing portfolio:') }}
                <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $this->portfolio?->display_name }}</span>
                ·
                <a href="{{ url('/') }}" target="_blank" class="text-emerald-500 hover:underline">{{ __('view live site') }} ↗</a>
            </flux:subheading>
        </div>
        <div class="flex gap-2">
            <flux:button :href="route('admin.projects.create')" wire:navigate variant="primary" icon="plus">
                {{ __('New Project') }}
            </flux:button>
            <flux:button :href="route('admin.blog.posts.create')" wire:navigate icon="pencil-square">
                {{ __('New Post') }}
            </flux:button>
        </div>
    </div>

    <!-- Stat grid -->
    <div class="grid gap-3 grid-cols-2 md:grid-cols-4">
        @foreach ($this->stats as $stat)
            <a href="{{ $stat['href'] }}" wire:navigate class="group rounded-xl border border-zinc-200 bg-white p-4 transition hover:border-emerald-400 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-emerald-500">
                <div class="flex items-center justify-between">
                    <flux:text size="sm" class="uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ $stat['label'] }}</flux:text>
                    <flux:icon :name="$stat['icon']" class="size-4 text-zinc-400 group-hover:text-emerald-500" />
                </div>
                <div class="mt-3 text-3xl font-semibold text-zinc-900 dark:text-zinc-50">{{ $stat['value'] }}</div>
            </a>
        @endforeach
    </div>

    <!-- Two-column lower section -->
    <div class="grid gap-4 lg:grid-cols-3">

        <!-- Recent Messages -->
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900 lg:col-span-2">
            <div class="mb-4 flex items-center justify-between">
                <flux:heading size="lg">{{ __('Recent Contact Messages') }}</flux:heading>
                <flux:link :href="route('admin.contact-messages')" wire:navigate>{{ __('View all') }} →</flux:link>
            </div>

            @forelse ($this->recentMessages as $message)
                <div class="flex items-start justify-between gap-4 border-b border-zinc-100 py-3 last:border-0 dark:border-zinc-800">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <flux:text class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $message->name }}</flux:text>
                            @if (! $message->is_read)
                                <flux:badge color="emerald" size="sm">{{ __('NEW') }}</flux:badge>
                            @endif
                        </div>
                        <flux:text size="sm" class="text-zinc-500">{{ $message->email }}</flux:text>
                        <p class="mt-1 line-clamp-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $message->message }}</p>
                    </div>
                    <flux:text size="xs" class="shrink-0 text-zinc-400">{{ $message->created_at?->diffForHumans() }}</flux:text>
                </div>
            @empty
                <flux:text class="py-6 text-center text-zinc-500">{{ __('No contact messages yet.') }}</flux:text>
            @endforelse
        </div>

        <!-- Recent Visitors -->
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-4 flex items-center justify-between">
                <flux:heading size="lg">{{ __('Recent Visitors') }}</flux:heading>
                <flux:link :href="route('admin.visitor-logs')" wire:navigate>{{ __('View all') }} →</flux:link>
            </div>

            @forelse ($this->recentVisitors as $visitor)
                <div class="flex items-center justify-between border-b border-zinc-100 py-2 last:border-0 dark:border-zinc-800">
                    <div class="min-w-0">
                        <flux:text size="sm" class="truncate font-medium text-zinc-700 dark:text-zinc-300">
                            {{ $visitor->city ?: '—' }}{{ $visitor->country ? ', '.$visitor->country : '' }}
                        </flux:text>
                        <flux:text size="xs" class="text-zinc-500">{{ $visitor->page_visited }} · {{ $visitor->browser ?? 'unknown' }}</flux:text>
                    </div>
                    <flux:text size="xs" class="text-zinc-400">{{ $visitor->visit_time?->diffForHumans() }}</flux:text>
                </div>
            @empty
                <flux:text class="py-6 text-center text-zinc-500">{{ __('No visits yet.') }}</flux:text>
            @endforelse
        </div>
    </div>

</div>
