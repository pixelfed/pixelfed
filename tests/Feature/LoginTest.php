<?php

it('shows the login page', function () {
    $response = $this->get('login');

    $response->assertSee('Forgot Password');
});
