<?php

namespace Inspector\Laravel;

use Inspector\Models\Segment;

trait InspectorLivewire
{
    protected Inspector $inspector;

    protected Segment $segment;

    protected Segment $componentSegment;

    public function getLivewireUrl(): string
    {
        return '/livewire/update';
    }

    public function bootInspectorLivewire(): void
    {
        $this->inspector = inspector();
    }

    public function hydrateInspectorLivewire(): void
    {
        if (!$this->inspector->canAddSegments()) {
            return;
        }

        if (\str_contains($this->inspector->transaction()->name, 'POST '.trim($this->getLivewireUrl(), '/'))) {
            $this->inspector->transaction()
                ->setType('livewire')
                ->name = get_class($this);
        } else {
            $this->componentSegment = $this->inspector->startSegment('livewire', get_class($this));
        }
    }

    public function dehydrateInspectorLivewire(): void
    {
        if (isset($this->componentSegment)) {
            $this->componentSegment->end();
        }
    }

    public function updatingInspectorLivewire($property, $value): void
    {
        if ($this->inspector->canAddSegments()) {
            $this->segment = $this->inspector->startSegment('livewire.update', $property)
                ->setContext(['Value' => $value]);
        }
    }

    public function updatedInspectorLivewire($property): void
    {
        if (isset($this->segment)) {
            $this->segment->end();
        }
    }

    public function renderingInspectorLivewire($view, $data): void
    {
        if ($this->inspector->canAddSegments()) {
            $this->segment = $this->inspector->startSegment('livewire.render')
                ->setContext(['Data' => $data]);
        }
    }

    public function renderedInspectorLivewire($view, $html): void
    {
        if (isset($this->segment)) {
            $this->segment->end();
        }
    }
}
