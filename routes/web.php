<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
	return $router->app->version();
});

$router->post('/login', 'Auth\LoginController@tryLogin');
$router->post('/forgetPassword', 'Auth\ForgetPasswordController@forgetPassword');
$router->post('/passwordReset', 'Auth\ResetPasswordController@passwordReset');
$router->post('/register', 'Auth\RegisterController@tryRegister');

$router->group(['middleware' => 'checkIdentity', 'prefix' => 'user'], function () use ($router) {
	$router->get('/', 'UserController@me');
	$router->get('/log', 'UserController@actionLog');
});

$router->group(['prefix' => 'vote'], function () use ($router) {

    $router->get('/rrr_secret/1122233', function() {
        return view('vote.result')->with('vote',Vote::find(2));
    });

    $router->group(['middleware' => 'vote_group'], function () use ($router) {
        // 访客 Ticket 验证
        $router->get('/ticket/{ticket}', 'VoteController@showVoteGroup');
        // 访客 Ticket 认证结束
    });

    $router->group(['middleware' => 'vote_result'], function () use ($router) {
        // 投票结果处理
        $router->get('/id/{id}/ticket/{ticket}/result', 'VoteController@showVoteResult')->where(['id' => '[0-9]+']);
        // 投票结果结束
    });

    $router->group(['middleware' => 'vote'], function () use ($router) {
        // 投票处理认证

        $router->post('/id/{id}/ticket/{ticket}', 'VoteController@voteHandler')->where(['id' => '[0-9]+']);

        // 投票处理结束
    });
    $router->get('/id/{id}/ticket/{ticket}', 'VoteController@showIndividualVote')->where(['id' => '[0-9]+']);
    $router->get('/id/{id}/ticket/{ticket}/qr_cache', 'VoteController@cacheOptions')->where(['id' => '[0-9]+']);
    $router->get('/id/{id}/ticket/{ticket}/get_qr_cache', 'VoteController@getCachedOptions')->where(['id' => '[0-9]+']);

});

$router->get('/testreq/{id}', 'ExampleController@testreq');