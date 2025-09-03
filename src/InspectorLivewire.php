<?php

namespace Inspector\Laravel;

use Inspector\Models\Segment;

trait InspectorLivewire
{
    protected Inspector $inspector;

    protected Segment $segment;

    public function bootInspectorLivewire(): void
    {
        $this->inspector = inspector();
        $this->inspector->transaction()
            ->setType('livewire')
            ->name = get_class($this);
    }

    public function updatingInspectorLivewire($property, $value)
    {
        if ($this->inspector->canAddSegments()) {
            $this->segment = $this->inspector->startSegment('livewire.update', $property)
                ->setContext(['Value' => $value]);
        }
    }

    public function updatedInspectorLivewire($property)
    {
        if (isset($this->segment)) {
            $this->segment->end();
        }
    }

    public function renderingInspectorLivewire($view, $data)
    {
        if ($this->inspector->canAddSegments()) {
            $this->segment = $this->inspector->startSegment('livewire.render')
                ->setContext(['Data' => $data]);
        }
    }

    public function renderedInspectorLivewire($view, $html)
    {
        if (isset($this->segment)) {
            $this->segment->end();
        }
    }
}
