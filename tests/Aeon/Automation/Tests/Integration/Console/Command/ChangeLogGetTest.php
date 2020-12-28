<?php

declare(strict_types=1);

namespace Aeon\Automation\Tests\Integration\Console\Command;

use Aeon\Automation\Console\Command\ChangeLogGet;
use Aeon\Automation\Tests\Http\HttpRequestStub;
use Aeon\Automation\Tests\Integration\Console\CommandTestCase;
use Aeon\Automation\Tests\Mother\GitHubResponseMother;
use Aeon\Automation\Tests\Mother\ResponseMother;
use Aeon\Automation\Tests\Mother\SHAMother;
use Github\Client;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Tester\CommandTester;

final class ChangeLogGetTest extends CommandTestCase
{
    public function test_get_changelog_without_parameters() : void
    {
        $client = Client::createWithHttpClient($httpClient = $this->httpClient(
            new HttpRequestStub('GET', '/repos/aeon-php/automation/tags', ResponseMother::jsonSuccess([
                GitHubResponseMother::tag('1.0.0'),
            ])),
            new HttpRequestStub('GET', '/repos/aeon-php/automation', ResponseMother::jsonSuccess(
                GitHubResponseMother::repository('1.x')
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/git/refs/tags/1.0.0', ResponseMother::jsonSuccess(
                GitHubResponseMother::refCommit('tags/1.0.0')
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/git/refs/heads/1.x', ResponseMother::jsonSuccess(
                GitHubResponseMother::refCommit('heads/1.x', $headRefSHA = SHAMother::random())
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits', ResponseMother::jsonSuccess(
                [GitHubResponseMother::commit('Commit 1', $commit1SHA = SHAMother::random()), GitHubResponseMother::commit('Commit 2', $commit2SHA = SHAMother::random())]
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/git/commits/' . $headRefSHA, ResponseMother::jsonSuccess(
                GitHubResponseMother::commitWithDate('Commit Message', '2020-01-01 00:00:00+00:00'),
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits/' . $commit1SHA . '/pulls', ResponseMother::jsonSuccess(
                [GitHubResponseMother::pullRequest(1)]
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits/' . $commit2SHA . '/pulls', ResponseMother::jsonSuccess(
                []
            )),
        ));

        $commandTester = new CommandTester(new ChangeLogGet($client));
        $commandTester->setInputs(['verbosity' => ConsoleOutput::VERBOSITY_VERY_VERBOSE]);

        $commandTester->execute(
            [
                'project' => 'aeon-php/automation',
            ]
        );

        $this->assertStringContainsString('Change Log - Get', $commandTester->getDisplay());

        $this->assertStringContainsString('[NOTE] Project: aeon-php/automation', $commandTester->getDisplay());
        $this->assertStringContainsString('[NOTE] From Reference: heads/1.x', $commandTester->getDisplay());
        $this->assertStringContainsString('[NOTE] Until Reference: tags/1.0.0', $commandTester->getDisplay());
        $this->assertStringContainsString('[NOTE] Fetching all commits between "heads/1.x" and "tags/1.0.0"', $commandTester->getDisplay());
        $this->assertStringContainsString('[NOTE] Total commits: 2', $commandTester->getDisplay());

        $this->assertStringContainsString('## Unreleased - 2020-01-01', $commandTester->getDisplay());
        $this->assertStringContainsString('### Changed', $commandTester->getDisplay());
        $this->assertStringContainsString(' - [#1](http://api.github.com) - **Pull Request Title** - [@user_login](http//github.com/user_login)', $commandTester->getDisplay());
        $this->assertStringContainsString(' - [' . \substr($commit2SHA, 0, 6) . '](http://api.github.com) - **Commit 2** - [@user_login](http//github.com/user_login)', $commandTester->getDisplay());

        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function test_get_only_changes_for_given_tags() : void
    {
        $client = Client::createWithHttpClient($httpClient = $this->httpClient(
            new HttpRequestStub('GET', '/repos/aeon-php/automation/tags', ResponseMother::jsonSuccess([
                GitHubResponseMother::tag('1.0.0'),
            ])),
            new HttpRequestStub('GET', '/repos/aeon-php/automation', ResponseMother::jsonSuccess(
                GitHubResponseMother::repository('1.x')
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/git/refs/tags/1.0.0', ResponseMother::jsonSuccess(
                GitHubResponseMother::refCommit('tags/1.0.0', $refUntilSHA = SHAMother::random())
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/git/refs/heads/1.x', ResponseMother::jsonSuccess(
                GitHubResponseMother::refCommit('heads/1.x', $refHeadSHA = SHAMother::random())
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/git/refs/tags/2.0.0', ResponseMother::jsonSuccess(
                GitHubResponseMother::refCommit('tags/2.0.0', $refFromSHA = SHAMother::random())
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits', ResponseMother::jsonSuccess(
                [
                    GitHubResponseMother::commit('Commit 1', $refFromSHA),
                    GitHubResponseMother::commit('Commit 2', $commit2SHA = SHAMother::random()),
                    GitHubResponseMother::commit('Commit 3', $refUntilSHA),
                    GitHubResponseMother::commit('Commit 4', $commit4SHA = SHAMother::random()),
                ]
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/git/commits/' . $refFromSHA, ResponseMother::jsonSuccess(
                GitHubResponseMother::commitWithDate('Commit Message', '2020-01-01 00:00:00+00:00'),
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits/' . $refFromSHA . '/pulls', ResponseMother::jsonSuccess([])),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits/' . $commit2SHA . '/pulls', ResponseMother::jsonSuccess([])),
        ));

        $commandTester = new CommandTester(new ChangeLogGet($client));
        $commandTester->setInputs(['verbosity' => ConsoleOutput::VERBOSITY_VERY_VERBOSE]);

        $commandTester->execute(
            [
                'project' => 'aeon-php/automation',
                '--tag-start' => '2.0.0',
                '--tag-end' => '1.0.0',
            ]
        );

        $this->assertStringContainsString('Change Log - Get', $commandTester->getDisplay());

        $this->assertStringContainsString('[NOTE] Project: aeon-php/automation', $commandTester->getDisplay());
        $this->assertStringContainsString('[NOTE] From Reference: tags/2.0.0', $commandTester->getDisplay());
        $this->assertStringContainsString('[NOTE] Until Reference: tags/1.0.0', $commandTester->getDisplay());
        $this->assertStringContainsString('[NOTE] Fetching all commits between "tags/2.0.0" and "tags/1.0.0"', $commandTester->getDisplay());
        $this->assertStringContainsString('[NOTE] Total commits: 2', $commandTester->getDisplay());

        $this->assertStringContainsString('## 2.0.0 - 2020-01-01', $commandTester->getDisplay());
        $this->assertStringContainsString('### Changed', $commandTester->getDisplay());
        $this->assertStringContainsString(' - [' . \substr($refFromSHA, 0, 6) . '](http://api.github.com) - **Commit 1** - [@user_login](http//github.com/user_login)', $commandTester->getDisplay());
        $this->assertStringContainsString(' - [' . \substr($commit2SHA, 0, 6) . '](http://api.github.com) - **Commit 2** - [@user_login](http//github.com/user_login)', $commandTester->getDisplay());

        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function test_get_only_commits() : void
    {
        $client = Client::createWithHttpClient($httpClient = $this->httpClient(
            new HttpRequestStub('GET', '/repos/aeon-php/automation/tags', ResponseMother::jsonSuccess([
                GitHubResponseMother::tag('1.0.0'),
            ])),
            new HttpRequestStub('GET', '/repos/aeon-php/automation', ResponseMother::jsonSuccess(
                GitHubResponseMother::repository('1.x')
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/git/refs/tags/1.0.0', ResponseMother::jsonSuccess(
                GitHubResponseMother::refCommit('tags/1.0.0', $refUntilSHA = SHAMother::random())
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/git/refs/heads/1.x', ResponseMother::jsonSuccess(
                GitHubResponseMother::refCommit('heads/1.x', $refHeadSHA = SHAMother::random())
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/git/refs/tags/2.0.0', ResponseMother::jsonSuccess(
                GitHubResponseMother::refCommit('tags/2.0.0', $refFromSHA = SHAMother::random())
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits', ResponseMother::jsonSuccess(
                [
                    GitHubResponseMother::commit('Commit 1', $refFromSHA),
                    GitHubResponseMother::commit('Commit 2', $commit2SHA = SHAMother::random()),
                    GitHubResponseMother::commit('Commit 3', $refUntilSHA),
                    GitHubResponseMother::commit('Commit 4', $commit4SHA = SHAMother::random()),
                ]
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/git/commits/' . $refFromSHA, ResponseMother::jsonSuccess(
                GitHubResponseMother::commitWithDate('Commit Message', '2020-01-01 00:00:00+00:00'),
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits/' . $refFromSHA . '/pulls', ResponseMother::jsonSuccess([GitHubResponseMother::pullRequest(1)])),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits/' . $commit2SHA . '/pulls', ResponseMother::jsonSuccess([GitHubResponseMother::pullRequest(2)])),
        ));

        $commandTester = new CommandTester(new ChangeLogGet($client));
        $commandTester->setInputs(['verbosity' => ConsoleOutput::VERBOSITY_VERY_VERBOSE]);

        $commandTester->execute(
            [
                'project' => 'aeon-php/automation',
                '--tag-start' => '2.0.0',
                '--tag-end' => '1.0.0',
                '--only-commits' => true,
            ]
        );

        $this->assertStringContainsString('Change Log - Get', $commandTester->getDisplay());

        $this->assertStringContainsString('[NOTE] Project: aeon-php/automation', $commandTester->getDisplay());
        $this->assertStringContainsString('[NOTE] From Reference: tags/2.0.0', $commandTester->getDisplay());
        $this->assertStringContainsString('[NOTE] Until Reference: tags/1.0.0', $commandTester->getDisplay());
        $this->assertStringContainsString('[NOTE] Fetching all commits between "tags/2.0.0" and "tags/1.0.0"', $commandTester->getDisplay());
        $this->assertStringContainsString('[NOTE] Total commits: 2', $commandTester->getDisplay());

        $this->assertStringContainsString('## 2.0.0 - 2020-01-01', $commandTester->getDisplay());
        $this->assertStringContainsString('### Changed', $commandTester->getDisplay());
        $this->assertStringContainsString(' - [' . \substr($refFromSHA, 0, 6) . '](http://api.github.com) - **Commit 1** - [@user_login](http//github.com/user_login)', $commandTester->getDisplay());
        $this->assertStringContainsString(' - [' . \substr($commit2SHA, 0, 6) . '](http://api.github.com) - **Commit 2** - [@user_login](http//github.com/user_login)', $commandTester->getDisplay());

        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function test_get_only_pull_requests() : void
    {
        $client = Client::createWithHttpClient($httpClient = $this->httpClient(
            new HttpRequestStub('GET', '/repos/aeon-php/automation/tags', ResponseMother::jsonSuccess([
                GitHubResponseMother::tag('1.0.0'),
            ])),
            new HttpRequestStub('GET', '/repos/aeon-php/automation', ResponseMother::jsonSuccess(
                GitHubResponseMother::repository('1.x')
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/git/refs/tags/1.0.0', ResponseMother::jsonSuccess(
                GitHubResponseMother::refCommit('tags/1.0.0', $refUntilSHA = SHAMother::random())
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/git/refs/heads/1.x', ResponseMother::jsonSuccess(
                GitHubResponseMother::refCommit('heads/1.x', $refHeadSHA = SHAMother::random())
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/git/refs/tags/2.0.0', ResponseMother::jsonSuccess(
                GitHubResponseMother::refCommit('tags/2.0.0', $refFromSHA = SHAMother::random())
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits', ResponseMother::jsonSuccess(
                [
                    GitHubResponseMother::commit('Commit 1', $refFromSHA),
                    GitHubResponseMother::commit('Commit 2', $commit2SHA = SHAMother::random()),
                    GitHubResponseMother::commit('Commit 3', $refUntilSHA),
                    GitHubResponseMother::commit('Commit 4', $commit4SHA = SHAMother::random()),
                ]
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/git/commits/' . $refFromSHA, ResponseMother::jsonSuccess(
                GitHubResponseMother::commitWithDate('Commit Message', '2020-01-01 00:00:00+00:00'),
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits/' . $refFromSHA . '/pulls', ResponseMother::jsonSuccess([])),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits/' . $commit2SHA . '/pulls', ResponseMother::jsonSuccess([GitHubResponseMother::pullRequest(2)])),
        ));

        $commandTester = new CommandTester(new ChangeLogGet($client));
        $commandTester->setInputs(['verbosity' => ConsoleOutput::VERBOSITY_VERY_VERBOSE]);

        $commandTester->execute(
            [
                'project' => 'aeon-php/automation',
                '--tag-start' => '2.0.0',
                '--tag-end' => '1.0.0',
                '--only-pull-requests' => true,
            ]
        );

        $this->assertStringContainsString('Change Log - Get', $commandTester->getDisplay());

        $this->assertStringContainsString('[NOTE] Project: aeon-php/automation', $commandTester->getDisplay());
        $this->assertStringContainsString('[NOTE] From Reference: tags/2.0.0', $commandTester->getDisplay());
        $this->assertStringContainsString('[NOTE] Until Reference: tags/1.0.0', $commandTester->getDisplay());
        $this->assertStringContainsString('[NOTE] Fetching all commits between "tags/2.0.0" and "tags/1.0.0"', $commandTester->getDisplay());
        $this->assertStringContainsString('[NOTE] Total commits: 2', $commandTester->getDisplay());

        $this->assertStringContainsString('## 2.0.0 - 2020-01-01', $commandTester->getDisplay());
        $this->assertStringContainsString('### Changed', $commandTester->getDisplay());
        $this->assertStringContainsString(' - [#2](http://api.github.com) - **Pull Request Title** - [@user_login](http//github.com/user_login)', $commandTester->getDisplay());

        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function test_get_changed_after() : void
    {
        $client = Client::createWithHttpClient($httpClient = $this->httpClient(
            new HttpRequestStub('GET', '/repos/aeon-php/automation/tags', ResponseMother::jsonSuccess([
                GitHubResponseMother::tag('1.0.0'),
            ])),
            new HttpRequestStub('GET', '/repos/aeon-php/automation', ResponseMother::jsonSuccess(
                GitHubResponseMother::repository('1.x')
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/git/refs/tags/1.0.0', ResponseMother::jsonSuccess(
                GitHubResponseMother::refCommit('tags/1.0.0', $refUntilSHA = SHAMother::random())
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/git/refs/heads/1.x', ResponseMother::jsonSuccess(
                GitHubResponseMother::refCommit('heads/1.x', $refHeadSHA = SHAMother::random())
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/git/refs/tags/2.0.0', ResponseMother::jsonSuccess(
                GitHubResponseMother::refCommit('tags/2.0.0', $refFromSHA = SHAMother::random())
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits', ResponseMother::jsonSuccess(
                [
                    GitHubResponseMother::commit('Commit 1', $refFromSHA, '2020-01-10 00:00:00'),
                    GitHubResponseMother::commit('Commit 2', $commit2SHA = SHAMother::random(), '2020-01-09 00:00:00'),
                    GitHubResponseMother::commit('Commit 3', $refUntilSHA, '2020-01-08 00:00:00'),
                    GitHubResponseMother::commit('Commit 4', $commit4SHA = SHAMother::random(), '2020-01-07 00:00:00'),
                ]
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/git/commits/' . $refFromSHA, ResponseMother::jsonSuccess(
                GitHubResponseMother::commitWithDate('Commit Message', '2020-01-01 00:00:00+00:00'),
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits/' . $refFromSHA . '/pulls', ResponseMother::jsonSuccess([])),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits/' . $commit2SHA . '/pulls', ResponseMother::jsonSuccess([GitHubResponseMother::pullRequest(2)])),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits/' . $refUntilSHA . '/pulls', ResponseMother::jsonSuccess([])),
        ));

        $commandTester = new CommandTester(new ChangeLogGet($client));
        $commandTester->setInputs(['verbosity' => ConsoleOutput::VERBOSITY_VERY_VERBOSE]);

        $commandTester->execute(
            [
                'project' => 'aeon-php/automation',
                '--tag-start' => '2.0.0',
                '--changed-after' => '2020-01-07 12:00:00',
            ]
        );

        $this->assertStringContainsString('Change Log - Get', $commandTester->getDisplay());

        $this->assertStringContainsString('[NOTE] Project: aeon-php/automation', $commandTester->getDisplay());
        $this->assertStringContainsString('[NOTE] From Reference: tags/2.0.0', $commandTester->getDisplay());
        $this->assertStringContainsString('[NOTE] Until Reference: ', $commandTester->getDisplay());
        $this->assertStringContainsString('[NOTE] Fetching all commits between "tags/2.0.0" and ""', $commandTester->getDisplay());
        $this->assertStringContainsString('[NOTE] Total commits: 4', $commandTester->getDisplay());

        $this->assertStringContainsString('## 2.0.0 - 2020-01-01', $commandTester->getDisplay());
        $this->assertStringContainsString('### Changed', $commandTester->getDisplay());
        $this->assertStringContainsString(' - [' . \substr($refFromSHA, 0, 6) . '](http://api.github.com) - **Commit 1** - [@user_login](http//github.com/user_login)', $commandTester->getDisplay());
        $this->assertStringContainsString(' - [#2](http://api.github.com) - **Pull Request Title** - [@user_login](http//github.com/user_login)', $commandTester->getDisplay());
        $this->assertStringContainsString(' - [' . \substr($refUntilSHA, 0, 6) . '](http://api.github.com) - **Commit 3** - [@user_login](http//github.com/user_login)', $commandTester->getDisplay());
        $this->assertStringNotContainsString('[' . \substr($commit4SHA, 0, 6) . '](http://api.github.com) - **Commit 4** - [@user_login](http//github.com/user_login)', $commandTester->getDisplay());

        $this->assertSame(0, $commandTester->getStatusCode());
    }
}
