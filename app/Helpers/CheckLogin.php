<?php

use Illuminate\Support\Facades\Cache;

if (!function_exists('CheckLogin')) {

    /**
     * description
     *
     * @param
     * @return
     */
    function CheckLogin($request)
    {
        $token = 'UserToken-'.$request->header('Auth-Token');
        $userId = Cache::get($token);
        if(!empty($userId))
        {
            return true;
        }
        return false;
    }
}
