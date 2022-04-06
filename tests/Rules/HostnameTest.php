<?php

namespace VLauciani\LaravelValidationRules\Tests\Rules;

use VLauciani\LaravelValidationRules\Tests\TestCase;
use VLauciani\LaravelValidationRules\Rules\Hostname;

class HostnameTest extends TestCase
{
    public function testRulePasses()
    {
        $rule = new Hostname();
        $this->assertTrue($rule->passes('', 'example.com'));
        $this->assertTrue($rule->passes('', 'host-name'));
        $this->assertTrue($rule->passes('', 'www.example.com'));
    }

    public function testRuleFails()
    {
        $rule = new Hostname();
        $this->assertFalse($rule->passes('', 'I\'m not a valid hostname!'));
    }
}
