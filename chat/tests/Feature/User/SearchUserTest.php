<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

uses(RefreshDatabase::class);

test('search user by name or email', function () {
    User::factory()->create([
        "name" => "test",
        "email" => "example@gmail.com",
    ]);

    User::factory()->create([
        "name" => "example",
        "email" => "test@gmail.com",
    ]);

    User::factory()->create([
        'name' => 'John',
        'email' => 'john@example.com',
    ]);

    $authUser = User::factory()->create();

    $response = $this
        ->actingAs($authUser)
        ->getJson(route("users.search",[
        "query" => "test"
    ]));

    $response
        ->assertSuccessful()
        ->assertJsonCount(2, 'data');
});
