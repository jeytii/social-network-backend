<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function get()
    {
        $paginated = User::paginate(20);
        $data = collect($paginated)->only(['data', 'next_page_url']);

        return response()->json($data);
    }
}
