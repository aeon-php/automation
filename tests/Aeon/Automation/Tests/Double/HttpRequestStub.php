<?php

declare(strict_types=1);

namespace Aeon\Automation\Tests\Double;

use GuzzleHttp\Psr7\Response;

final class HttpRequestStub
{
    private string $method;

    private string $pathPattern;

    private Response $response;

    public function __construct(string $method, string $pathPattern, Response $response)
    {
        $this->method = $method;
        $this->pathPattern = $pathPattern;
        $this->response = $response;
    }

    public function method() : string
    {
        return $this->method;
    }

    public function pathPattern() : string
    {
        return \strtolower($this->pathPattern);
    }

    public function response() : Response
    {
        return $this->response;
    }
}
