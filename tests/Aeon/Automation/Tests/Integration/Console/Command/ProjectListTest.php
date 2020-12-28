<?php

declare(strict_types=1);

namespace Aeon\Automation\Tests\Integration\Console\Command;

use Aeon\Automation\Console\Command\ProjectList;
use Aeon\Automation\Tests\Integration\Console\CommandTestCase;
use Github\Client;
use Symfony\Component\Console\Tester\CommandTester;

final class ProjectListTest extends CommandTestCase
{
    public function test_project_list_without_configuration() : void
    {
        $client = Client::createWithHttpClient($httpClient = $this->httpClient());

        $commandTester = new CommandTester(new ProjectList($client));

        $commandTester->execute([]);

        $this->assertStringContainsString('Project - List', $commandTester->getDisplay());
        $this->assertSame(0, $commandTester->getStatusCode());
    }
}
