<?php

declare(strict_types=1);

namespace Aeon\Automation\Tests\Integration\Console\Command;

use Aeon\Automation\Console\Command\BranchList;
use Aeon\Automation\Tests\Http\HttpRequestStub;
use Aeon\Automation\Tests\Integration\Console\CommandTestCase;
use Aeon\Automation\Tests\Mother\GitHubResponseMother;
use Aeon\Automation\Tests\Mother\ResponseMother;
use Github\Client;
use Symfony\Component\Console\Tester\CommandTester;

final class BranchListTest extends CommandTestCase
{
    public function test_project_list_without_configuration() : void
    {
        $client = Client::createWithHttpClient($httpClient = $this->httpClient(
            new HttpRequestStub('GET', '/repos/aeon-php/automation', ResponseMother::jsonSuccess(GitHubResponseMother::repository('1.x'))),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/branches', ResponseMother::jsonSuccess([['name' => '1.x']])),
        ));

        $command = new BranchList();
        $command->setGithub($client);

        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'project' => 'aeon-php/automation',
        ]);

        $this->assertStringContainsString('Branch - List', $commandTester->getDisplay());
        $this->assertStringContainsString('[NOTE] Name: 1.x - default', $commandTester->getDisplay());

        $this->assertSame(0, $commandTester->getStatusCode());
    }
}
