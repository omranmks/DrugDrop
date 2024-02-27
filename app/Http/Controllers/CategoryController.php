<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function GetAllCategories()
    {
        return response([
            'Status' => 'Success',
            'Message' => 'Data has been fetched successfuly.',
            'Data' => Category::all()
        ], 200);
    }
    public function GetCategories(){
        $category = Category::paginate(20);
        return response([
            'Status' => 'Success',
            'Message' => 'Data has been fetched successfuly.',
            'Data' => $category->toArray()['data'],
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
