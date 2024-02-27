<?php

namespace App\Http\Controllers;

use App\Models\Operation;
use App\Models\Report;
use App\Models\Sales;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

//Need Improvments
class ReportController extends Controller
{
    public function CreateBestSellingsReport()
    {
        $sales = Sales::with(['drug' => function ($query) {
            $query->with(['drug_details' => function ($query) {
                $query->ByLang('en');
            }]);
        }])->orderBy('quantity', 'DESC')->get();

        $reports = [];

        foreach ($sales as $sale) {
            $report = new \stdClass;
            $report->row1 = $sale->drug->drug_details[0]->trade_name;
            $report->row2 = $sale->quantity;
            $report->row3 = $sale->sale_date;
            $report->row4 = count($sale->drug->favorites);
            array_push($reports, $report);
        }

        $attributes = [
            'url' => Str::slug(Hash::make('^@x2W1Q_T=' . request()->user()->id)),
            'title' => 'Best Sellings',
            'report' => json_encode($reports),
            'user_id' => 1
        ];

        $createdReport = Report::create($attributes);

        return response([
            'State' => 'Success',
            'Message' => 'Report has been created successfuly.',
            'Data' => URL::to('/') . "/reports/" . $createdReport->url
        ], 200);
    }
    public function CreateOrdersByYear()
    {
        $validator = Validator::make(request()->all(), ['date' => 'required|date']);
        if ($validator->fails()) {
            return response(['Status' => 'Failed', 'Error' => $validator->errors()], 400);
        }
        $order = Operation::where('created_at', '>=', request()->date)->where('status', 'done')->where('is_paid', 1)->get();

        if (count($order) == 0) {
            return response([
                'Status' => 'Failed',
                'Error' => 'No orders found.'
            ], 404);
        }

        $drugs = [];

        foreach ($order as $item) {
            $item->invoice = json_decode($item->invoice, true);
            foreach ($item->invoice as $invoice) {
                $flag = 0;
                if (count($drugs) == 0) {
                    $curDrug = new \stdClass;
                    $curDrug->row1 = $invoice['Drug Name'];
                    $curDrug->row2 = $invoice['Quantity'];
                    $curDrug->row3 = $invoice['Total'];
                    array_push($drugs, $curDrug);
                } else {
                    foreach ($drugs as $drug) {
                        if ($drug->row1 == $invoice['Drug Name']) {
                            $drug->row2 += $invoice['Quantity'];
                            $drug->row3 += $invoice['Total'];
                            $flag = 1;
                        }
                    }
                    if (!$flag) {
                        $curDrug = new \stdClass;
                        $curDrug->row1 = $invoice['Drug Name'];
                        $curDrug->row2 = $invoice['Quantity'];
                        $curDrug->row3 = $invoice['Total'];
                        array_push($drugs, $curDrug);
                    }
                }
            }
        }

        $attributes = [
            'url' => Str::slug(Hash::make('^@x2W1Q_T=' . request()->user()->id)),
            'title' => 'Orders Report',
            'report' => json_encode($drugs),
            'user_id' => 1
        ];

        $createdReport = Report::create($attributes);

        return response([
            'State' => 'Success',
            'Message' => 'Report has been created successfuly.',
            'Data' => URL::to('/') . "/reports/" . $createdReport->url
        ], 200);
    }
    public function GetReport($url)
    {
        $report = Report::where('url', $url)->first();

        if (!$report)
            abort(404);

        $report->report = collect(json_decode($report->report))->sortByDesc('row3');
        return view('report', ['report' => $report, 'data' => $report->report]);
    }
}
