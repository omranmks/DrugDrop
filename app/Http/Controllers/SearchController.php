<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Drug;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function SearchDrugs()
    {
        if (!request()->lang_code) {
            return response([
                'Status' => 'Failed',
                'Error' => 'Please provide language code.'
            ], 400);
        }

        if (!request()->search && !request()->category) {
            return response([
                'Status' => 'Failed',
                'Error' => 'Please provide any word to serach for.'
            ], 400);
        }

        $drugs = Drug::where('expiry_date', '>=', now())
            ->with(['drug_details' => function ($query) {
                $query->ByLang(request()->lang_code);
            }])->whereHas('drug_details', function ($query) {
                $query->filter(request()->search);
            })->whereHas('categories', function ($query) {
                $query->filter(request()->category);
            })->paginate(20);

        foreach ($drugs->items() as $drug) {
            $drug->merge($drug->drug_details[0]);
            unset($drug->drug_details);
        }

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
    public function SearchCategory()
    {
        if (!request()->search) {
            return response([
                'Status' => 'Failed',
                'Error' => 'Please provide any word to serach for.'
            ], 400);
        }

        $category = Category::where('en_name', 'like', '%' . request()->search . '%')
            ->orWhere('ar_name', 'like', '%' . request()->search . '%')->paginate(20);

        return response([
            'Status' => 'Success',
            'Message' => 'Items have been found successfuly.',
            'Data' => $category->items(),
            'Information' => [
                'total' => $category->total(),
                'per_page' => $category->perPage(),
                'in_this_page' => $category->count(),
                'current_page' => $category->currentPage(),
                'first_page' => $category->url(1),
                'last_page' => $category->url($category->lastPage()),
                'prev_page' => $category->previousPageUrl(),
                'next_page' => $category->nextPageUrl()
            ]
        ], 200);
    }
}
