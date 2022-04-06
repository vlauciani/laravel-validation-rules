<?php

namespace VLauciani\LaravelValidationRules\Tests\Rules;

use VLauciani\LaravelValidationRules\Tests\TestCase;
use VLauciani\LaravelValidationRules\Rules\RFC3339ExtendedRule;

class RFC3339ExtendedTest extends TestCase
{
    public function testRulePasses()
    {
        $rule = new RFC3339ExtendedRule();
        $this->assertTrue($rule->passes('', '2021-10-06T06:33:36.440+00:00'));
        $this->assertTrue($rule->passes('', '2021-10-06T06:33:36.440Z'));
        $this->assertTrue($rule->passes('', '2021-10-06T06:33:36.44Z'));
        $this->assertTrue($rule->passes('', '2021-10-06T06:33:36Z'));
    }

    public function testRuleFails()
    {
        $rule = new RFC3339ExtendedRule();
        $this->assertFalse($rule->passes('', 'I\'m not a valid RFC3339Extended!'));
        $this->assertFalse($rule->passes('', '2021-10-06 06:33:36'));
    }
}
