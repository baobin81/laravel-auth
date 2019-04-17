<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
| Middleware options can be located in `app/Http/Kernel.php`
|
*/

//授权码获得token
Route::get('/api/redirect', function () {
    $query = http_build_query([
        'client_id' => '7',
        'redirect_uri' => 'http://user.quanjingke.com/auth/callback',
        'response_type' => 'code',
        'scope' => '',
    ]);

    return redirect('http://user.quanjingke.com/oauth/authorize?'.$query);
});

Route::get('/auth/callback', function (\Illuminate\Http\Request $request){

    $http = new GuzzleHttp\Client;

    $response = $http->post('http://user.quanjingke.com/oauth/token', [
        'form_params' => [
            'grant_type' => 'authorization_code',
            'client_id' => '7',  // your client id
            'client_secret' => 'aY7Nql0kWWv5NkaGWVMDCBVZdGZRo8f4ySXTCsKJ',   // your client secret
            'redirect_uri' => 'http://user.quanjingke.com/auth/callback',
            'code' => $request->code,
        ],
    ]);

    return json_decode((string) $response->getBody(), true);

});   

//密码获得授权令牌(用户名+用户密码+应用ID+应用key)
Route::get('/auth/password', function (\Illuminate\Http\Request $request){
    $http = new \GuzzleHttp\Client();

    $response = $http->post('http://user.quanjingke.com/oauth/token', [
        'form_params' => [
            'grant_type' => 'password',
            'client_id' => '8',
            'client_secret' => 'uma4uTOfJEVDy3fRObq3ea0Qgb675KoAn93g0gt8',
            'username' => 'baobin81@qq.com',
            'password' => 'baobin123',
            'scope' => '*',
        ],
    ]);

    return json_decode((string)$response->getBody(), true);
});


//隐式授权令牌
Route::get('/auth/implicit', function () {
    $query = http_build_query([
        'client_id' => '7',
        'redirect_uri' => 'http://user.quanjingke.com/auth/implicit/callback',
        'response_type' => 'token',
        'scope' => '',
    ]);

    return redirect('http://user.quanjingke.com/oauth/authorize?'.$query);
});


//隐式授权回调路由
Route::get('/auth/implicit/callback', function () {
     dd($request->get('access_token'));
});


//客户端凭证获得得令牌
Route::get('/auth/client', function (\Illuminate\Http\Request $request){
    $http = new \GuzzleHttp\Client();

    $response = $http->post('http://user.quanjingke.com/oauth/token', [
        'form_params' => [
            'grant_type' => 'client_credentials',
            'client_id' => '8',
            'client_secret' => 'uma4uTOfJEVDy3fRObq3ea0Qgb675KoAn93g0gt8',
            'scope' => '*',
        ],
    ]);

    return json_decode((string)$response->getBody(), true);
});

//客户端凭证获得得令牌,路由保护
Route::get('/userinfo', function () {
    //
    return "pass";

})->middleware('client');



// Homepage Route
Route::group(['middleware' => ['web', 'checkblocked']], function () {
    Route::get('/', 'WelcomeController@welcome')->name('welcome');
});

// Authentication Routes
Auth::routes();

// Public Routes
Route::group(['middleware' => ['web', 'activity', 'checkblocked']], function () {

    // Activation Routes
    Route::get('/activate', ['as' => 'activate', 'uses' => 'Auth\ActivateController@initial']);

    Route::get('/activate/{token}', ['as' => 'authenticated.activate', 'uses' => 'Auth\ActivateController@activate']);
    Route::get('/activation', ['as' => 'authenticated.activation-resend', 'uses' => 'Auth\ActivateController@resend']);
    Route::get('/exceeded', ['as' => 'exceeded', 'uses' => 'Auth\ActivateController@exceeded']);

    // Socialite Register Routes
    Route::get('/social/redirect/{provider}', ['as' => 'social.redirect', 'uses' => 'Auth\SocialController@getSocialRedirect']);
    Route::get('/social/handle/{provider}', ['as' => 'social.handle', 'uses' => 'Auth\SocialController@getSocialHandle']);

    // Route to for user to reactivate their user deleted account.
    Route::get('/re-activate/{token}', ['as' => 'user.reactivate', 'uses' => 'RestoreUserController@userReActivate']);
});

// Registered and Activated User Routes
Route::group(['middleware' => ['auth', 'activated', 'activity', 'checkblocked']], function () {

    // Activation Routes
    Route::get('/activation-required', ['uses' => 'Auth\ActivateController@activationRequired'])->name('activation-required');
    Route::get('/logout', ['uses' => 'Auth\LoginController@logout'])->name('logout');
});

// Registered and Activated User Routes
Route::group(['middleware' => ['auth', 'activated', 'activity', 'twostep', 'checkblocked']], function () {

    //  Homepage Route - Redirect based on user role is in controller.
    Route::get('/home', ['as' => 'public.home',   'uses' => 'UserController@index']);

    // Show users profile - viewable by other users.
    Route::get('profile/{username}', [
        'as'   => '{username}',
        'uses' => 'ProfilesController@show',
    ]);
});

// Registered, activated, and is current user routes.
Route::group(['middleware' => ['auth', 'activated', 'currentUser', 'activity', 'twostep', 'checkblocked']], function () {

    // User Profile and Account Routes
    Route::resource(
        'profile',
        'ProfilesController', [
            'only' => [
                'show',
                'edit',
                'update',
                'create',
            ],
        ]
    );
    Route::put('profile/{username}/updateUserAccount', [
        'as'   => '{username}',
        'uses' => 'ProfilesController@updateUserAccount',
    ]);
    Route::put('profile/{username}/updateUserPassword', [
        'as'   => '{username}',
        'uses' => 'ProfilesController@updateUserPassword',
    ]);
    Route::delete('profile/{username}/deleteUserAccount', [
        'as'   => '{username}',
        'uses' => 'ProfilesController@deleteUserAccount',
    ]);

    // Route to show user avatar
    Route::get('images/profile/{id}/avatar/{image}', [
        'uses' => 'ProfilesController@userProfileAvatar',
    ]);

    // Route to upload user avatar.
    Route::post('avatar/upload', ['as' => 'avatar.upload', 'uses' => 'ProfilesController@upload']);
});

// Registered, activated, and is admin routes.
Route::group(['middleware' => ['auth', 'activated', 'role:admin', 'activity', 'twostep', 'checkblocked']], function () {
    Route::resource('/users/deleted', 'SoftDeletesController', [
        'only' => [
            'index', 'show', 'update', 'destroy',
        ],
    ]);

    Route::resource('users', 'UsersManagementController', [
        'names' => [
            'index'   => 'users',
            'destroy' => 'user.destroy',
        ],
        'except' => [
            'deleted',
        ],
    ]);
    Route::post('search-users', 'UsersManagementController@search')->name('search-users');

    Route::resource('themes', 'ThemesManagementController', [
        'names' => [
            'index'   => 'themes',
            'destroy' => 'themes.destroy',
        ],
    ]);

    Route::get('logs', '\Rap2hpoutre\LaravelLogViewer\LogViewerController@index');
    Route::get('routes', 'AdminDetailsController@listRoutes');
    Route::get('active-users', 'AdminDetailsController@activeUsers');
});

Route::redirect('/php', '/phpinfo', 301);
