<?php


namespace Inspector\Laravel\Models\Partials;


class Http extends \Inspector\Models\Partials\Http
{
    /**
     * Http constructor.
     */
    public function __construct()
    {
        $this->request = new Request();
        $this->url = new Url();
    }
}
