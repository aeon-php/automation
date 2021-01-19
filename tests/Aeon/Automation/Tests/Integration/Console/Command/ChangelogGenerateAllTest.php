<?php

declare(strict_types=1);

namespace Aeon\Automation\Tests\Integration\Console\Command;

use Aeon\Automation\Console\AeonApplication;
use Aeon\Automation\Console\Command\ChangelogGenerateAll;
use Aeon\Automation\Tests\Double\HttpRequestStub;
use Aeon\Automation\Tests\Integration\Console\CommandTestCase;
use Aeon\Automation\Tests\Mother\GitHub\GitHubResponseMother;
use Aeon\Automation\Tests\Mother\GitHub\SHAMother;
use Aeon\Automation\Tests\Mother\ResponseMother;
use Github\Client;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Tester\CommandTester;

final class ChangelogGenerateAllTest extends CommandTestCase
{
    public function test_changelog_generate_all_with_github_release_update() : void
    {
        $client = Client::createWithHttpClient($httpClient = $this->httpClient(
            new HttpRequestStub('GET', '/repos/aeon-php/automation', ResponseMother::jsonSuccess(
                GitHubResponseMother::repository('1.x')
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/branches/1.x', ResponseMother::jsonSuccess(
                GitHubResponseMother::branch('1.x', $branchSHA = SHAMother::random())
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/tags', ResponseMother::jsonSuccess([
                GitHubResponseMother::tag('1.2.0', $tag120SHA = SHAMother::random()),
                GitHubResponseMother::tag('1.1.0', $tag110SHA = SHAMother::random()),
                GitHubResponseMother::tag('1.0.0', $tag100SHA = SHAMother::random()),
            ])),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/git/refs/tags/1.2.0', ResponseMother::jsonSuccess(
                GitHubResponseMother::refCommit('tags/1.2.0', $tag120SHA)
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/git/refs/tags/1.1.0', ResponseMother::jsonSuccess(
                GitHubResponseMother::refCommit('tags/1.1.0', $tag110SHA)
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/git/refs/tags/1.0.0', ResponseMother::jsonSuccess(
                GitHubResponseMother::refCommit('tags/1.0.0', $tag100SHA)
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits/' . $branchSHA, ResponseMother::jsonSuccess(
                GitHubResponseMother::commit('Unreleased 1', $branchSHA, '2021-01-01'),
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits/' . $tag120SHA, ResponseMother::jsonSuccess(
                GitHubResponseMother::commit('Tag 1.2.0', $tag120SHA, '2020-03-01 00:00:00'),
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits/' . $tag110SHA, ResponseMother::jsonSuccess(
                GitHubResponseMother::commit('Tag 1.1.0', $tag110SHA, '2020-02-01 00:00:00'),
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits/' . $tag100SHA, ResponseMother::jsonSuccess(
                GitHubResponseMother::commit('Tag 1.0.0', $tag100SHA, '2020-01-01 00:00:00'),
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/compare/' . $tag120SHA . '...' . $branchSHA, ResponseMother::jsonSuccess(
                [
                    'total_commits' => 1,
                    'commits' => [
                        GitHubResponseMother::commit('Commit 1 - Unreleased', $commit1Unreleased = SHAMother::random()),
                    ],
                ]
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits/' . $commit1Unreleased . '/pulls', ResponseMother::jsonSuccess([])),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/compare/' . $tag110SHA . '...' . $tag120SHA, ResponseMother::jsonSuccess(
                [
                    'total_commits' => 2,
                    'commits' => [
                        GitHubResponseMother::commit('Commit 1 - Tag 1.2.0', $commit1Tag120 = SHAMother::random()),
                        GitHubResponseMother::commit('Commit 2 - Tag 1.2.0', $commit2Tag120 = SHAMother::random()),
                    ],
                ]
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits/' . $commit1Tag120 . '/pulls', ResponseMother::jsonSuccess([])),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits/' . $commit2Tag120 . '/pulls', ResponseMother::jsonSuccess([])),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/compare/' . $tag100SHA . '...' . $tag110SHA, ResponseMother::jsonSuccess(
                [
                    'total_commits' => 2,
                    'commits' => [
                        GitHubResponseMother::commit('Commit 1 - Tag 1.1.0', $commit1Tag110 = SHAMother::random()),
                        GitHubResponseMother::commit('Commit 2 - Tag 1.1.0', $commit2Tag110 = SHAMother::random()),
                    ],
                ]
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits/' . $commit1Tag110 . '/pulls', ResponseMother::jsonSuccess([])),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits/' . $commit2Tag110 . '/pulls', ResponseMother::jsonSuccess([])),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits', ResponseMother::jsonSuccess(
                [
                    GitHubResponseMother::commit('Commit 1 - Tag 1.0.0', $commit1Tag100 = SHAMother::random()),
                    GitHubResponseMother::commit('Commit 2 - Tag 1.0.0', $commit2Tag100 = SHAMother::random()),
                ]
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits/' . $commit1Tag100 . '/pulls', ResponseMother::jsonSuccess([])),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits/' . $commit2Tag100 . '/pulls', ResponseMother::jsonSuccess([])),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/releases', ResponseMother::jsonSuccess([
                GitHubResponseMother::release(3, '1.2.0'),
                GitHubResponseMother::release(2, '1.1.0'),
                GitHubResponseMother::release(1, '1.0.0'),
            ])),
            new HttpRequestStub('PATCH', '/repos/aeon-php/automation/releases/3', ResponseMother::jsonSuccess([])),
            new HttpRequestStub('PATCH', '/repos/aeon-php/automation/releases/2', ResponseMother::jsonSuccess([])),
            new HttpRequestStub('PATCH', '/repos/aeon-php/automation/releases/1', ResponseMother::jsonSuccess([])),
            new HttpRequestStub('PUT', '/repos/aeon-php/automation/contents/CHANGELOG.md', ResponseMother::jsonSuccess([]))
        ));

        $command = new ChangelogGenerateAll(\getenv('AUTOMATION_ROOT_DIR'));
        $command->setGithub($client);
        $command->setHttpCache(new ArrayAdapter());
        $command->setGitHubCache(new ArrayAdapter());

        $application = new AeonApplication();
        $application->add($command);

        $commandTester = new CommandTester($application->get(ChangelogGenerateAll::getDefaultName()));

        $commandTester->setInputs(['verbosity' => ConsoleOutput::VERBOSITY_VERY_VERBOSE]);

        $commandTester->execute(
            ['project' => 'aeon-php/automation', '--github-release-update' => true, '--github-file-update-path'=> 'CHANGELOG.md'],
            ['verbosity' => ConsoleOutput::VERBOSITY_VERBOSE]
        );

        $this->assertStringContainsString('Changelog - Generate - All', $commandTester->getDisplay());
        $this->assertStringContainsString(' ! [NOTE] Tags: 3', $commandTester->getDisplay());

        $this->assertStringContainsString('[1.2.0]', $commandTester->getDisplay());
        $this->assertStringContainsString(' ! [NOTE] Total commits: 2', $commandTester->getDisplay());
        $this->assertStringContainsString('## [1.2.0] - 2020-03-01', $commandTester->getDisplay());
        $this->assertStringContainsString('### Changed', $commandTester->getDisplay());
        $this->assertStringContainsString('  - [' . \substr($commit1Tag120, 0, 6) . '](http://api.github.com) - **Commit 1 - Tag 1.2.0**', $commandTester->getDisplay());
        $this->assertStringContainsString('  - [' . \substr($commit2Tag120, 0, 6) . '](http://api.github.com) - **Commit 2 - Tag 1.2.0**', $commandTester->getDisplay());

        $this->assertStringContainsString('[1.1.0]', $commandTester->getDisplay());
        $this->assertStringContainsString(' ! [NOTE] Total commits: 2', $commandTester->getDisplay());
        $this->assertStringContainsString('## [1.1.0] - 2020-02-01', $commandTester->getDisplay());
        $this->assertStringContainsString('### Changed', $commandTester->getDisplay());
        $this->assertStringContainsString('  - [' . \substr($commit1Tag110, 0, 6) . '](http://api.github.com) - **Commit 1 - Tag 1.1.0** - [@user_login](http//github.com/user_login)', $commandTester->getDisplay());
        $this->assertStringContainsString('  - [' . \substr($commit2Tag110, 0, 6) . '](http://api.github.com) - **Commit 2 - Tag 1.1.0** - [@user_login](http//github.com/user_login)', $commandTester->getDisplay());

        $this->assertStringContainsString('[1.0.0]', $commandTester->getDisplay());
        $this->assertStringContainsString(' ! [NOTE] Total commits: 2', $commandTester->getDisplay());
        $this->assertStringContainsString('## [1.0.0] - 2020-01-01', $commandTester->getDisplay());
        $this->assertStringContainsString('### Changed', $commandTester->getDisplay());
        $this->assertStringContainsString('  - [' . \substr($commit1Tag100, 0, 6) . '](http://api.github.com) - **Commit 1 - Tag 1.0.0** - [@user_login](http//github.com/user_login)', $commandTester->getDisplay());
        $this->assertStringContainsString('  - [' . \substr($commit2Tag100, 0, 6) . '](http://api.github.com) - **Commit 2 - Tag 1.0.0** - [@user_login](http//github.com/user_login)', $commandTester->getDisplay());

        $this->assertStringContainsString('Generated by [Automation](https://github.com/aeon-php/automation)', $commandTester->getDisplay());

        $this->assertStringContainsString('Updating file CHANGELOG.md content...', $commandTester->getDisplay());
        $this->assertStringContainsString('File CHANGELOG.md content updated.', $commandTester->getDisplay());

        $this->assertSame(0, $commandTester->getStatusCode());
    }
}
