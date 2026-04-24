<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.app')]
#[Title('Admin · Visitor Logs')]
class extends Component {};
?>

<x-admin.coming-soon
    title="Visitor Logs"
    phase="Phase 9"
    description="Browse anonymous visitor analytics with country, device and referrer."
    icon="chart-bar"
/>
