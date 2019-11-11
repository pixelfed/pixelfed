<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use App\User;

class LoginTest extends TestCase
{

    /** @test */
    public function view_login_page()
    {
        $response = $this->get('login');

        $response->assertSee('Forgot Password');
    }
}