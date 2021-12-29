<?php

declare(strict_types=1);

namespace Aeon\Automation\Tests\Integration\Console\Command\GitHub;

use Aeon\Automation\Console\AeonApplication;
use Aeon\Automation\Console\Command\GitHub\TagList;
use Aeon\Automation\Tests\Double\HttpRequestStub;
use Aeon\Automation\Tests\Integration\Console\CommandTestCase;
use Aeon\Automation\Tests\Mother\GitHub\GitHubResponseMother;
use Aeon\Automation\Tests\Mother\GitHub\SHAMother;
use Aeon\Automation\Tests\Mother\ResponseMother;
use Github\Client;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Tester\CommandTester;

final class TagListTest extends CommandTestCase
{
    public function test_project_list_without_configuration() : void
    {
        $client = Client::createWithHttpClient($httpClient = $this->httpClient(
            new HttpRequestStub('GET', '/repos/aeon-php/automation/tags', ResponseMother::jsonSuccess([
                GitHubResponseMother::tag('1.0.0', $sha = SHAMother::random()),
            ])),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits/' . $sha, ResponseMother::jsonSuccess(
                GitHubResponseMother::commit('Commit Message', $sha, '2020-01-01 00:00:00')
            )),
        ));

        $command = new TagList(\getenv('AUTOMATION_ROOT_DIR'));
        $command->setGithub($client);
        $command->setHttpCache(new ArrayAdapter());
        $command->setGitHubCache(new ArrayAdapter());

        $application = new AeonApplication();
        $application->add($command);

        $commandTester = new CommandTester($application->get(TagList::getDefaultName()));

        $commandTester->execute(
            ['project' => 'aeon-php/automation', '--with-date' => true, '--with-commit' => true],
            ['verbosity' => ConsoleOutput::VERBOSITY_VERBOSE]
        );

        $this->assertStringContainsString('Tag - List', $commandTester->getDisplay());
        $this->assertStringContainsString('1.0.0 - 2020-01-01 - ' . $sha, $commandTester->getDisplay());

        $this->assertSame(0, $commandTester->getStatusCode());
    }
}
