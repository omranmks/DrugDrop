<?php

namespace App\Http\Controllers;

use App\Mail\VerificationEmail;
use Illuminate\Http\Request;
use App\Models\OTPCode;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use PhpParser\Lexer\TokenEmulator\AttributeEmulator;

class OTPCodeController extends Controller
{
    private static function Messages($id, $otp)
    {
        $messages = [
            'Welcome to DrugDrop! We\'re excited to have you on board. Your OTP code is: ' . $otp . '. Use this code to complete your verification. Thank you!',
            'Hello there! You requested password change, your OTP code is: '. $otp . '. If you did not reqeust anything you can ignore this message.'
        ];

        return $messages[$id];
    }
    private static function GenerateCode()
    {
        $generator = '';
        $availableDigits = range(0, 9);
        for ($i = 0; $i < 10; $i++) {
            $randomIndex = random_int(0, count($availableDigits) - 1);
            $generator .= $availableDigits[$randomIndex];
            array_splice($availableDigits, $randomIndex, 1);
        }
        $otp = '';
        for ($i = 0; $i < 6; $i++) {
            $otp .= substr($generator, (rand() % 10), 1);
        }
        return $otp;
    }

    public static function SendVerificationPhoneNumber($user)
    {
        $otp = OTPCodeController::GenerateCode();

        $attributes = [
            'user_id' => $user->id,
            'pin_code' => $otp,
            'type' => 'user varification'
        ];

        $url = env("SMS_API_URL");
        $token = env("SMS_API_TOKEN");
        $to = '+963' . ltrim($user->phone_number, $user->phone_number[0]);
        $message = OTPCodeController::Messages(0, $otp);
        $headers = [
            'Authorization' => $token
        ];
        $data = [
            'to' => $to,
            'message' => $message
        ];

        try {
            Http::acceptJson()->withHeaders($headers)->post($url, $data);
        } catch (\Exception $e) {
            User::destroy($user->id);
            return response(['Status' => 'Failed', 'Error' => 'Please try later.'], 500);
        }

        OTPCode::create($attributes);

        return response(['Status' => 'Success', 'Message' => 'User has been created successfully.'], 201);
    }
    public static function SendPasswordRestoration($user){
        $otp = OTPCodeController::GenerateCode();

        $attributes = [
            'user_id' => $user->id,
            'pin_code' => $otp,
            'type' => 'password restoration'
        ];
        
        $url = env("SMS_API_URL");
        $token = env("SMS_API_TOKEN");
        $to = '+963' . ltrim($user->phone_number, $user->phone_number[0]);
        $message = OTPCodeController::Messages(1, $otp);
        $headers = [
            'Authorization' => $token
        ];
        $data = [
            'to' => $to,
            'message' => $message
        ];

        try {
            Http::acceptJson()->withHeaders($headers)->post($url, $data);
        } catch (\Exception $e) {
            User::destroy($user->id);
            return response(['Status' => 'Failed', 'Error' => 'Please try later.'], 500);
        }

        OTPCode::create($attributes);

        return response(['Status' => 'Success', 'Message' => 'Password restoration code has been sent.'], 201);
    }

    public static function DeleteRow($otpcodeID)
    {
        OTPCode::destroy($otpcodeID);
    }
}
