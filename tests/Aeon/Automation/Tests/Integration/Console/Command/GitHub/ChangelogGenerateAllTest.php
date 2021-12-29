<?php

declare(strict_types=1);

namespace Aeon\Automation\Tests\Integration\Console\Command\GitHub;

use Aeon\Automation\Console\AeonApplication;
use Aeon\Automation\Console\Command\GitHub\ChangelogGenerateAll;
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
                GitHubResponseMother::tag('1.1.0-RC1', $tag110RC1SHA = SHAMother::random()),
                GitHubResponseMother::tag('1.0.0', $tag100SHA = SHAMother::random()),
            ])),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/git/refs/tags/1.2.0', ResponseMother::jsonSuccess(
                GitHubResponseMother::refCommit('tags/1.2.0', $tag120SHA)
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/git/refs/tags/1.1.0', ResponseMother::jsonSuccess(
                GitHubResponseMother::refCommit('tags/1.1.0', $tag110SHA)
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/git/refs/tags/1.1.0-RC1', ResponseMother::jsonSuccess(
                GitHubResponseMother::refCommit('tags/1.1.0-RC1', $tag110RC1SHA)
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
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits/' . $tag110RC1SHA, ResponseMother::jsonSuccess(
                GitHubResponseMother::commit('Tag 1.1.0-RC1', $tag110RC1SHA, '2020-02-01 00:00:00'),
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits/' . $tag100SHA, ResponseMother::jsonSuccess(
                GitHubResponseMother::commit('Tag 1.0.0', $tag100SHA, '2020-01-01 00:00:00'),
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/compare/' . $tag120SHA . '...' . $branchSHA, ResponseMother::jsonSuccess(
                [
                    'total_commits' => 1,
                    'commits' => [
                        GitHubResponseMother::commit('Commit 1 - Unreleased', $commit1Unreleased = 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c'),
                    ],
                ]
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits/' . $commit1Unreleased . '/pulls', ResponseMother::jsonSuccess([])),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/compare/' . $tag110SHA . '...' . $tag120SHA, ResponseMother::jsonSuccess(
                [
                    'total_commits' => 2,
                    'commits' => [
                        GitHubResponseMother::commit('Commit 1 - Tag 1.2.0', $commit1Tag120 = '356a192b7913b04c54574d18c28d46e6395428ab'),
                        GitHubResponseMother::commit('Commit 2 - Tag 1.2.0', $commit2Tag120 = 'da4b9237bacccdf19c0760cab7aec4a8359010b0'),
                    ],
                ]
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits/' . $commit1Tag120 . '/pulls', ResponseMother::jsonSuccess([])),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits/' . $commit2Tag120 . '/pulls', ResponseMother::jsonSuccess([])),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/compare/' . $tag100SHA . '...' . $tag110SHA, ResponseMother::jsonSuccess(
                [
                    'total_commits' => 2,
                    'commits' => [
                        GitHubResponseMother::commit('Commit 1 - Tag 1.1.0', $commit1Tag110 = '77de68daecd823babbb58edb1c8e14d7106e83bb'),
                        GitHubResponseMother::commit('Commit 2 - Tag 1.1.0', $commit2Tag110 = '1b6453892473a467d07372d45eb05abc2031647a'),
                    ],
                ]
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits/' . $commit1Tag110 . '/pulls', ResponseMother::jsonSuccess([])),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits/' . $commit2Tag110 . '/pulls', ResponseMother::jsonSuccess([])),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits', ResponseMother::jsonSuccess(
                [
                    GitHubResponseMother::commit('Commit 1 - Tag 1.0.0', $commit1Tag100 = 'ac3478d69a3c81fa62e60f5c3696165a4e5e6ac4'),
                    GitHubResponseMother::commit('Commit 2 - Tag 1.0.0', $commit2Tag100 = 'c1dfd96eea8cc2b62785275bca38ac261256e278'),
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

        $localChangelogFile = \sys_get_temp_dir() . '/CHANGELOG_ALL.md';

        $commandTester->execute(
            [
                'project' => 'aeon-php/automation',
                '--github-release-update' => true,
                '--github-file-update-path' => 'CHANGELOG.md',
                '--tag-only-stable' => true,
                '--file-update-path' => $localChangelogFile,
            ],
            ['verbosity' => ConsoleOutput::VERBOSITY_VERBOSE]
        );

        $this->assertStringContainsString('Changelog - Generate - All', $commandTester->getDisplay());
        $this->assertStringContainsString(' ! [NOTE] Tags: 3', $commandTester->getDisplay());

        $this->assertStringContainsString('[1.2.0]', $commandTester->getDisplay());
        $this->assertStringContainsString(' ! [NOTE] Total commits: 2', $commandTester->getDisplay());
        $this->assertStringContainsString('## [1.2.0] - 2020-03-01', $commandTester->getDisplay());
        $this->assertStringContainsString('### Changed', $commandTester->getDisplay());
        $this->assertStringContainsString('- [' . \substr($commit1Tag120, 0, 6) . '](http://api.github.com) - **Commit 1 - Tag 1.2.0**', $commandTester->getDisplay());
        $this->assertStringContainsString('- [' . \substr($commit2Tag120, 0, 6) . '](http://api.github.com) - **Commit 2 - Tag 1.2.0**', $commandTester->getDisplay());

        $this->assertStringContainsString('[1.1.0]', $commandTester->getDisplay());
        $this->assertStringContainsString(' ! [NOTE] Total commits: 2', $commandTester->getDisplay());
        $this->assertStringContainsString('## [1.1.0] - 2020-02-01', $commandTester->getDisplay());
        $this->assertStringContainsString('### Changed', $commandTester->getDisplay());
        $this->assertStringContainsString('- [' . \substr($commit1Tag110, 0, 6) . '](http://api.github.com) - **Commit 1 - Tag 1.1.0** - [@user_login](http//github.com/user_login)', $commandTester->getDisplay());
        $this->assertStringContainsString('- [' . \substr($commit2Tag110, 0, 6) . '](http://api.github.com) - **Commit 2 - Tag 1.1.0** - [@user_login](http//github.com/user_login)', $commandTester->getDisplay());

        $this->assertStringContainsString('[1.0.0]', $commandTester->getDisplay());
        $this->assertStringContainsString(' ! [NOTE] Total commits: 2', $commandTester->getDisplay());
        $this->assertStringContainsString('## [1.0.0] - 2020-01-01', $commandTester->getDisplay());
        $this->assertStringContainsString('### Changed', $commandTester->getDisplay());
        $this->assertStringContainsString('- [' . \substr($commit1Tag100, 0, 6) . '](http://api.github.com) - **Commit 1 - Tag 1.0.0** - [@user_login](http//github.com/user_login)', $commandTester->getDisplay());
        $this->assertStringContainsString('- [' . \substr($commit2Tag100, 0, 6) . '](http://api.github.com) - **Commit 2 - Tag 1.0.0** - [@user_login](http//github.com/user_login)', $commandTester->getDisplay());

        $this->assertStringContainsString('Generated by [Automation](https://github.com/aeon-php/automation)', $commandTester->getDisplay());

        $this->assertStringContainsString('Updating file CHANGELOG.md content...', $commandTester->getDisplay());
        $this->assertStringContainsString('File CHANGELOG.md content updated.', $commandTester->getDisplay());

        $this->assertSame(0, $commandTester->getStatusCode());
        $this->assertSame(
            \file_get_contents(__DIR__ . '/Fixtures/CHANGELOG_ALL.md'),
            \file_get_contents($localChangelogFile)
        );

        \unlink($localChangelogFile);
    }
}
