<?php

namespace App\Http\Controllers;

use App\Models\Drug;
use App\Models\User;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function GetAllFavoriteDrugs()
    {
        if (!request()->lang_code) {
            return response([
                'Status' => 'Failed',
                'Error' => 'Please provide language code.'
            ], 400);
        }

        $drugs = User::find(request()->user()->id)
            ->favorites()
            ->with(['drug_details' => function ($query) {
                $query->ByLang(request()->lang_code);
            }])
            ->paginate(20);
            
        $drugs->getCollection()->map(function ($drug) {
            $drug->trade_name = $drug->drug_details[0]->trade_name;
            $drug->scientific_name = $drug->drug_details[0]->scientific_name;
            $drug->company = $drug->drug_details[0]->company;
            $drug->dose_unit = $drug->drug_details[0]->dose_unit;
            unset($drug['drug_details']);
            return $drug;
        });
        return response([
            'Status' => 'Success',
            'Message' => 'Data has been fetched successfuly.',
            'Data' => $drugs->toArray()['data'],
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
    public function AddToFavorite($id)
    {
        $drug = Drug::find($id);
        if (!$drug) {
            return response([
                'Status' => 'Failed',
                'Error' => 'Please provide a drug.'
            ], 404);
        }
        foreach (request()->user()->favorites as $favorite) {
            if ($favorite->pivot->drug_id == $id)
                return response([
                    'Status' => 'Failed',
                    'Error' => 'Drug is already added.'
                ], 400);
        }
        request()->user()->favorites()->attach($drug->id);
        return response([
            'Status' => 'Success',
            'Message' => 'Drug has been added to favorites.'
        ], 201);
    }
    public function DeleteFromFavorite($id){
        $drug = Drug::find($id);
        if (!$drug) {
            return response([
                'Status' => 'Failed',
                'Error' => 'Please provide a drug.'
            ], 404);
        }
        $flag = false;
        foreach (request()->user()->favorites as $favorite) {
            if ($favorite->pivot->drug_id == $id)
                $flag = true;
        }
        if(!$flag){
            return response([
                'Status' => 'Failed',
                'Error' => 'Drug is not in favorites.'
            ], 400);
        }
        request()->user()->favorites()->detach($drug->id);
        return response([
            'Status' => 'Success',
            'Message' => 'Drug has been deleted from favorites.'
        ], 201);
    }
}
