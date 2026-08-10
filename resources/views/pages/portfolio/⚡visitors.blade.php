<?php

use App\Models\VisitorLog;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public int $portfolioId;

    public function mount(): void
    {
        $portfolio = Auth::user()->portfolio;

        abort_if(! $portfolio, 404);

        $this->portfolioId = $portfolio->id;
    }

    public function getStatsProperty(): array
    {
        $base = VisitorLog::where('portfolio_id', $this->portfolioId);

        $totalVisits = (clone $base)->count();
        $uniqueVisitors = (clone $base)->distinct('ip_address')->count('ip_address');
        $last7Days = (clone $base)->where('visit_time', '>=', now()->subDays(7))->count();
        $avgDurationSeconds = (clone $base)->whereNotNull('duration_seconds')->where('duration_seconds', '>', 0)->avg('duration_seconds');

        $topCities = (clone $base)
            ->whereNotNull('city')
            ->where('city', '!=', 'Unknown')
            ->where('city', '!=', 'Local')
            ->selectRaw('city, country, count(*) as visits')
            ->groupBy('city', 'country')
            ->orderByDesc('visits')
            ->limit(5)
            ->get();

        return [
            'totalVisits' => $totalVisits,
            'uniqueVisitors' => $uniqueVisitors,
            'last7Days' => $last7Days,
            'avgDuration' => $avgDurationSeconds ? $this->formatDuration((int) round($avgDurationSeconds)) : '—',
            'topCities' => $topCities,
        ];
    }

    public function getVisitsProperty()
    {
        return VisitorLog::where('portfolio_id', $this->portfolioId)
            ->orderByDesc('visit_time')
            ->paginate(20);
    }

    public function formatDuration(?int $seconds): string
    {
        if (! $seconds) {
            return '—';
        }

        $minutes = intdiv($seconds, 60);
        $remaining = $seconds % 60;

        return "{$minutes}m {$remaining}s";
    }
}; ?>

<section class="w-full">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Visitors') }}</flux:heading>
            <flux:subheading>{{ __('Analytics for your portfolio visitors') }}</flux:subheading>
        </div>
    </div>

    <div class="my-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:text class="text-zinc-500">{{ __('Total Visits') }}</flux:text>
            <flux:heading size="lg">{{ number_format($this->stats['totalVisits']) }}</flux:heading>
        </div>
        <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:text class="text-zinc-500">{{ __('Unique Visitors') }}</flux:text>
            <flux:heading size="lg">{{ number_format($this->stats['uniqueVisitors']) }}</flux:heading>
        </div>
        <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:text class="text-zinc-500">{{ __('Last 7 Days') }}</flux:text>
            <flux:heading size="lg">{{ number_format($this->stats['last7Days']) }}</flux:heading>
        </div>
        <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:text class="text-zinc-500">{{ __('Avg. Time on Site') }}</flux:text>
            <flux:heading size="lg">{{ $this->stats['avgDuration'] }}</flux:heading>
        </div>
    </div>

    <div class="mb-6 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
        <flux:heading size="sm" class="mb-3">{{ __('Top Locations') }}</flux:heading>
        @forelse ($this->stats['topCities'] as $location)
            <div class="flex items-center justify-between border-b border-zinc-100 py-2 last:border-b-0 dark:border-zinc-800">
                <flux:text>{{ $location->city }}{{ $location->country && $location->country !== 'Unknown' ? ', '.$location->country : '' }}</flux:text>
                <flux:badge size="sm" color="zinc">{{ $location->visits }} {{ __('visits') }}</flux:badge>
            </div>
        @empty
            <flux:text class="text-zinc-500">{{ __('No location data yet.') }}</flux:text>
        @endforelse
    </div>

    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
                <tr>
                    <th class="px-4 py-2 font-medium text-zinc-500">{{ __('Location') }}</th>
                    <th class="px-4 py-2 font-medium text-zinc-500">{{ __('Device') }}</th>
                    <th class="px-4 py-2 font-medium text-zinc-500">{{ __('Browser') }}</th>
                    <th class="px-4 py-2 font-medium text-zinc-500">{{ __('Time') }}</th>
                    <th class="px-4 py-2 font-medium text-zinc-500">{{ __('Duration') }}</th>
                    <th class="px-4 py-2 font-medium text-zinc-500">{{ __('Repeat') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->visits as $visit)
                    <tr class="border-b border-zinc-100 last:border-b-0 dark:border-zinc-800">
                        <td class="px-4 py-2">
                            {{ collect([$visit->city, $visit->region, $visit->country])->filter(fn ($v) => $v && $v !== 'Unknown')->implode(', ') ?: '—' }}
                        </td>
                        <td class="px-4 py-2">{{ $visit->device_type ?? '—' }}</td>
                        <td class="px-4 py-2">{{ $visit->browser ?? '—' }}</td>
                        <td class="px-4 py-2">{{ $visit->visit_time?->diffForHumans() ?? '—' }}</td>
                        <td class="px-4 py-2">{{ $this->formatDuration($visit->duration_seconds) }}</td>
                        <td class="px-4 py-2">
                            @if ($visit->is_repeat_visitor)
                                <flux:badge size="sm" color="blue">{{ __('Repeat') }}</flux:badge>
                            @else
                                <flux:text class="text-zinc-400">—</flux:text>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-zinc-500">{{ __('No visits recorded yet.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $this->visits->links() }}
    </div>
</section>
