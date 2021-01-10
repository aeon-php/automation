<?php

declare(strict_types=1);

namespace Aeon\Automation\Tests\Unit\Release;

use Aeon\Automation\GitHub\Branch;
use Aeon\Automation\GitHub\Commit;
use Aeon\Automation\Release\Scope;
use Aeon\Automation\Tests\Mother\GitHub\GitHubResponseMother;
use PHPUnit\Framework\TestCase;

final class ScopeTest extends TestCase
{
    public function test_override_scope() : void
    {
        $scope = Scope::empty();

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
            new Commit(GitHubResponseMother::commit('commit 2')),
            new Branch(GitHubResponseMother::branch('main'))
        );

        $newScope = $scope->override(
            new Scope(
                null,
                new Commit(GitHubResponseMother::commit('commit 3'))
            )
        );

        $this->assertTrue($newScope->isFull());
        $this->assertSame('commit 1', $newScope->commitStart()->title());
        $this->assertSame('commit 3', $newScope->commitEnd()->title());
        $this->assertSame('main', $newScope->branch()->name());
    }
}
