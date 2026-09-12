<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchUsersRequest;
use App\Http\Resources\SearchUsersResource;
use App\Repository\UserRepository;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}
    //
    public function search(SearchUsersRequest $request): AnonymousResourceCollection
    {
        $users = $this->userRepository->searchUsersByNameOrEmail(
            $request->getQuery()
        );

        return SearchUsersResource::collection($users);
    }
}
