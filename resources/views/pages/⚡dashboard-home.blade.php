<?php

use App\Models\ContactMessage;
use App\Models\Portfolio;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component {
    public int $portfolioId;

    public ?ContactMessage $viewingMessage = null;

    public ?int $deletingMessageId = null;

    public function mount(): void
    {
        $portfolio = Auth::user()->portfolio;

        abort_if(! $portfolio, 404);

        $this->portfolioId = $portfolio->id;
    }

    public function viewMessage(int $id): void
    {
        $this->viewingMessage = $this->portfolio->contactMessages()->findOrFail($id);

        $this->modal('message-detail')->show();
    }

    public function confirmDeleteMessage(int $id): void
    {
        $this->deletingMessageId = $id;

        $this->modal('message-detail')->close();
        $this->modal('message-delete')->show();
    }

    public function deleteMessage(): void
    {
        if ($this->deletingMessageId) {
            $this->portfolio->contactMessages()->where('id', $this->deletingMessageId)->delete();
        }

        $this->deletingMessageId = null;
        $this->viewingMessage = null;
        $this->modal('message-delete')->close();
        $this->dispatch('message-deleted');
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

    <div class="mb-2">
        <x-action-message on="message-deleted">{{ __('Message deleted.') }}</x-action-message>
    </div>

    <div class="stat-grid-2col">
        <div class="dash-card dash-card-flush">
            <div class="dash-card-header">
                <flux:heading size="sm">{{ __('Recent Messages') }}</flux:heading>
                <flux:link :href="route('portfolio.visitors')" wire:navigate class="text-xs">{{ __('View all') }}</flux:link>
            </div>
            <div class="dash-divide">
                @forelse ($this->recentMessages as $message)
                    <button type="button" wire:click="viewMessage({{ $message->id }})" class="w-full p-3 text-left transition hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                        <div class="flex items-center justify-between">
                            <flux:text class="dash-text-primary font-medium">{{ $message->name }}</flux:text>
                            <flux:text class="text-xs text-zinc-400">{{ $message->submission_time?->diffForHumans() }}</flux:text>
                        </div>
                        <flux:text class="text-zinc-500">{{ Illuminate\Support\Str::limit($message->message, 90) }}</flux:text>
                    </button>
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

    <flux:modal name="message-detail" class="max-w-lg">
        @if ($viewingMessage)
            <div class="space-y-4">
                <flux:heading size="lg">{{ __('Message Details') }}</flux:heading>

                <div class="space-y-3">
                    <div>
                        <flux:text class="text-xs text-zinc-400">{{ __('From') }}</flux:text>
                        <flux:text class="dash-text-primary font-medium">{{ $viewingMessage->name }}</flux:text>
                    </div>

                    @if ($viewingMessage->email)
                        <div>
                            <flux:text class="text-xs text-zinc-400">{{ __('Email') }}</flux:text>
                            <flux:text class="dash-text-primary">
                                <a href="mailto:{{ $viewingMessage->email }}" class="dash-link">{{ $viewingMessage->email }}</a>
                            </flux:text>
                        </div>
                    @endif

                    @if ($viewingMessage->phone)
                        <div>
                            <flux:text class="text-xs text-zinc-400">{{ __('Phone') }}</flux:text>
                            <flux:text class="dash-text-primary">{{ $viewingMessage->phone }}</flux:text>
                        </div>
                    @endif

                    <div>
                        <flux:text class="text-xs text-zinc-400">{{ __('Received') }}</flux:text>
                        <flux:text class="dash-text-primary">
                            {{ $viewingMessage->submission_time?->format('M j, Y g:i A') }}
                            <span class="text-zinc-400">({{ $viewingMessage->submission_time?->diffForHumans() }})</span>
                        </flux:text>
                    </div>

                    <div>
                        <flux:text class="text-xs text-zinc-400">{{ __('Message') }}</flux:text>
                        <flux:text class="dash-text-primary whitespace-pre-wrap">{{ $viewingMessage->message }}</flux:text>
                    </div>
                </div>

                @php
                    $geoParts = collect([$viewingMessage->city, $viewingMessage->region, $viewingMessage->country])
                        ->filter(fn ($v) => $v && $v !== 'Unknown' && $v !== 'Local');
                    $geoLabel = $geoParts->isNotEmpty() ? $geoParts->implode(', ') : '—';
                    $hasCoords = filled($viewingMessage->latitude) && filled($viewingMessage->longitude);
                    $mapLink = $hasCoords ? "https://maps.google.com/?q={$viewingMessage->latitude},{$viewingMessage->longitude}" : null;
                @endphp

                <div class="dash-card space-y-3">
                    <flux:heading size="sm">{{ __('Visitor Details') }}</flux:heading>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <flux:text class="text-xs text-zinc-400">{{ __('IP Address') }}</flux:text>
                            <flux:text class="dash-text-primary font-mono text-xs">{{ $viewingMessage->ip_address ?? '—' }}</flux:text>
                        </div>
                        <div>
                            <flux:text class="text-xs text-zinc-400">{{ __('Location') }}</flux:text>
                            <flux:text class="dash-text-primary">
                                {{ $geoLabel }}
                                @if ($mapLink)
                                    · <a href="{{ $mapLink }}" target="_blank" rel="noopener noreferrer" class="dash-link">{{ __('Map') }}</a>
                                @endif
                            </flux:text>
                        </div>
                        <div>
                            <flux:text class="text-xs text-zinc-400">{{ __('Browser') }}</flux:text>
                            <flux:text class="dash-text-primary">{{ $viewingMessage->browser ?? '—' }}</flux:text>
                        </div>
                        <div>
                            <flux:text class="text-xs text-zinc-400">{{ __('OS') }}</flux:text>
                            <flux:text class="dash-text-primary">{{ $viewingMessage->os ?? '—' }}</flux:text>
                        </div>
                        <div>
                            <flux:text class="text-xs text-zinc-400">{{ __('Device') }}</flux:text>
                            <flux:text class="dash-text-primary">{{ $viewingMessage->device_type ?? '—' }}</flux:text>
                        </div>
                    </div>

                    @if ($viewingMessage->user_agent)
                        <div>
                            <flux:text class="text-xs text-zinc-400">{{ __('User Agent') }}</flux:text>
                            <flux:text class="dash-text-primary text-xs break-all">{{ $viewingMessage->user_agent }}</flux:text>
                        </div>
                    @endif
                </div>

                <div class="flex justify-between">
                    <flux:button variant="danger" icon="trash" wire:click="confirmDeleteMessage({{ $viewingMessage->id }})">
                        {{ __('Delete') }}
                    </flux:button>

                    <flux:modal.close>
                        <flux:button variant="filled">{{ __('Close') }}</flux:button>
                    </flux:modal.close>
                </div>
            </div>
        @endif
    </flux:modal>

    <flux:modal name="message-delete" class="max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Delete this message?') }}</flux:heading>
            <flux:text>{{ __("This will move the message to trash. It won't appear in your inbox anymore.") }}</flux:text>

            <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="deleteMessage">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
