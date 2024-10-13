<?php


namespace Inspector\Laravel\Models;


use Exception;
use Inspector\Exceptions\InspectorException;
use Inspector\Models\Partials\Host;
use Inspector\Laravel\Models\Partials\Http;
use Inspector\Models\Partials\User;

class Transaction extends \Inspector\Models\Transaction
{
    /**
     * Mark the current transaction as an HTTP request.
     *
     * @return $this
     */
    public function markAsRequest()
    {
        $this->setType('request');
        $this->http = new Http();
        return $this;
    }
}
