<?php

namespace App\Http\Controllers;

use App\Events\Notifications as EventsNotifications;
use App\Events\PublicNotifications;
use App\Models\Notifications;

class NotificationsController extends Controller
{
    static public function NewDrugAdded($drugId)
    {
        $evenName = 'New Drug in Storage';
        $message = 'Check our new added drug in the storage';
        broadcast(new PublicNotifications($evenName, $message, $drugId));
        Notifications::create([
            'user_id' => 1,
            'event_name' => $evenName,
            'message' => $message . ' #Drug id is : ' . $drugId . '#'
        ]);
    }
    static public function OrderInTheWay($userId)
    {
        $evenName = 'Your package on the way';
        $message = 'Your package has entered the phase on going, it is on the way';
        broadcast(new EventsNotifications($userId, $evenName, $message));
        Notifications::create([
            'user_id' => $userId,
            'event_name' => $evenName,
            'message' => $message
        ]);
    }
    static public function PaymentComplated($userId, $orderId)
    {
        $evenName = 'Payment has been complated';
        $message = 'Payment has been complated for order id ' . $orderId . ', thanks for using DrugDrop!';
        broadcast(new EventsNotifications($userId, $evenName, $message));
        Notifications::create([
            'user_id' => $userId,
            'event_name' => $evenName,
            'message' => $message
        ]);
    }
    public function GetNotification()
    {
        $notication = Notifications::orWhere('user_id', 1)->orWhere('user_id', request()->user()->id)->take(20)->get();

        return response([
            'Status' => 'Success',
            'Message' => 'Notifications fetched successfuly.',
            'Data' => $notication
        ], 200);
    }
}
