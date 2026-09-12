<?php

namespace App\Repository;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class UserRepository
{
    public function searchUsersByNameOrEmail(string $query): Collection
    {
        return User::query()
            ->select(["name", "email",])
            ->where("name", "like", "%$query%")
            ->orWhere("email", "like", "%$query%")
            ->limit(10)
            ->get();
    }
}
