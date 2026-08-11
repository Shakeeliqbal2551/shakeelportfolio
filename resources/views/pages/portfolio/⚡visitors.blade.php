<?php

use App\Models\VisitorLog;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public int $portfolioId;

    #[Url]
    public string $search = '';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    #[Url]
    public string $perPage = '20';

    public ?int $deletingId = null;

    public ?string $deletingIp = null;

    public int $deletingIpCount = 0;

    public function mount(): void
    {
        $portfolio = Auth::user()->portfolio;

        abort_if(! $portfolio, 404);

        $this->portfolioId = $portfolio->id;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function clearDateRange(): void
    {
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
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
        $query = VisitorLog::where('portfolio_id', $this->portfolioId)
            ->when($this->search !== '', function ($query) {
                $term = '%'.$this->search.'%';

                $query->where(function ($query) use ($term) {
                    $query->where('ip_address', 'like', $term)
                        ->orWhere('country', 'like', $term)
                        ->orWhere('city', 'like', $term)
                        ->orWhere('region', 'like', $term)
                        ->orWhere('browser', 'like', $term);
                });
            })
            ->when($this->dateFrom !== '', fn ($query) => $query->whereDate('visit_time', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($query) => $query->whereDate('visit_time', '<=', $this->dateTo))
            ->orderByDesc('visit_time');

        if ($this->perPage === 'all') {
            $total = (clone $query)->count();
            $capped = min($total > 0 ? $total : 1, 2000);

            return $query->paginate($capped);
        }

        return $query->paginate((int) $this->perPage);
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

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->modal('visitor-delete')->show();
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            VisitorLog::where('portfolio_id', $this->portfolioId)
                ->where('id', $this->deletingId)
                ->delete();
        }

        $this->deletingId = null;
        $this->modal('visitor-delete')->close();
        $this->resetPage();
        $this->dispatch('visitor-deleted');
    }

    public function confirmDeleteIp(string $ip): void
    {
        $this->deletingIp = $ip;
        $this->deletingIpCount = VisitorLog::where('portfolio_id', $this->portfolioId)
            ->where('ip_address', $ip)
            ->count();

        $this->modal('visitor-delete-ip')->show();
    }

    public function deleteIp(): void
    {
        if ($this->deletingIp) {
            VisitorLog::where('portfolio_id', $this->portfolioId)
                ->where('ip_address', $this->deletingIp)
                ->delete();
        }

        $this->deletingIp = null;
        $this->deletingIpCount = 0;
        $this->modal('visitor-delete-ip')->close();
        $this->resetPage();
        $this->dispatch('visitor-deleted');
    }
}; ?>

