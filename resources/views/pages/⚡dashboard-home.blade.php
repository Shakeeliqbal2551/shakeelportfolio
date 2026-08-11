<?php

use App\Models\Portfolio;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component {
    public int $portfolioId;

    public function mount(): void
    {
        $portfolio = Auth::user()->portfolio;

        abort_if(! $portfolio, 404);

        $this->portfolioId = $portfolio->id;
    }

    public function getPortfolioProperty(): Portfolio
    {
        return Portfolio::withCount(['projects', 'services', 'testimonials', 'experiences', 'skills'])
            ->findOrFail($this->portfolioId);
    }

    public function getStatsProperty(): array
    {
        $portfolio = $this->portfolio;

        return [
            'projects' => $portfolio->projects_count,
            'publishedPosts' => $portfolio->posts()->published()->count(),
            'draftPosts' => $portfolio->posts()->whereNull('published_at')->count(),
            'testimonials' => $portfolio->testimonials_count,
            'messagesTotal' => $portfolio->contactMessages()->count(),
            'messagesLast7Days' => $portfolio->contactMessages()->where('submission_time', '>=', now()->subDays(7))->count(),
            'visitsLast7Days' => $portfolio->visitorLogs()->where('visit_time', '>=', now()->subDays(7))->count(),
            'visitsTotal' => $portfolio->visitorLogs()->count(),
        ];
    }

    public function getRecentMessagesProperty()
    {
        return $this->portfolio->contactMessages()
            ->orderByDesc('submission_time')
            ->limit(5)
            ->get();
    }

    public function getRecentVisitsProperty()
    {
        return $this->portfolio->visitorLogs()
            ->orderByDesc('visit_time')
            ->limit(5)
            ->get();
    }
}; ?>

<section class="w-full">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Dashboard') }}</flux:heading>
            <flux:subheading>{{ __('Welcome back, :name', ['name' => Auth::user()->name]) }}</flux:subheading>
        </div>
        <flux:button variant="primary" icon="eye" :href="route('portfolio.show', $this->portfolio)" target="_blank">
            {{ __('View Live Site') }}
        </flux:button>
    </div>

    <div class="my-6 stat-grid">
        <a href="{{ route('portfolio.projects') }}" wire:navigate class="dash-card-link">
            <flux:text class="text-zinc-500">{{ __('Projects') }}</flux:text>
            <flux:heading size="lg">{{ number_format($this->stats['projects']) }}</flux:heading>
        </a>
        <a href="{{ route('portfolio.posts') }}" wire:navigate class="dash-card-link">
            <flux:text class="text-zinc-500">{{ __('Published Posts') }}</flux:text>
            <flux:heading size="lg">{{ number_format($this->stats['publishedPosts']) }}</flux:heading>
            @if ($this->stats['draftPosts'] > 0)
                <flux:text class="text-xs text-zinc-400">{{ trans_choice(':count draft|:count drafts', $this->stats['draftPosts'], ['count' => $this->stats['draftPosts']]) }}</flux:text>
            @endif
        </a>
        <div class="dash-card">
            <flux:text class="text-zinc-500">{{ __('Messages (7 days)') }}</flux:text>
            <flux:heading size="lg">{{ number_format($this->stats['messagesLast7Days']) }}</flux:heading>
            <flux:text class="text-xs text-zinc-400">{{ number_format($this->stats['messagesTotal']) }} {{ __('total') }}</flux:text>
        </div>
        <a href="{{ route('portfolio.visitors') }}" wire:navigate class="dash-card-link">
            <flux:text class="text-zinc-500">{{ __('Visits (7 days)') }}</flux:text>
            <flux:heading size="lg">{{ number_format($this->stats['visitsLast7Days']) }}</flux:heading>
            <flux:text class="text-xs text-zinc-400">{{ number_format($this->stats['visitsTotal']) }} {{ __('total') }}</flux:text>
        </a>
    </div>

    <div class="stat-grid-2col">
        <div class="dash-card dash-card-flush">
            <div class="dash-card-header">
                <flux:heading size="sm">{{ __('Recent Messages') }}</flux:heading>
                <flux:link :href="route('portfolio.visitors')" wire:navigate class="text-xs">{{ __('View all') }}</flux:link>
            </div>
            <div class="dash-divide">
                @forelse ($this->recentMessages as $message)
                    <div class="p-3">
                        <div class="flex items-center justify-between">
                            <flux:text class="dash-text-primary font-medium">{{ $message->name }}</flux:text>
                            <flux:text class="text-xs text-zinc-400">{{ $message->submission_time?->diffForHumans() }}</flux:text>
                        </div>
                        <flux:text class="text-zinc-500">{{ Illuminate\Support\Str::limit($message->message, 90) }}</flux:text>
                    </div>
                @empty
                    <div class="p-6 text-center">
                        <flux:text class="text-zinc-500">{{ __('No messages yet.') }}</flux:text>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="dash-card dash-card-flush">
            <div class="dash-card-header">
                <flux:heading size="sm">{{ __('Recent Visits') }}</flux:heading>
                <flux:link :href="route('portfolio.visitors')" wire:navigate class="text-xs">{{ __('View all') }}</flux:link>
            </div>
            <div class="dash-divide">
                @forelse ($this->recentVisits as $visit)
                    <div class="flex items-center justify-between p-3">
                        <flux:text class="dash-text-primary">
                            {{ collect([$visit->city, $visit->country])->filter(fn ($v) => $v && $v !== 'Unknown')->implode(', ') ?: '—' }}
                        </flux:text>
                        <flux:text class="text-xs text-zinc-400">{{ $visit->visit_time?->diffForHumans() }}</flux:text>
                    </div>
                @empty
                    <div class="p-6 text-center">
                        <flux:text class="text-zinc-500">{{ __('No visits recorded yet.') }}</flux:text>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</section>
