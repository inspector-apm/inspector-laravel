<?php


namespace Inspector\Laravel\Models\Partials;


class Request extends \Inspector\Models\Partials\Request
{
    /**
     * Request constructor.
     */
    public function __construct()
    {
        $request = \request();
        $this->method = $request->getMethod();

        $this->version = $request->getProtocolVersion()
            ? \substr($request->getProtocolVersion(), \strpos($request->getProtocolVersion(), '/'))
            : 'unknown';

        $this->socket = new Socket();

        $this->cookies = $request->cookie();

        $h = $request->header();
        if (\array_key_exists('sec-ch-ua', $h)) {
            unset($h['sec-ch-ua']);
        }
        if (\array_key_exists('cookie', $h)) {
            unset($h['cookie']);
        }
        $this->headers = $h;
    }
}