<section class="w-full">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Visitors') }}</flux:heading>
            <flux:subheading>{{ __('Analytics for your portfolio visitors') }}</flux:subheading>
        </div>
    </div>

    <div class="my-4 stat-grid">
        <div class="dash-card">
            <flux:text size="sm" class="text-zinc-500">{{ __('Total Visits') }}</flux:text>
            <flux:heading class="text-xl font-semibold">{{ number_format($this->stats['totalVisits']) }}</flux:heading>
        </div>
        <div class="dash-card">
            <flux:text size="sm" class="text-zinc-500">{{ __('Unique Visitors') }}</flux:text>
            <flux:heading class="text-xl font-semibold">{{ number_format($this->stats['uniqueVisitors']) }}</flux:heading>
        </div>
        <div class="dash-card">
            <flux:text size="sm" class="text-zinc-500">{{ __('Last 7 Days') }}</flux:text>
            <flux:heading class="text-xl font-semibold">{{ number_format($this->stats['last7Days']) }}</flux:heading>
        </div>
        <div class="dash-card">
            <flux:text size="sm" class="text-zinc-500">{{ __('Avg. Time on Site') }}</flux:text>
            <flux:heading class="text-xl font-semibold">{{ $this->stats['avgDuration'] }}</flux:heading>
        </div>
    </div>

    <div class="dash-card mb-4">
        <flux:heading size="sm" class="mb-2">{{ __('Top Locations') }}</flux:heading>
        @forelse ($this->stats['topCities'] as $location)
            <div class="dash-list-item">
                <flux:text size="sm">{{ $location->city }}{{ $location->country && $location->country !== 'Unknown' ? ', '.$location->country : '' }}</flux:text>
                <flux:badge size="sm" color="zinc">{{ $location->visits }} {{ __('visits') }}</flux:badge>
            </div>
        @empty
            <flux:text class="text-zinc-500">{{ __('No location data yet.') }}</flux:text>
        @endforelse
    </div>

    <div class="dash-card mb-4">
        <div class="dash-filters-bar">
            <div class="dash-filter-field dash-filter-search">
                <flux:label>{{ __('Search') }}</flux:label>
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    icon="magnifying-glass"
                    placeholder="{{ __('IP, country, city, region, browser...') }}"
                    clearable
                />
            </div>

            <div class="dash-filter-field" style="position: relative; width: 100%; max-width: 18rem;"
                 wire:ignore.self
                 x-data="dateRangePicker()"
                 data-initial-from="{{ $dateFrom }}"
                 data-initial-to="{{ $dateTo }}"
                 @click.outside="open = false"
            >
                <flux:label>{{ __('Date Range') }}</flux:label>

                <button type="button" class="dash-daterange-trigger" @click="open = !open">
                    <span x-text="label"></span>
                    <flux:icon.chevron-down class="size-4" />
                </button>

                <div class="dash-daterange-panel" x-show="open" x-cloak style="position: absolute; top: 100%; left: 0;">
                    <div class="dash-daterange-body">
                        <div class="dash-daterange-presets">
                            <template x-for="preset in presets" :key="preset.key">
                                <button
                                    type="button"
                                    :class="activePreset === preset.key ? 'dash-daterange-preset-active' : 'dash-daterange-preset'"
                                    @click="applyPreset(preset.key)"
                                    x-text="preset.label"
                                ></button>
                            </template>
                        </div>

                        <div class="dash-daterange-calendars">
                            <div class="dash-daterange-months">
                                <template x-for="(month, monthIndex) in [leftMonth, rightMonth]" :key="monthIndex">
                                    <div class="dash-daterange-month">
                                        <div class="dash-daterange-month-header">
                                            <button type="button" class="dash-daterange-nav" @click="shiftMonths(-1)" x-show="monthIndex === 0">
                                                <flux:icon.chevron-left class="size-4" />
                                            </button>
                                            <span x-show="monthIndex !== 0"></span>
                                            <span x-text="monthLabel(month)"></span>
                                            <button type="button" class="dash-daterange-nav" @click="shiftMonths(1)" x-show="monthIndex === 1">
                                                <flux:icon.chevron-right class="size-4" />
                                            </button>
                                            <span x-show="monthIndex === 0" style="width: 1.5rem;"></span>
                                        </div>

                                        <div class="dash-daterange-grid">
                                            <template x-for="wd in weekdays" :key="wd">
                                                <div class="dash-daterange-weekday" x-text="wd"></div>
                                            </template>

                                            <template x-for="cell in daysFor(month)" :key="cell.key">
                                                <button
                                                    type="button"
                                                    :class="dayClass(cell)"
                                                    :disabled="!cell.inMonth"
                                                    @click="pickDay(cell)"
                                                    x-text="cell.day"
                                                ></button>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="dash-daterange-footer">
                        <span class="dash-daterange-footer-text" x-text="footerLabel"></span>
                        <div class="flex items-center gap-2">
                            <button type="button" class="dash-daterange-btn-cancel" @click="cancel">{{ __('Cancel') }}</button>
                            <button type="button" class="dash-daterange-btn-apply" @click="apply">{{ __('Apply') }}</button>
                        </div>
                    </div>
                </div>
            </div>

            @if ($dateFrom !== '' || $dateTo !== '')
                <flux:button size="sm" variant="subtle" wire:click="clearDateRange">{{ __('Clear dates') }}</flux:button>
            @endif

            <div class="dash-filter-field dash-filter-select">
                <flux:label>{{ __('Show') }}</flux:label>
                <flux:select wire:model.live="perPage">
                    <flux:select.option value="10">10</flux:select.option>
                    <flux:select.option value="20">20</flux:select.option>
                    <flux:select.option value="50">50</flux:select.option>
                    <flux:select.option value="100">100</flux:select.option>
                    <flux:select.option value="500">500</flux:select.option>
                    <flux:select.option value="all">{{ __('All') }}</flux:select.option>
                </flux:select>
            </div>
        </div>
    </div>

    <div class="mb-4">
        <x-action-message on="visitor-deleted">{{ __('Deleted.') }}</x-action-message>
    </div>

    <div class="dash-table-wrap">
        <table class="w-full text-left text-sm">
            <thead class="dash-table-head">
                <tr>
                    <th class="px-4 py-2 font-medium text-zinc-500">{{ __('IP Address') }}</th>
                    <th class="px-4 py-2 font-medium text-zinc-500">{{ __('Location') }}</th>
                    <th class="px-4 py-2 font-medium text-zinc-500">{{ __('Map') }}</th>
                    <th class="px-4 py-2 font-medium text-zinc-500">{{ __('Browser') }}</th>
                    <th class="px-4 py-2 font-medium text-zinc-500">{{ __('OS') }}</th>
                    <th class="px-4 py-2 font-medium text-zinc-500">{{ __('Device') }}</th>
                    <th class="px-4 py-2 font-medium text-zinc-500">{{ __('Page') }}</th>
                    <th class="px-4 py-2 font-medium text-zinc-500">{{ __('Resolution') }}</th>
                    <th class="px-4 py-2 font-medium text-zinc-500">{{ __('Referrer') }}</th>
                    <th class="px-4 py-2 font-medium text-zinc-500">{{ __('ISP') }}</th>
                    <th class="px-4 py-2 font-medium text-zinc-500">{{ __('Status') }}</th>
                    <th class="px-4 py-2 font-medium text-zinc-500">{{ __('Visit Time') }}</th>
                    <th class="px-4 py-2 font-medium text-zinc-500">{{ __('Duration') }}</th>
                    <th class="px-4 py-2 font-medium text-zinc-500">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="dash-row-divide">
                @forelse ($this->visits as $visit)
                    @php
                        $geoParts = collect([$visit->city, $visit->region, $visit->country])
                            ->filter(fn ($v) => $v && $v !== 'Unknown' && $v !== 'Local');
                        $geoLabel = $geoParts->isNotEmpty() ? $geoParts->implode(', ') : '—';
                        $hasCoords = filled($visit->latitude) && filled($visit->longitude);
                        $mapLink = $hasCoords ? "https://maps.google.com/?q={$visit->latitude},{$visit->longitude}" : null;
                        $referrerLabel = filled($visit->referrer) ? $visit->referrer : __('Direct');
                        $ispLabel = ($visit->isp && $visit->isp !== 'Unknown' && $visit->isp !== 'Local') ? $visit->isp : '—';
                    @endphp
                    <tr>
                        <td class="px-4 py-2 font-mono text-xs">{{ $visit->ip_address ?? '—' }}</td>
                        <td class="px-4 py-2" title="{{ __('Country') }}: {{ $visit->country ?? '—' }} · {{ __('City') }}: {{ $visit->city ?? '—' }} · {{ __('Region') }}: {{ $visit->region ?? '—' }}">
                            {{ $geoLabel }}
                        </td>
                        <td class="px-4 py-2">
                            @if ($mapLink)
                                <a href="{{ $mapLink }}" target="_blank" rel="noopener noreferrer" class="dash-link">{{ __('Map') }}</a>
                            @else
                                <flux:text class="text-zinc-400">—</flux:text>
                            @endif
                        </td>
                        <td class="px-4 py-2">{{ $visit->browser ?? '—' }}</td>
                        <td class="px-4 py-2">{{ $visit->os ?? '—' }}</td>
                        <td class="px-4 py-2">{{ $visit->device_type ?? '—' }}</td>
                        <td class="px-4 py-2">{{ $visit->page_visited ?? '—' }}</td>
                        <td class="px-4 py-2">{{ $visit->screen_resolution ?? '—' }}</td>
                        <td class="px-4 py-2 dash-table-cell-truncate" title="{{ $referrerLabel }}">{{ $referrerLabel }}</td>
                        <td class="px-4 py-2 dash-table-cell-truncate" title="{{ $ispLabel }}">{{ $ispLabel }}</td>
                        <td class="px-4 py-2">
                            @if ($visit->is_repeat_visitor)
                                <flux:badge size="sm" color="amber">{{ __('Repeat') }}</flux:badge>
                            @else
                                <flux:badge size="sm" color="green">{{ __('New') }}</flux:badge>
                            @endif
                        </td>
                        <td class="px-4 py-2 whitespace-nowrap" title="{{ $visit->visit_time?->diffForHumans() }}">
                            {{ $visit->visit_time?->format('M j, Y g:i A') ?? '—' }}
                        </td>
                        <td class="px-4 py-2">{{ $this->formatDuration($visit->duration_seconds) }}</td>
                        <td class="px-4 py-2">
                            <div class="flex items-center gap-1">
                                <flux:button size="sm" variant="subtle" icon="trash" wire:click="confirmDelete({{ $visit->id }})" title="{{ __('Delete this visit') }}" />
                                @if ($visit->ip_address)
                                    <flux:button size="sm" variant="subtle" icon="no-symbol" wire:click="confirmDeleteIp('{{ $visit->ip_address }}')" title="{{ __('Delete all visits from this IP') }}" />
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="14" class="px-4 py-6 text-center text-zinc-500">{{ __('No visits recorded yet.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($this->visits->lastPage() > 1)
        <div class="dash-pagination mt-4">
            <div class="dash-pagination-info">
                {{ __('Showing :from–:to of :total', ['from' => number_format($this->visits->firstItem() ?? 0), 'to' => number_format($this->visits->lastItem() ?? 0), 'total' => number_format($this->visits->total())]) }}
            </div>

            <div class="dash-pagination-links">
                @if ($this->visits->onFirstPage())
                    <span class="dash-page-link-disabled">‹</span>
                @else
                    <button type="button" wire:click="previousPage" class="dash-page-link">‹</button>
                @endif

                @php
                    $current = $this->visits->currentPage();
                    $last = $this->visits->lastPage();
                    $window = 2;
                    $pages = collect(range(max(1, $current - $window), min($last, $current + $window)));
                @endphp

                @if ($pages->first() > 1)
                    <button type="button" wire:click="gotoPage(1)" class="dash-page-link">1</button>
                    @if ($pages->first() > 2)
                        <span class="dash-page-ellipsis">…</span>
                    @endif
                @endif

                @foreach ($pages as $page)
                    @if ($page === $current)
                        <span class="dash-page-link-active">{{ $page }}</span>
                    @else
                        <button type="button" wire:click="gotoPage({{ $page }})" class="dash-page-link">{{ $page }}</button>
                    @endif
                @endforeach

                @if ($pages->last() < $last)
                    @if ($pages->last() < $last - 1)
                        <span class="dash-page-ellipsis">…</span>
                    @endif
                    <button type="button" wire:click="gotoPage({{ $last }})" class="dash-page-link">{{ $last }}</button>
                @endif

                @if ($this->visits->hasMorePages())
                    <button type="button" wire:click="nextPage" class="dash-page-link">›</button>
                @else
                    <span class="dash-page-link-disabled">›</span>
                @endif
            </div>
        </div>
    @else
        <div class="dash-pagination-info mt-4">
            {{ __(':total total', ['total' => number_format($this->visits->total())]) }}
        </div>
    @endif

    <flux:modal name="visitor-delete" class="max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Delete this visit?') }}</flux:heading>
            <flux:text>{{ __('This action cannot be undone.') }}</flux:text>

            <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="delete">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="visitor-delete-ip" class="max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Delete all visits from this IP?') }}</flux:heading>
            <flux:text>
                {{ __('This will permanently delete :count visit(s) from :ip. This action cannot be undone.', ['count' => $deletingIpCount, 'ip' => $deletingIp]) }}
            </flux:text>

            <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="deleteIp">{{ __('Delete All') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>

@script
<script>
    Alpine.data('dateRangePicker', () => ({
        open: false,
        weekdays: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],
        presets: [
            { key: 'today', label: 'Today' },
            { key: 'yesterday', label: 'Yesterday' },
            { key: 'this_week', label: 'This Week' },
            { key: 'last_week', label: 'Last Week' },
            { key: 'this_month', label: 'This Month' },
            { key: 'last_month', label: 'Last Month' },
            { key: 'this_year', label: 'This Year' },
            { key: 'custom', label: 'Custom Range' },
        ],
        activePreset: 'custom',
        from: null,
        to: null,
        pendingFrom: null,
        pendingTo: null,
        viewMonth: new Date().getMonth(),
        viewYear: new Date().getFullYear(),

        // Reads the initial value from the DOM exactly once, when this
        // Alpine component is first mounted. Livewire's morph preserves
        // Alpine component state across re-renders (the wrapping element
        // has wire:ignore.self), so subsequent server round-trips never
        // re-run this and never clobber in-progress picker selections.
        init() {
            this.from = this.$el.dataset.initialFrom || null;
            this.to = this.$el.dataset.initialTo || null;
            this.pendingFrom = this.from;
            this.pendingTo = this.to;

            if (this.from) {
                const d = new Date(this.from + 'T00:00:00');
                this.viewMonth = d.getMonth();
                this.viewYear = d.getFullYear();
            }
        },

        get label() {
            if (!this.from && !this.to) {
                return 'All time';
            }
            if (this.from && this.to) {
                return this.fmt(this.from) + ' - ' + this.fmt(this.to);
            }
            return this.fmt(this.from || this.to);
        },

        get footerLabel() {
            if (!this.pendingFrom && !this.pendingTo) {
                return 'No dates selected';
            }
            if (this.pendingFrom && this.pendingTo) {
                return this.fmtShort(this.pendingFrom) + ' - ' + this.fmtShort(this.pendingTo);
            }
            return this.fmtShort(this.pendingFrom || this.pendingTo);
        },

        fmtShort(iso) {
            const d = new Date(iso + 'T00:00:00');
            return d.toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' });
        },

        fmt(iso) {
            const d = new Date(iso + 'T00:00:00');
            return d.toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' });
        },

        toIso(d) {
            const y = d.getFullYear();
            const m = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return `${y}-${m}-${day}`;
        },

        get leftMonth() {
            return { month: this.viewMonth, year: this.viewYear };
        },

        get rightMonth() {
            let month = this.viewMonth + 1;
            let year = this.viewYear;
            if (month > 11) {
                month = 0;
                year += 1;
            }
            return { month, year };
        },

        shiftMonths(delta) {
            this.viewMonth += delta;
            if (this.viewMonth > 11) {
                this.viewMonth = 0;
                this.viewYear += 1;
            } else if (this.viewMonth < 0) {
                this.viewMonth = 11;
                this.viewYear -= 1;
            }
        },

        monthLabel({ month, year }) {
            return new Date(year, month, 1).toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
        },

        daysFor({ month, year }) {
            const firstOfMonth = new Date(year, month, 1);
            const startOffset = firstOfMonth.getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const cells = [];

            for (let i = 0; i < startOffset; i++) {
                const d = new Date(year, month, 1 - (startOffset - i));
                cells.push({ key: this.toIso(d), day: d.getDate(), date: d, inMonth: false });
            }

            for (let day = 1; day <= daysInMonth; day++) {
                const d = new Date(year, month, day);
                cells.push({ key: this.toIso(d), day, date: d, inMonth: true });
            }

            while (cells.length % 7 !== 0) {
                const last = cells[cells.length - 1].date;
                const d = new Date(last);
                d.setDate(d.getDate() + 1);
                cells.push({ key: this.toIso(d), day: d.getDate(), date: d, inMonth: false });
            }

            return cells;
        },

        dayClass(cell) {
            if (!cell.inMonth) {
                return 'dash-daterange-day-muted';
            }

            const iso = this.toIso(cell.date);

            if (this.pendingFrom && iso === this.pendingFrom && (!this.pendingTo || iso === this.pendingTo)) {
                return 'dash-daterange-day-endpoint';
            }
            if (this.pendingTo && iso === this.pendingTo) {
                return 'dash-daterange-day-endpoint';
            }
            if (this.pendingFrom && iso === this.pendingFrom) {
                return 'dash-daterange-day-endpoint';
            }
            if (this.pendingFrom && this.pendingTo && iso > this.pendingFrom && iso < this.pendingTo) {
                return 'dash-daterange-day-inrange';
            }

            return 'dash-daterange-day';
        },

        pickDay(cell) {
            if (!cell.inMonth) {
                return;
            }

            const iso = this.toIso(cell.date);
            this.activePreset = 'custom';

            const rangeIsComplete = this.pendingFrom !== null && this.pendingTo !== null;

            if (rangeIsComplete) {
                // Start a fresh range on the next click, regardless of where
                // the previous range was.
                this.pendingFrom = iso;
                this.pendingTo = null;
                return;
            }

            if (this.pendingFrom === null) {
                this.pendingFrom = iso;
                this.pendingTo = null;
                return;
            }

            // One endpoint already picked — this click completes the range.
            if (iso === this.pendingFrom) {
                this.pendingTo = iso;
            } else if (iso < this.pendingFrom) {
                this.pendingTo = this.pendingFrom;
                this.pendingFrom = iso;
            } else {
                this.pendingTo = iso;
            }
        },

        applyPreset(key) {
            this.activePreset = key;
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            const startOfWeek = (d) => {
                const copy = new Date(d);
                const day = copy.getDay();
                copy.setDate(copy.getDate() - day);
                return copy;
            };

            switch (key) {
                case 'today':
                    this.pendingFrom = this.pendingTo = this.toIso(today);
                    break;
                case 'yesterday': {
                    const y = new Date(today);
                    y.setDate(y.getDate() - 1);
                    this.pendingFrom = this.pendingTo = this.toIso(y);
                    break;
                }
                case 'this_week': {
                    const start = startOfWeek(today);
                    this.pendingFrom = this.toIso(start);
                    this.pendingTo = this.toIso(today);
                    break;
                }
                case 'last_week': {
                    const start = startOfWeek(today);
                    start.setDate(start.getDate() - 7);
                    const end = new Date(start);
                    end.setDate(end.getDate() + 6);
                    this.pendingFrom = this.toIso(start);
                    this.pendingTo = this.toIso(end);
                    break;
                }
                case 'this_month': {
                    const start = new Date(today.getFullYear(), today.getMonth(), 1);
                    this.pendingFrom = this.toIso(start);
                    this.pendingTo = this.toIso(today);
                    break;
                }
                case 'last_month': {
                    const start = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                    const end = new Date(today.getFullYear(), today.getMonth(), 0);
                    this.pendingFrom = this.toIso(start);
                    this.pendingTo = this.toIso(end);
                    break;
                }
                case 'this_year': {
                    const start = new Date(today.getFullYear(), 0, 1);
                    this.pendingFrom = this.toIso(start);
                    this.pendingTo = this.toIso(today);
                    break;
                }
                case 'custom':
                default:
                    break;
            }

            if (this.pendingFrom) {
                const d = new Date(this.pendingFrom + 'T00:00:00');
                this.viewMonth = d.getMonth();
                this.viewYear = d.getFullYear();
            }
        },

        cancel() {
            this.pendingFrom = this.from;
            this.pendingTo = this.to;
            this.open = false;
        },

        apply() {
            this.from = this.pendingFrom;
            this.to = this.pendingTo || this.pendingFrom;
            this.open = false;
            this.$wire.set('dateFrom', this.from || '');
            this.$wire.set('dateTo', this.to || '');
        },
    }));
</script>
@endscript
