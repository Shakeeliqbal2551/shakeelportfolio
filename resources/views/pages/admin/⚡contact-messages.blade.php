<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.app')]
#[Title('Admin · Contact Messages')]
class extends Component {};
?>

<x-admin.coming-soon
    title="Contact Messages"
    phase="Phase 9"
    description="Read and manage messages submitted via the contact form."
    icon="envelope"
/>
