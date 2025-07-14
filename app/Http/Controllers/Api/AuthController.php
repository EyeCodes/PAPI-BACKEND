<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Email;
use Illuminate\Support\Str;
use App\Rules\PasswordComplexityRule;


use function Laravel\Prompts\error;
use function Laravel\Prompts\password;

class AuthController extends Controller
{
    public function register(Request $request){
        $validate = Validator::make($request->all(),[
            'name'=> 'required|string|max:255',
            'email'=> 'required|email|unique:users',
            'password' => ['required', 'string', 'between:8, 255', 'confirmed', new PasswordComplexityRule()],
            'gender' => 'required|string',
            'home_address' => 'required|string',
            'current_address' => 'required|string',
             'date_of_birth' => ['required', 'date', 'before:today'],
            'contact_num' => ['required', 'digits_between:7,15'],
            'avatar' => ['nullable', 'image', 'max:2048'], 
        ]);
        
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
        ]);
        $token = $user->createToken($request->name);


        if($request->hasFile('avatar')){
            $avatar = $request->file('avatar');

            $fileName = Str::uuid() . '.' . 
            $avatar->getClientOriginalExtension();
            $path = $avatar->storeAs('avatar', $fileName, 'public');

            $user->avatar = $path;
        };

        $user->save();

        return $token->plainTextToken;

    }



    public function login(Request $request){

        $validate = Validator::make($request->all(),[
            'email'=>'required|email|exist:users',
            'password' =>'required'
        ]);

        $user = User::where('email',$request->email)->first();

        if(!Hash::check($request->password, $user->password)){
            return [
                'message' => "INVALID CREDENTIALS",
            ];
        }
        $token = $user->createToken($user->email);
        return $token->plainTextToken;
    }

    public function logout(Request $request){

        $request->user()->tokens()->delete();
        return 'logged out';
    }

}

