<?php


namespace Inspector\Laravel\Models\Partials;


class Url extends \Inspector\Models\Partials\Url
{
    /**
     * Url constructor.
     */
    public function __construct()
    {
        $request = \request();
        $this->protocol = $request->getScheme();
        $this->port = $request->getPort() ?? '';
        $this->path = $request->getScriptName() ?? '';
        $this->search = '?' . (($request->getQueryString() ?? '') ?? '');
        $this->full = $request->getUri() ? $request->getUri() : '';
    }
}
