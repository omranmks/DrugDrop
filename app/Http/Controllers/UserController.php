<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Events\Notifications;

use App\Http\Controllers\OTPCodeController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function test(){
        NotificationsController::NewDrugAdded(2);
        return 'hi';

    }
    public function Store()
    {
        $validateRules = [
            'name' => 'required|string|max:255',
            'phone_number' => ['unique:users', 'required', 'numeric', 'digits:10', 'regex:/(09)[0-9]{8}/'],
            'location' => 'required|string|max:255',
            'password' => 'required|min:4|max:255'
        ];

        $attributes = [
            'name' => request()->name,
            'location' => request()->location,
            'phone_number' => request()->phone_number,
            'status' => 'pending',
            'password' => request()->password
        ];

        $validation = Validator::make($attributes, $validateRules);

        if ($validation->fails()) {
            return response(['Status' => 'Failed', 'Error' => $validation->errors()], 400);
        }

        $user = User::create($attributes);

        return OTPCodeController::SendVerificationPhoneNumber($user);
    }
    public function Login()
    {
        $user = User::where('phone_number', request()->phone_number)->first()->makeVisible('id');

        if (!$user || !Hash::check(request()->password, $user->password)) {
            return response(['Status' => 'Failed', 'Error' => 'The credentials do not match.'], 401);
        }

        $token = $user->createToken('Security-Token')->plainTextToken;

        // if ($user->tokens()->count() > 5) {
        //     $user->tokens()->where('tokenable_id', $user->id)->oldest()->limit(1)->delete();
        // }

        return response([
            'Status' => 'Success',
            'Message' => 'User has been logged in successfuly.',
            'Data' => $user->attributesToArray() + ['token' => $token]
        ], 200);
    }
    public function Logout()
    {
        request()->user()->currentAccessToken()->delete();

        return response([
            'Status' => 'Success',
            'Message' => 'Logout successful.'
        ], 200);
    }
    public function ReciveVerificationCode()
    {
        $credentials = request()->phone_number;

        if (!$credentials) {
            return response(['Status' => 'Failed', 'Error' => 'Please use your phone number.'], 401);
        }

        $user = User::where('phone_number', $credentials)->first();

        if (!$user || !$user->otpcode || !request()->pin_code || !Hash::check(request()->pin_code, $user->otpcode->pin_code)) {
            return response(['Status' => 'Failed', 'Error' => 'The credentials do not match.'], 401);
        }

        OTPCodeController::DeleteRow($user->otpcode->id);

        $user->status = 'verified';
        $user->save();

        $token = $user->createToken('Security-Token')->plainTextToken;

        if ($user->tokens()->count() > 5) {
            $user->tokens()->where('tokenable_id', $user->id)->oldest()->limit(1)->delete();
        }

        return response(['Status' => 'Success', 'Message' => 'User has been verified successfuly.', 'Data' => $user->attributesToArray() + ['token' => $token]], 200);
    }
    public function Delete()
    {
        $user = request()->user();

        if (!Hash::check(request()->password, $user->password)) {
            return response(['Status' => 'Failed', 'Error' => 'Not allowed.'], 401);
        }

        User::destroy($user->id);

        return response(['Status' => 'Success', 'Message' => 'User has been deleted successfully.'], 201);
    }
    public function ForgetPassword()
    {
        $validation = Validator::make(
            request()->all(),
            [
                'phone_number' => ['required', 'numeric', 'digits:10', 'regex:/(09)[0-9]{8}/']
            ]
        );
        if ($validation->fails()) {
            return response(['Status' => 'Failed', 'Error' => $validation->errors()], 400);
        }
        $user = User::where('phone_number', request()->phone_number)->first();
        if (!$user) {
            return response(['Status' => 'Failed', 'Error' => 'Credentials do not match.'], 400);
        }
        return OTPCodeController::SendPasswordRestoration($user);
    }

    public function ReciveRestorationCode()
    {
        $validation = Validator::make(
            request()->all(),
            [
                'phone_number' => ['required', 'numeric', 'digits:10', 'regex:/(09)[0-9]{8}/']
            ]
        );
        if ($validation->fails()) {
            return response(['Status' => 'Failed', 'Error' => $validation->errors()], 400);
        }

        $credentials = request()->phone_number;

        $user = User::where('phone_number', $credentials)->first();

        if (!$user || !$user->otpcode || !request()->pin_code || !Hash::check(request()->pin_code, $user->otpcode->pin_code)) {
            return response(['Status' => 'Failed', 'Error' => 'The credentials do not match.'], 401);
        }

        $token = $user->createToken('Security-Token')->plainTextToken;

        if ($user->tokens()->count() > 5) {
            $user->tokens()->where('tokenable_id', $user->id)->oldest()->limit(1)->delete();
        }

        return response(['Status' => 'Success', 'Message' => 'User has been verified successfuly.', 'Data' => ['token' => $token]], 200);
    }

    public function ResetPassword()
    {
        $validator = Validator::make(request()->all(), ['password' => 'required|min:4|max:255']);

        if ($validator->fails()) {
            return response(['Status' => 'Failed', 'Error' => $validator->errors()], 401);
        }

        $user = User::find(request()->user()->id);

        if (!$user->otpcode || $user->otpcode->type != 'password restoration') {
            return response([
                'Status' => 'Failed',
                'Error' => 'Unauthorized.',
            ], 401);
        }

        OTPCodeController::DeleteRow($user->otpcode->id);

        $user->password = Hash::make(request()->password);
        $user->save();

        request()->user()->currentAccessToken()->delete();

        return response(['Status' => 'Success', 'Message' => 'Password has been changed successfully.'], 200);
    }
    public function ChangePassword()
    {
        $validator = Validator::make(request()->all(), ['new_password' => 'required|min:4|max:255']);

        if ($validator->fails()) {
            return response(['Status' => 'Failed', 'Error' => $validator->errors()], 401);
        }

        $user = request()->user();
        if (!request()->password || !Hash::check(request()->password, $user->passwrod)) {
            return response(['Status' => 'Failed', 'Error' => 'The credentials do not match.'], 401);
        }
        $user->password = Hash::make(request()->new_password);
        $user->save();

        return response(['Status' => 'Success', 'Message' => 'Password has been changed successfully.'], 200);
    }
}
