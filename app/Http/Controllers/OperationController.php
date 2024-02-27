<?php

namespace App\Http\Controllers;

use App\Models\Drug;
use App\Models\Operation;


class OperationController extends Controller
{
    //Warhouse APIs
    public function GetOrders()
    {
        $operation = Operation::with('user')->paginate(10);

        $operation->getCollection()->map(function ($order) {
            $order->is_paid = (bool)$order->is_paid;
            $order->name = $order->user->name;
            $order->phone_number = $order->user->phone_number;
            $order->location = $order->user->location;
            unset($order['user']);
            return $order;
        });

        return response([
            'Status' => 'Success',
            'Message' => 'Data has been fetched successfuly.',
            'Data' => $operation->toArray()['data'],
            'Information' => [
                'total' => $operation->total(),
                'per_page' => $operation->perPage(),
                'in_this_page' => $operation->count(),
                'current_page' => $operation->currentPage(),
                'first_page' => $operation->url(1),
                'last_page' => $operation->url($operation->lastPage()),
                'prev_page' => $operation->previousPageUrl(),
                'next_page' => $operation->nextPageUrl()
            ]
        ], 200);
    }
    public function GetOrder($id)
    {
        $orderItem = Operation::with('user')->find($id);

        if (!$orderItem) {
            return response(['Status' => 'Failed', 'Error' => 'Not found.'], 404);
        }

        $orderItem->getCollection()->map(function ($order) {
            $order->name = $order->user->name;
            $order->phone_number = $order->user->phone_number;
            $order->location = $order->user->location;
            unset($order['user']);
            return $order;
        });

        return response([
            'Status' => 'Success',
            'Message' => 'Order has been fetched successfuly.',
            'Data' => $orderItem->makeVisible(['invoice'])
        ], 201);
    }
    public function ChangeState($id)
    {
        $order = Operation::find($id);
        if (!$order) {
            return response(['Status' => 'Failed', 'Error' => 'Please provide valid id.'], 404);
        }
        if (!request()->status && !request()->is_paid) {
            return response(['Status' => 'Failed', 'Error' => 'Please provide any field to update.'], 400);
        }
        if (request()->is_paid && !filter_var(request()->is_paid, FILTER_VALIDATE_BOOLEAN) || request()->status && !in_array(request()->status, ['on going', 'done'])) {
            return response(['Status' => 'Failed', 'Error' => 'Some fields are not validated.'], 400);
        }

        if (request()->status == 'on going' && $order->status != 'on going') {
            OperationController::UpdateQuantity($order);
            NotificationsController::OrderInTheWay($order->user_id);
        }

        if ((int)request()->is_paid == 1 && $order->is_paid != 1) {
            NotificationsController::PaymentComplated($order->user_id, $order->id);
        }
        if (request()->is_paid == 'true')
            request()->is_paid = 1;
        $order->status = request()->status ?? $order->status;
        $order->is_paid = request()->is_paid ? (int)request()->is_paid : $order->is_paid;
        $order->save();

        return response(['Status' => 'Success', 'Message' => 'The state of order has been updated successfuly.'], 200);
    }
    static private function UpdateQuantity($order)
    {
        $invoice = json_decode($order->invoice, true);
        foreach ($invoice as $item) {
            $drug = Drug::whereHas('drug_details', function ($query) use ($item) {
                $query->where('trade_name', $item['Drug Name']);
            })->first();

            SalesController::AddQuantity($item, $drug);
            //Case where there is no drugs
            $drug->quantity = $drug->quantity - $item['Quantity'];
            $drug->save();
        }
    }
    public function PlaceOrder()
    {
        $total_price = 0;

        $orders = request()->orders;
        $invoice = [];
        if (!$orders) {
            return response([
                'Status' => 'Failed',
                'Error' => 'Please provide some orders.',
                'يا تيس قلي شو عم يصير' => request()->orders
            ], 400);
        }
        foreach ($orders as $order) {
            $drug = Drug::find($order['drug_id']);
            if (!$drug) {
                return response([
                    'Status' => 'Failed',
                    'Error' => 'There is a drug that does not exist.'
                ], 404);
            }
            if ($order['quantity'] <= 0) {
                return response([
                    'Status' => 'Failed',
                    'Error' => 'Quantity must be positive number.'
                ], 400);
            }
            if ($order['quantity'] > $drug->quantity) {
                return response([
                    'Status' => 'Failed',
                    'Error' => 'There is not enough drugs.',
                ], 400);
            }
            $item_price = $drug->price * $order['quantity'];
            $total_price += $item_price;

            array_push($invoice, [
                'Drug Name' => $drug->drug_details()->ByLang(request()->lang_code ?? 'en')->first()->trade_name,
                'Drug Price' => $drug->price,
                'Quantity' => $order['quantity'],
                'Total' => $item_price
            ]);
        }

        Operation::create([
            'user_id' => request()->user()->id,
            'total_price' => $total_price,
            'invoice' => json_encode($invoice), // Convert the invoice array to JSON
        ]);

        return response([
            'Status' => 'Success',
            'Message' => 'Order has been placed successfuly.'
        ], 201);
    }
    public function GetUserOrder($id)
    {
        $operation = Operation::where('user_id', request()->user()->id)
            ->where('id', $id)->first();
        return response([
            'Status' => 'Success',
            'Message' => 'Order has been fetched successfuly.',
            'Data' => $operation->makeVisible(['invoice'])
        ], 201);
    }
    public function GetUnDone()
    {
        $operation = Operation::where('user_id', request()->user()->id)
            ->whereIn('status', ['pending', 'on going'])
            ->paginate(20);
        return response([
            'Status' => 'Success',
            'Message' => 'Data has been fetched successfuly.',
            'Data' => $operation->toArray()['data'],
            'Information' => [
                'total' => $operation->total(),
                'per_page' => $operation->perPage(),
                'in_this_page' => $operation->count(),
                'current_page' => $operation->currentPage(),
                'first_page' => $operation->url(1),
                'last_page' => $operation->url($operation->lastPage()),
                'prev_page' => $operation->previousPageUrl(),
                'next_page' => $operation->nextPageUrl()
            ]
        ], 200);
    }
    public function GetDone()
    {
        $operation =  Operation::where('user_id', request()->user()->id)
            ->where('is_paid', 1)
            ->where('status', 'done')
            ->paginate(20);
        return response([
            'Status' => 'Success',
            'Message' => 'Data has been fetched successfuly.',
            'Data' => $operation->toArray()['data'],
            'Information' => [
                'total' => $operation->total(),
                'per_page' => $operation->perPage(),
                'in_this_page' => $operation->count(),
                'current_page' => $operation->currentPage(),
                'first_page' => $operation->url(1),
                'last_page' => $operation->url($operation->lastPage()),
                'prev_page' => $operation->previousPageUrl(),
                'next_page' => $operation->nextPageUrl()
            ]
        ], 200);
    }
}
