<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.app')]
#[Title('Admin · Create Project')]
class extends Component {};
?>

<div>
    <livewire:admin.project-editor />
</div>
