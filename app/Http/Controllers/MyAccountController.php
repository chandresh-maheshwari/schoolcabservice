<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MyAccountController extends Controller
{
    public function show()
    {
        return view('my_account.show');
    }
}
