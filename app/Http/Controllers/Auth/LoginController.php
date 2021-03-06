<?php

namespace App\Http\Controllers\Auth;

use Activation;
use App\Http\Controllers\Controller;
use Cartalyst\Sentinel\Sentinel;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Exception;
use Cartalyst\Sentinel\Users\UserInterface;
use App\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use Cartalyst\Sentinel\Laravel\Facades\Reminder;
use App\Http\Requests\ValidationRequest;
use Illuminate\Support\Facades\Validator;
use View;

class LoginController extends Controller

{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;


    /**
     * @param Request $request
     * @return mixed
     */
    public function login(Request $request)
    {
        try {
            $data = $request->input();
            $validation = Validator::make($data, ValidationRequest::$login);
            if ($validation->fails()) {
                $errors = $validation->messages();
                return Redirect::back()->with('errors', $errors);
            }
            //Get and check user data by email
            $userData = User::GetUserByMail($data['email']);
            //Check Email Exit
            if (empty($userData)) {
                Session::flash('error', Config::get('message.options.INLAVID_MAIL'));
                return Redirect::back();
            }
//Check User Activation

            $user = \Sentinel::findById($userData->id);
            $activation = Activation::exists($user);
            if (!empty($activation) && $activation != '') {
                Session::flash('error', Config::get('message.options.USER_NOT_ACTIVATE'));
                return Redirect::back();
            }
//Check authenticate user
            $authenticate_user = \Sentinel::authenticateAndRemember($request->all());
            if (empty($authenticate_user) && $authenticate_user == '') {
                Session::flash('error', Config::get('message.options.LOGIN_INVALID'));
                return Redirect::back();
            }

//Check the roles of users
            if ($user = \Sentinel::check()) {
                \Sentinel::login($user, true);
                if (\Sentinel::getUser()->roles()->first()->slug == 'admin') {
                    return Redirect::to('/admin');
                }
            } else {
                Session::flash('error', Config::get('message.options.LOGIN_INVALID'));
                return Redirect::back();
            }
        } catch (Exception $ex) {
            return View::make('errors.exception')->with('Message', $ex->getMessage());
        }
    }

    public function logout(Request $request) {
        try {
            \Sentinel::logout();
            return Redirect::to('/login');
        } catch (Exception $ex) {
            return View::make('errors.exception')->with('Message', $ex->getMessage());
        }
    }

    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }
}
