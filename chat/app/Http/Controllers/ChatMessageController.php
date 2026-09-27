<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreChatMessageRequest;
use App\Http\Resources\ChatMessageResource;
use App\Repository\ChatMessageRepository;

class ChatMessageController extends Controller
{
    public function __construct(
        private readonly ChatMessageRepository $chatMessageRepository,
    )
    {}

    public function store(StoreChatMessageRequest $request): ChatMessageResource
    {
        $message = $this->chatMessageRepository->storeChatMessage([
            'sender_id' => $request->user()->id,
            'receiver_id' => $request->integer('receiver_id'),
            'message' => $request->string('message')->toString(),
        ]);
        return new ChatMessageResource($message);
    }
}
