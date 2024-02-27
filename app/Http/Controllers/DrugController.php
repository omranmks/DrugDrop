<?php

namespace App\Http\Controllers;

use App\Models\Drug;
use App\Models\DrugDetail;
use App\Models\Tag;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class DrugController extends Controller
{
    public function Store()
    {
        // $data = [
        //     'all request' => request()->all(),
        //     'all headers' => request()->header(),
        //     'all cookies' => request()->cookie(),
        //     'all files' => request()->file()
        // ];
        // Log::info($data);

        $validateRules = [
            'tag_id' => 'required|numeric|exists:tags,id',
            'quantity' => 'required|integer|max:500000|min:0',
            'price' => 'required|integer|max:500000|min:0',
            'expiry_date' => 'required|date|after:tomorrow',
            'dose' => 'required|integer',
            'img' => 'nullable|image|mimes:jpeg,jpg,png|max:2048',
            'scientific_name_ar' => ['required', 'string', 'max:255', 'regex:/[\p{Arabic}\p{N}\p{L}\s\p{P}]+/u'],
            'trade_name_ar' => ['required', 'unique:drug_details,trade_name', 'string', 'max:255', 'regex:/[\p{Arabic}\p{N}\p{L}\s\p{P}]+/u'],
            'company_ar' => ['required', 'string', 'max:255', 'regex:/[\p{Arabic}\p{N}\p{L}\s\p{P}]+/u'],
            'description_ar' => ['required', 'string', 'max:500', 'regex:/[\p{Arabic}\p{N}\p{L}\s\p{P}]+/u'],
            'dose_unit_ar' => ['required', 'string', 'max:3', 'regex:/[\p{Arabic}\p{N}\p{L}\s\p{P}]+/u'],
            'scientific_name_en' => 'required|string|max:255|alpha:ascii',
            'trade_name_en' => 'required|unique:drug_details,trade_name|string|max:255|alpha:ascii',
            'company_en' => 'required|string|max:255|alpha:ascii',
            'description_en' => 'required|string|max:500',
            'dose_unit_en' => 'required|string|max:3|alpha:ascii',
            'category' => 'required|array|min:1',
            'category.*' => 'required|numeric|exists:categories,id'
        ];

        $attributes = [
            'tag_id' => request()->tag_id,
            'quantity' => request()->quantity,
            'price' => request()->price,
            'expiry_date' => request()->expiry_date,
            'dose' => request()->dose,
        ];

        $validation = Validator::make(request()->all(), $validateRules);

        if ($validation->fails()) {
            return response(['Status' => 'Failed', 'Error' => $validation->errors()], 400);
        }

        $drug = Drug::create($attributes);

        if (request()->file('img')) {
            request()->file('img')->storeAs('public/drugs', $drug->id . '.' . request()->file('img')->extension());
            $drug->img_url = 'storage/drugs/' . $drug->id . '.jpg';
            $drug->save();
        }
        $drugEn = DrugDetail::create(DrugController::GetDetails($drug->id, 'en'));
        $drugAr = DrugDetail::create(DrugController::GetDetails($drug->id, 'ar'));

        foreach (request()->category as $category) {
            $drug->categories()->attach($category);
        }

        $data = $drug->attributesToArray() + ['drug_details' => [$drugEn->makeVisible('description')->attributesToArray(), $drugAr->makeVisible('description')->attributesToArray()]];
        $data = $data + ['categories' => $drug->categories];
        $data = $data + ['tag' => $drug->tag];

        NotificationsController::NewDrugAdded($drug->id);

        return response(['Status' => 'Success', 'Message' => 'Drug has been created successfuly.', 'Data' => $data], 200);
    }

    public function UpdateDrug($id)
    {
        $validateRules = [
            'quantity' => 'required|integer|max:500000|min:0',
            'price' => 'required|integer|max:500000|min:0',
            'expiry_date' => 'required|date|after:tomorrow',
            'img' => 'nullable|image|mimes:jpeg,jpg,png|max:2048',
            'description_ar' => ['required', 'string', 'max:500', 'regex:/[\p{Arabic}\p{N}\p{L}\s\p{P}]+/u'],
            'description_en' => 'required|string|max:500',
            'category' => 'required|array|min:1',
            'category.*' => 'required|numeric|exists:categories,id'
        ];

        $drug = Drug::find($id);
        $drugDetailsAr = $drug->drug_details()->ByLang('ar')->first();
        $drugDetailsEn = $drug->drug_details()->ByLang('en')->first();

        if (!$drug) {
            return response([
                'Status' => 'Failed',
                'Error' => 'Drug not found.'
            ], 404);
        }

        $validation = Validator::make(request()->all(), $validateRules);

        if ($validation->fails()) {
            return response(['Status' => 'Failed', 'Error' => $validation->errors()], 400);
        }

        $drug->quantity = request()->quantity;
        $drug->price = request()->price;
        $drug->expiry_date = request()->expiry_date;
        if (!request()->file('img')) {
            $drug->img_url = null;
        } else {
            request()->file('img')->storeAs('public/drugs', $drug->id . '.' . request()->file('img')->extension());
            $drug->img_url = 'storage/drugs/' . $drug->id . '.jpg';
        }
        $drug->save();

        $drugDetailsAr->description = request()->description_ar;
        $drugDetailsAr->save();

        $drugDetailsEn->description = request()->description_en;
        $drugDetailsEn->save();

        $drug->categories()->detach();
        foreach (request()->category as $category) {
            $drug->categories()->attach($category);
        }

        return response([
            'Status' => 'Success',
            'Message' => 'Drug has been updated successfuly.'
        ], 200);
    }
    public function DeleteDrug($id)
    {
        Drug::destroy($id);
        return response([
            'Status' => 'Success',
            'Message' => 'Drug has been deleted successfuly.'
        ], 200);
    }
    public function GetDrugs()
    {
        if (!request()->lang_code) {
            return response([
                'Status' => 'Failed',
                'Error' => 'Please provide language code.'
            ], 400);
        }

        $drugs = Drug::when(
            request()->user()->id != 1,
            fn ($query) =>
            $query
                ->where('expiry_date', '>=', now())
                ->with(['drug_details' =>  function ($query) {
                    $query->ByLang(request()->lang_code);
                }])
                ->inRandomOrder()
        )
            ->when(
                request()->user()->id == 1,
                fn ($query) =>
                $query
                    ->with(['drug_details' =>  function ($query) {
                        $query->ByLang(request()->lang_code);
                    }])
            )->paginate(20);

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
    public function GetDrug($id)
    {
        if (!request()->lang_code && request()->user()->id != 1) {
            return response([
                'Status' => 'Failed',
                'Error' => 'Please provide language code.'
            ], 400);
        }

        $drug = Drug::find($id);

        if (!$drug) {
            return response([
                'Status' => 'Failed',
                'Error' => 'Drug not found.'
            ], 404);
        }

        if (request()->user()->id != 1)
            $drug->is_favorite = request()->user()->favorites()->where('drug_id', $drug->id)->first() ? true : false;

        if (request()->user()->id == 1) {
            $drug->trade_name_en = $drug->drug_details[0]->trade_name;
            $drug->scientific_name_en = $drug->drug_details[0]->scientific_name;
            $drug->company_en = $drug->drug_details[0]->company;
            $drug->dose_unit_en = $drug->drug_details[0]->dose_unit;
            $drug->description_en = $drug->drug_details[0]->description;

            $drug->trade_name_ar = $drug->drug_details[1]->trade_name;
            $drug->scientific_name_ar = $drug->drug_details[1]->scientific_name;
            $drug->company_ar = $drug->drug_details[1]->company;
            $drug->dose_unit_ar = $drug->drug_details[1]->dose_unit;
            $drug->description_ar = $drug->drug_details[1]->description;
        }else{
            $i = 0;
            if(request()->lang_code == 'ar') $i = 1; 
            $drug->trade_name = $drug->drug_details[$i]->trade_name;
            $drug->scientific_name = $drug->drug_details[$i]->scientific_name;
            $drug->company = $drug->drug_details[$i]->company;
            $drug->dose_unit = $drug->drug_details[$i]->dose_unit;
            $drug->description = $drug->drug_details[$i]->description;
            $drug->is_favorite = request()->user()->favorites()->where('drug_id', $drug->id)->first() ? true : false;
        }

        return response([
            'Status' => 'Success',
            'Message' => 'Data has been fetched successfuly.',
            'Data' =>
            request()->user()->id == 1 ?
                $drug->attributesToArray() +
                ['categories' => $drug->categories] +
                ['tag' => $drug->tag] : ($drug->expiry_date >= now() ?
                    $drug->attributesToArray() +
                    ['categories' => $drug->categories] +
                    ['tag' => $drug->tag] :
                    null)

        ], 200);
    }
    public function GetDrugsByCategory($id)
    {
        if (!request()->lang_code) {
            return response([
                'Status' => 'Failed',
                'Error' => 'Please provide language code.'
            ], 400);
        }

        $tags = [];

        for ($i = 1; $i < 8; $i++) {
            $tag = Tag::where('id', $i)
                ->with(
                    ['drugs' => function ($query) use ($id) {
                        $query
                            ->take(15)
                            ->where('expiry_date', '>=', now())
                            ->whereHas('categories', function ($query) use ($id) {
                                $query->where('id', $id);
                            })
                            ->with([
                                'drug_details' => function ($query) {
                                    $query->ByLang(request()->lang_code);
                                }
                            ]);
                    }]
                )->get();
            array_push($tags, $tag[0]);
        }
        foreach ($tags as $tag) {
            if (count($tag->drugs) == 0)
                continue;
            $tag->drugs = $tag->drugs->map(function ($drug) {
                $drugDetails = $drug->drug_details[0];
                unset($drug->drug_details);
                $drug->trade_name = $drugDetails->trade_name;
                $drug->scientific_name = $drugDetails->scientific_name;
                $drug->company = $drugDetails->company;
                $drug->dose_unit = $drugDetails->dose_unit;
                $drug->is_favorite = request()->user()->favorites()->where('drug_id', $drug->id)->first() ? true : false;
                return $drug;
            });
        }
        return response([
            'Status' => 'Success',
            'Message' => 'Data has been fetched successfuly.',
            'Data' => $tags
        ], 200);
    }
    public function GetDrugsByCategoryAdmin($id)
    {
        if (!request()->lang_code) {
            return response([
                'Status' => 'Failed',
                'Error' => 'Please provide language code.'
            ], 400);
        }

        $drugs = Drug::with(['drug_details' => function ($query) {
            $query->byLang(request()->lang_code);
        }])->whereHas('categories', function ($query) use ($id) {
            $query->where('id', $id);
        })->paginate(20);

        if (!$drugs) {
            return response()->json([
                'Status' => 'Failed',
                'Error' => 'The category does not exist.'
            ], 404);
        }

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

    static private function GetDetails($id, $langCode)
    {
        $attributes = [
            'drug_id' => $id,
            'trade_name' => request()->input('trade_name_' . $langCode),
            'scientific_name' => request()->input('scientific_name_' . $langCode),
            'company' => request()->input('company_' . $langCode),
            'dose_unit' => request()->input('dose_unit_' . $langCode),
            'description' => request()->input('description_' . $langCode),
            'lang_code' => $langCode
        ];
        return $attributes;
    }
}
