<?php

use App\User;
use Illuminate\Support\Facades\Cache;


if (!function_exists('CurrentUser')) {

    /**
     * description
     *
     * @param
     * @return
     */
    function CurrentUser($request)
    {
        $token = 'UserToken-'.$request->header('Auth-Token');
        $userId = Cache::get($token);
        if(!empty($userId))
        {
            return null;
        }
        return User::find($userId);
    }
}
