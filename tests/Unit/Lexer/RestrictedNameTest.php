<?php

namespace Tests\Unit\Lexer;

use App\Util\Lexer\RestrictedNames;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RestrictedNameTest extends TestCase
{
    #[Test]
    public function restrictedUsername()
    {
        $names = RestrictedNames::get();
        $this->assertContains('p', $names);
        $this->assertContains('admin', $names);
        $this->assertNotContains('dansup', $names);
        $this->assertNotContains('earth', $names);
    }
}
