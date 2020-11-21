<?php

namespace Tests\Unit\Lexer;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Util\Lexer\RestrictedNames;

class RestrictedNameTest extends TestCase
{
    /** @test */
    public function restrictedUsername()
    {
        $this->assertContains('p', RestrictedNames::get());
        $this->assertContains('admin', RestrictedNames::get());
        $this->assertNotContains('dansup', RestrictedNames::get());
        $this->assertNotContains('lain', RestrictedNames::get());
    }
}
