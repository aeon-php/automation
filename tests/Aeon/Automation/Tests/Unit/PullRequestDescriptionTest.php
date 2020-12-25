<?php

declare(strict_types=1);

namespace Aeon\Automation\Tests\Unit;

use Aeon\Automation\PullRequest;
use PHPUnit\Framework\TestCase;

final class PullRequestDescriptionTest extends TestCase
{
    /**
     * @dataProvider have_html_data_provider
     */
    public function test_have_html(string $body, bool $haveHTML) : void
    {
        $this->assertEquals($haveHTML, (new PullRequest('1', 'https://github.com/issue/1', 'Amazin Pull request', 'user', 'https://github.com/user', $body))->haveHTML());
    }

    public function have_html_data_provider() : \Generator
    {
        yield ['no html', false];
        yield ['no html <html><body><p>some html</p></body></html>', true];
        yield ['no html <div="id">some more html</div>', true];
    }

    /**
     * @dataProvider have_change_log_data_provider
     */
    public function test_have_change_log(string $body, bool $haveChangeLog) : void
    {
        $this->assertEquals($haveChangeLog, (new PullRequest('1', 'https://github.com/issue/1', 'Amazin Pull request', 'user', 'https://github.com/user', $body))->haveChangesDescription());
    }

    public function have_change_log_data_provider() : \Generator
    {
        yield ['no html', false];
        yield ['no html <html><body><p>some html</p></body></html>', false];
        yield ['no html <div="id">some more html</div>', false];
        yield ['no html <div id="change-log"></div>', true];
    }
}
