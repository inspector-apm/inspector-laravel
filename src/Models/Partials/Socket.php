<?php


namespace Inspector\Laravel\Models\Partials;


class Socket extends \Inspector\Models\Partials\Socket
{
    /**
     * Socket constructor.
     */
    public function __construct()
    {
        $request = \request();
        $this->remote_address = $request->getClientIp() ?? '';
        $this->encrypted = $request->isSecure();
    }
}
