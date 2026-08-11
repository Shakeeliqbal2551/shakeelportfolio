<?php

use App\Models\Project;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.app')]
#[Title('Admin · Edit Project')]
class extends Component {
    public ?int $projectId = null;

    public function mount(Project $project): void
    {
        $this->projectId = $project->id;
    }
};
?>

<div>
    <livewire:admin.project-editor :project-id="$projectId" :key="'project-editor-'.$projectId" />
</div>
