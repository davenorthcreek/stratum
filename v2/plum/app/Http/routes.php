<?php

/*
|--------------------------------------------------------------------------
| Routes File
|--------------------------------------------------------------------------
|
| Here is where you will register all of the routes in an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/


/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| This route group applies the "web" middleware group to every route
| it contains. The "web" middleware group is defined in your HTTP
| kernel and includes session state, CSRF protection, and more.
|
*/

Route::group(['middleware' => ['web']], function () {
  // Authentication routes...
  Route::get('auth/login', 'Auth\AuthController@getLogin');
  Route::post('auth/login', 'Auth\AuthController@postLogin');
  Route::get('auth/logout', 'Auth\AuthController@logout');

  // Registration routes...
  Route::get('auth/register', 'Auth\AuthController@getRegister');
  Route::post('auth/register', 'Auth\AuthController@postRegister');

  Route::group(['middleware' => ['auth']], function () {
    //these routes need both web and auth middleware
    Route::get('/admin', 'CorporateUserController@index');

    Route::get('test', 'TestController@index');
    Route::get('candidate', 'CandidateController@index');
    Route::get('/candidate/{id}', ['uses' =>'CandidateController@show', 'as'=>'showCandidate']);
    Route::get('/formtemplate/{id}', ['uses'=>'FormTemplateController@getIndexWithId', 'as'=>'candidateFormTemplate']);
    Route::post('/formtemplate/{id}/update-content', ['uses'=>'FormTemplateController@postUpdateContent', 'as'=>'candidateUpdateTemplate']);
    Route::post('/formtemplate/{id}/launch-form', ['uses'=>'FormTemplateController@postLaunchForm', 'as'=>'candidateLaunchForm']);
    Route::get('/formresponse/{id}', ['uses'=>'FormResponseController@index', 'as'=>'formResponseDisplay']);

    Route::post('/formresponse/{id}/confirm', ['uses'=>'FormResponseController@confirmValues', 'as'=>'confirmValues']);



    Route::get('/home', 'CorporateUserController@index');
    Route::get('/refresh', 'CorporateUserController@refresh');

    Route::get('/', 'CorporateUserController@index');
    Route::controller('/profile', 'UsersController');

  });

});

Route::post('upload', 'UploadController@upload');
