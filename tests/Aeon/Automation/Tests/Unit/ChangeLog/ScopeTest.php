<?php

declare(strict_types=1);

namespace Aeon\Automation\Tests\Unit\ChangeLog;

use Aeon\Automation\ChangeLog\Scope;
use Aeon\Automation\GitHub\Commit;
use Aeon\Automation\Tests\Mother\GitHubResponseMother;
use PHPUnit\Framework\TestCase;

final class ScopeTest extends TestCase
{
    public function test_override_scope() : void
    {
        $scope = new Scope(null, null);

        $newScope = $scope->override(
            new Scope(
                new Commit(GitHubResponseMother::commit('commit 1')),
                new Commit(GitHubResponseMother::commit('commit 2'))
            )
        );

        $this->assertTrue($newScope->isFull());
        $this->assertSame($newScope->commitStart()->title(), 'commit 1');
        $this->assertSame($newScope->commitEnd()->title(), 'commit 2');
    }

    public function test_override_with_partially_empty_scope() : void
    {
        $scope = new Scope(
            new Commit(GitHubResponseMother::commit('commit 1')),
            new Commit(GitHubResponseMother::commit('commit 2'))
        );

        $newScope = $scope->override(
            new Scope(
                null,
                new Commit(GitHubResponseMother::commit('commit 3'))
            )
        );

        $this->assertTrue($newScope->isFull());
        $this->assertSame($newScope->commitStart()->title(), 'commit 1');
        $this->assertSame($newScope->commitEnd()->title(), 'commit 3');
    }
}
