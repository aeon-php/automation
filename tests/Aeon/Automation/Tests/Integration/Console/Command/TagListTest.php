<?php

declare(strict_types=1);

namespace Aeon\Automation\Tests\Integration\Console\Command;

use Aeon\Automation\Console\Command\TagList;
use Aeon\Automation\Tests\Http\HttpRequestStub;
use Aeon\Automation\Tests\Integration\Console\CommandTestCase;
use Aeon\Automation\Tests\Mother\GitHubResponseMother;
use Aeon\Automation\Tests\Mother\ResponseMother;
use Aeon\Automation\Tests\Mother\SHAMother;
use Github\Client;
use Symfony\Component\Console\Tester\CommandTester;

final class TagListTest extends CommandTestCase
{
    public function test_project_list_without_configuration() : void
    {
        $client = Client::createWithHttpClient($httpClient = $this->httpClient(
            new HttpRequestStub('GET', '/repos/aeon-php/automation/tags', ResponseMother::jsonSuccess([
                GitHubResponseMother::tag('1.0.0', $sha = SHAMother::random()),
            ])),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/git/commits/@string@', ResponseMother::jsonSuccess(
                GitHubResponseMother::commit('Commit Message', $sha, '2020-01-01 00:00:00')
            )),
        ));

        $command = new TagList();
        $command->setGithub($client);
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'project' => 'aeon-php/automation',
        ]);

        $this->assertStringContainsString('Tag - List', $commandTester->getDisplay());
        $this->assertStringContainsString('[NOTE] 1.0.0 - 2020-01-01', $commandTester->getDisplay());

        $this->assertSame(0, $commandTester->getStatusCode());
    }
}
