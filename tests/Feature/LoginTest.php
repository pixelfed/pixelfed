<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoginTest extends TestCase
{
    #[Test]
    public function view_login_page()
    {
        $response = $this->get('login');

        $response->assertSee('Forgot Password');
    }
}
