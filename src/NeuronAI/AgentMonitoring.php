<?php

namespace Inspector\Laravel\NeuronAI;

use Inspector\Inspector;

class AgentMonitoring extends \Inspector\NeuronAI\AgentMonitoring
{
    public function __construct()
    {
        parent::__construct(inspector());
    }
}
