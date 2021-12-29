<?php

declare(strict_types=1);

namespace Aeon\Automation\Tests\Integration\Console\Command\GitHub;

use Aeon\Automation\Console\AeonApplication;
use Aeon\Automation\Console\Command\GitHub\BranchList;
use Aeon\Automation\Tests\Double\HttpRequestStub;
use Aeon\Automation\Tests\Integration\Console\CommandTestCase;
use Aeon\Automation\Tests\Mother\GitHub\GitHubResponseMother;
use Aeon\Automation\Tests\Mother\ResponseMother;
use Github\Client;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Tester\CommandTester;

final class BranchListTest extends CommandTestCase
{
    public function test_project_list_without_configuration() : void
    {
        $client = Client::createWithHttpClient($httpClient = $this->httpClient(
            new HttpRequestStub('GET', '/repos/aeon-php/automation', ResponseMother::jsonSuccess(GitHubResponseMother::repository('1.x'))),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/branches', ResponseMother::jsonSuccess([
                $branch1x = GitHubResponseMother::branch('1.x'),
                GitHubResponseMother::branch('2.x'),
            ])),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/branches/1.x', ResponseMother::jsonSuccess($branch1x)),
        ));

        $command = new BranchList(\getenv('AUTOMATION_ROOT_DIR'));
        $command->setGithub($client);
        $command->setHttpCache(new ArrayAdapter());
        $command->setGitHubCache(new ArrayAdapter());

        $application = new AeonApplication();
        $application->add($command);

        $commandTester = new CommandTester($application->get(BranchList::getDefaultName()));

        $commandTester->execute(
            ['project' => 'aeon-php/automation'],
            ['verbosity' => ConsoleOutput::VERBOSITY_VERBOSE]
        );

        $this->assertStringContainsString('Branch - List', $commandTester->getDisplay());
        $this->assertStringContainsString('* 1.x', $commandTester->getDisplay());

        $this->assertSame(0, $commandTester->getStatusCode());
    }
}
