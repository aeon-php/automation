<?php

declare(strict_types=1);

namespace Aeon\Automation\Tests\Mother;

use GuzzleHttp\Psr7\Response;

final class ResponseMother
{
    public static function jsonSuccess(array $body) : Response
    {
        return new Response(
            200,
            [
                'content-type' => 'application/json; charset=utf-8',
            ],
            \json_encode($body)
        );
    }

    public static function json404(string $error) : Response
    {
        return new Response(
            404,
            [
                'content-type' => 'application/json; charset=utf-8',
            ],
            $error
        );
    }
}
