<?php

namespace App\Repository;

use App\Models\ChatMessage;

class ChatMessageRepository
{
    public function storeChatMessage(array $data): ChatMessage
    {
        return ChatMessage::create($data);
    }
}
