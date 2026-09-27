<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can send message to another user', function () {
    $sender = User::factory()->create();
    $receiver = User::factory()->create();

    $response = $this
        ->actingAs($sender)
        ->postJson(route('messages.store'), [
            'receiver_id' => $receiver->id,
            'message' => 'Hello!',
        ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.sender_id', $sender->id)
        ->assertJsonPath('data.receiver_id', $receiver->id)
        ->assertJsonPath('data.message', 'Hello!');

    $this->assertDatabaseHas('chat_messages', [
        'sender_id' => $sender->id,
        'receiver_id' => $receiver->id,
        'message' => 'Hello!',
    ]);
});
