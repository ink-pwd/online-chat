<?php

use App\Models\User;
use Illuminate\Support\Js;

it('renders the user search x-data bound to the search endpoint', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('chat'));

    $response->assertOk()
        ->assertSee('x-on:input.debounce.300ms="run()"', false)
        ->assertSee('x-html="mark(user.email)"', false)
        ->assertSee(e(Js::from(route('users.search'))), false);
});
