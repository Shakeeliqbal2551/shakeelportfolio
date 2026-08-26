<?php

namespace App\Livewire;

use App\Models\Portfolio;
use App\Models\ProfileImage;
use Livewire\Component;

class PortfolioPage extends Component
{
    public Portfolio $portfolio;

    public ?ProfileImage $randomProfileImage = null;

    public function mount(?Portfolio $portfolio = null)
    {
        $this->portfolio = $portfolio
            ?? request()->attributes->get('resolvedPortfolio')
            ?? Portfolio::default();

        $this->portfolio->load([
            'about',
            'experiences' => fn ($query) => $query->orderBy('sort_order'),
            'educations' => fn ($query) => $query->orderBy('sort_order'),
            'skills' => fn ($query) => $query->orderBy('sort_order'),
            'projects' => fn ($query) => $query->orderBy('sort_order'),
            'services' => fn ($query) => $query->orderBy('sort_order'),
            'testimonials' => fn ($query) => $query->orderBy('sort_order'),
        ]);

        $this->randomProfileImage = $this->portfolio->profileImages()
            ->where('is_active', true)
            ->inRandomOrder()
            ->first();
    }

    public function render()
    {
        return view('livewire.portfolio-page')->layout('layouts.empty');
    }
}
