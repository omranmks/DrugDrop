<?php

namespace App\Http\Controllers;

use App\Models\Drug;
use App\Models\Sales;
use Illuminate\Http\Request;

class SalesController extends Controller
{
    public function GetBestSelling()
    {
        if (!request()->lang_code) {
            return response([
                'Status' => 'Failed',
                'Error' => 'Please provide language code.'
            ], 400);
        }

        $bestSelling = Drug::with(['drug_details' => function ($query) {
            $query->ByLang(request()->lang_code);
        }])->whereHas('sale', function ($query) {
            $query->orderBy('id', 'DESC');
        })
            ->paginate(20);

        $bestSelling->getCollection()->map(function ($drug) {
            $drug->trade_name = $drug->drug_details[0]->trade_name;
            $drug->scientific_name = $drug->drug_details[0]->scientific_name;
            $drug->company = $drug->drug_details[0]->company;
            $drug->dose_unit = $drug->drug_details[0]->dose_unit;
            unset($drug['drug_details']);
            return $drug;
        });

        return response([
            'Status' => 'Success',
            'Message' => 'Items have been found successfuly.',
            'Data' => $bestSelling->items(),
            'Information' => [
                'total' => $bestSelling->total(),
                'per_page' => $bestSelling->perPage(),
                'in_this_page' => $bestSelling->count(),
                'current_page' => $bestSelling->currentPage(),
                'first_page' => $bestSelling->url(1),
                'last_page' => $bestSelling->url($bestSelling->lastPage()),
                'prev_page' => $bestSelling->previousPageUrl(),
                'next_page' => $bestSelling->nextPageUrl()
            ]
        ], 200);
    }
    static public function AddQuantity($orderItem, $drug)
    {
        $sale = Sales::where('drug_id', $drug->id)->first();
        if ($sale) {
            $sale->quantity += $orderItem['Quantity'];
            $sale->save();
        } else {
            $sale = Sales::create(
                [
                    'drug_id' => $drug->id,
                    'quantity' => $orderItem['Quantity'],
                    'sale_date' => now()
                ]
            );
        }
    }
}
