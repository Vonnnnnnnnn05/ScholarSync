<?php

it('returns a successful response', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});

it('renders the welcome landing page with services and portal actions', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('SKSUScholarSync')
        ->assertSee('Services')
        ->assertSee('Workflow')
        ->assertSee('Portals');
});
