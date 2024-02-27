<?php

namespace App\Http\Controllers;

use App\Models\Drug;
use App\Models\Tag;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function GetTags()
    {
        return response([
            'Status' => 'Success',
            'Message' => 'Data has been fetched successfuly.',
            'Data' => Tag::all()
        ], 200);
    }
    public function GetDrugsByTag($catId, $tagId)
    {
        if (!request()->lang_code) {
            return response([
                'Status' => 'Failed',
                'Error' => 'Please provide language code.'
            ], 400);
        }

        $drugs = Drug::with(['drug_details' => function ($query) {
            $query->ByLang(request()->lang_code);
        }])
            ->where('expiry_date', '>=', now())
            ->whereHas('categories', function($query) use ($catId) {
                $query->where('id', $catId);
            })
            ->whereHas('tag', function ($query) use ($tagId) {
                $query->where('id', $tagId);
            })
            ->paginate(20);

        $drugs->getCollection()->map(function ($drug) {
            $drug->trade_name = $drug->drug_details[0]->trade_name;
            $drug->scientific_name = $drug->drug_details[0]->scientific_name;
            $drug->company = $drug->drug_details[0]->company;
            $drug->dose_unit = $drug->drug_details[0]->dose_unit;
            $drug->is_favorite = request()->user()->favorites()->where('drug_id', $drug->id)->first() ? true : false;
            unset($drug['drug_details']);
            return $drug;
        });

        return response([
            'Status' => 'Success',
            'Message' => 'Items have been found successfuly.',
            'Data' => $drugs->items(),
            'Information' => [
                'total' => $drugs->total(),
                'per_page' => $drugs->perPage(),
                'in_this_page' => $drugs->count(),
                'current_page' => $drugs->currentPage(),
                'first_page' => $drugs->url(1),
                'last_page' => $drugs->url($drugs->lastPage()),
                'prev_page' => $drugs->previousPageUrl(),
                'next_page' => $drugs->nextPageUrl()
            ]
        ], 200);
    }
}
