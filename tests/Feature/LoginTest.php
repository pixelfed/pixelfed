<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class LoginTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function view_login_page()
    {
        $response = $this->get('login');

        $response->assertSuccessful()
                 ->assertSee('Forgot Your Password?');
    }

    /** @test */
    public function view_register_page()
    {
        if(true === config('pixelfed.open_registration')) {
            $response = $this->get('register');

            $response->assertSuccessful()
                     ->assertSee('Register a new account');
        } else {
            $this->assertTrue(true);
        }
    }
}