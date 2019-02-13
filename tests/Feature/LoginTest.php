<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;

class LoginTest extends TestCase
{

    /** @test */
    public function view_login_page()
    {
        $response = $this->get('login');

        $response->assertSee('Forgot Your Password?');
    }

    /** @test */
    public function view_register_page()
    {
        if(true == config('pixelfed.open_registration')) {
            $response = $this->get('register');

            $response->assertSee('Register a new account');
        } else {
            $response = $this->get('register');

            $response->assertSee('Registration is closed');
        }
    }
}