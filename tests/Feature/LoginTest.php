<?php

namespace Tests\Feature;

use Tests\TestCase;

class LoginTest extends TestCase
{
    /** @test */
    public function view_login_page()
    {
        $response = $this->get('login');

        $response->assertSee('Forgot Password');
    }
}
