<?php

namespace App\Livewire;

use App\Models\Portfolio;
use Livewire\Component;

class PortfolioPage extends Component
{
    public Portfolio $portfolio;

    public function mount(?Portfolio $portfolio = null): void
    {
        $this->portfolio = $portfolio ?? Portfolio::default();

        $this->portfolio->load([
            'about',
            'experiences' => fn ($query) => $query->orderBy('sort_order'),
            'educations' => fn ($query) => $query->orderBy('sort_order'),
            'skills' => fn ($query) => $query->orderBy('sort_order'),
            'projects' => fn ($query) => $query->orderBy('sort_order'),
            'services' => fn ($query) => $query->orderBy('sort_order'),
            'testimonials' => fn ($query) => $query->orderBy('sort_order'),
        ]);
    }

    public function render()
    {
        return view('livewire.portfolio-page')->layout('layouts.empty');
    }
}
