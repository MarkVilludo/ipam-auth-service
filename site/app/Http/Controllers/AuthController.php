<?php

namespace App\Http\Controllers;

use App\Actions\Auth\RegisterUserAction;
use App\Actions\Auth\LoginUserAction;
use App\Actions\Auth\RefreshTokenAction;
use App\Actions\Auth\GetAuthenticatedUserAction;
use App\Actions\Auth\LogoutUserAction;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private RegisterUserAction $registerUserAction,
        private LoginUserAction $loginUserAction,
        private RefreshTokenAction $refreshTokenAction,
        private GetAuthenticatedUserAction $getAuthenticatedUserAction,
        private LogoutUserAction $logoutUserAction,
    ) {}

    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        $result = $this->registerUserAction->execute($request->only('name', 'email', 'password'));
        return response()->json(
            collect($result)->except('status')->toArray(),
            $result['status']
        );
    }

    /**
     * Login user and return JWT token
     */
    public function login(Request $request)
    {
        $result = $this->loginUserAction->execute($request->only('email', 'password'), $request);
        return response()->json(
            collect($result)->except('status')->toArray(),
            $result['status']
        );
    }

    /**
     * Refresh JWT token
     */
    public function refresh()
    {
        $result = $this->refreshTokenAction->execute();
        return response()->json(
            collect($result)->except('status')->toArray(),
            $result['status']
        );
    }

    /**
     * Get authenticated user
     */
    public function me()
    {
        $result = $this->getAuthenticatedUserAction->execute();
        return response()->json(
            collect($result)->except('status')->toArray(),
            $result['status']
        );
    }

    /**
     * Logout user (invalidate token)
     */
    public function logout(Request $request)
    {
        $result = $this->logoutUserAction->execute($request);
        return response()->json(
            collect($result)->except('status')->toArray(),
            $result['status']
        );
    }
}
