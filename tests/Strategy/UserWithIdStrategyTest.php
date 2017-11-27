<?php

namespace Prisjakt\Unleash\Tests\Strategy;

use PHPUnit\Framework\TestCase;
use Prisjakt\Unleash\Strategy\UserWithIdStrategy;

class UserWithIdStrategyTest extends TestCase
{

    public function testStrategyHasCorrectName()
    {
        $strategy = new UserWithIdStrategy();

        $this->assertEquals("userWithId", $strategy->getName());
    }

    public function testNoUserIdInContextReturnsFalse()
    {
        $strategy = new UserWithIdStrategy();

        $this->assertFalse($strategy->isEnabled(["userIds" => "1,2,3,4"], []));
    }

    public function testNoUsersInParametersReturnFalse()
    {
        $strategy = new UserWithIdStrategy();

        $this->assertFalse($strategy->isEnabled([], []));
    }

    public function testReturnsTrueWhenParametersContainsContextUserId()
    {
        $strategy = new UserWithIdStrategy();

        $this->assertTrue($strategy->isEnabled(["userIds" => "1,2,3"], ["userId" => "3"]));
    }

    public function testReturnsFalseWhenParametersDoesNotContainContextUserId()
    {
        $strategy = new UserWithIdStrategy();

        $this->assertFalse($strategy->isEnabled(["userIds" => "1,2,3"], ["userId" => "4"]));
    }

    public function testReturnsFalseWhenNoParameters()
    {
        $strategy = new UserWithIdStrategy();

        $this->assertFalse($strategy->isEnabled([], ["userId" => "4"]));
    }
}
