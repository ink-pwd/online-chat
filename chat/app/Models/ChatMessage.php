<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $sender_id
 * @property int $receiver_id
 * @property string $message
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
*/
#[Fillable(["sender_id","receiver_id","message"])]
#[Hidden(["id","created_at","updated_at"])]
class ChatMessage extends Model
{
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class,"sender_id");
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, "receiver_id");
    }
}
