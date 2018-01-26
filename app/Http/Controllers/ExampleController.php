<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ExampleController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    //
    public function testreq(Request $request)
    {
        $request->merge(['mes' => $request->ip()]);
        return $request->route()[2]['id'];
    }

    public function clocky()
    {
        return strtotime('now');
    }
}
