<?php

namespace App\Http\Controllers;

use AccountInformation;
use App\Models\AboutUsModel;
use App\Models\AccountInformationModel;
use App\Models\CherrypikPage;
use App\Models\ClientModel;
use App\Models\HeroModel;
use App\Models\ProductModel;
use App\Models\ServiceModel;
use App\Models\StatsModel;
use GuzzleHttp\Promise\Create;
use Illuminate\Auth\Events\Logout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Product;

class HomeController extends Controller
{
    public function __construct()
    {

        // $this->middleware('auth');
    }

    // public function index()
    // {
    //     $pages = CherrypikPage::all();
    //     // dd($pages);
    //     return view('cherrypik_front_layout.content', ['pages' => $pages]);
    // }

    public function index()
    {
        $pages = CherrypikPage::where('status', 1)
            ->where('deleted', 0)
            ->get();

        // Prepare an array to hold data for templates
        // $templateData = [];

        // foreach ($pages as $page) {
        //     // switch ($page->template) {
        //     //     case 'hero':
        //     //         // Fetch hero section data
        //     //         $templateData['hero'] = HeroModel::where('deleted', 0)->first();
        //     //         break;
        //     //     case 'clients':
        //     //     // Fetch hero section data
        //     //     $templateData['clients'] = ClientModel::where('deleted', 0)->first();
        //     //     break;
        //     //     case 'about_us':
        //     //     // Fetch hero section data
        //     //     $templateData['about_us'] = AboutUsModel::where('deleted', 0)->first();
        //     //     break;

        //     //         // case 'about': ...
        //     //         // case 'contact': ...
        //     // }
        //      switch ($page->template) {
        //         case 'hero':
        //             // Fetch hero section data
        //             $templateData['hero'] = HeroModel::where('deleted', 0)->where('status', 1)->first();
        //             break;
        //             case 'clients':
        //             // Fetch hero section data
        //             $templateData['clients'] = ClientModel::where('deleted', 0)->where('status', 1)->first();
        //             break;
        //             case 'about_us':
        //             // Fetch hero section data
        //             $templateData['about_us'] = AboutUsModel::where('deleted', 0)->where('status', 1)->first();
        //             break;
        //             case 'stats':
        //                 // Fetch hero section data
        //                 $templateData['stats'] = StatsModel::where('deleted', 0)->where('status', 1)->first();
        //                 break;
        //             case 'services':
        //             // Fetch services section data
        //             $templateData['services'] = ServiceModel::where('deleted', 0)->where('status', 1)->get();
        //             break;

        //             // case 'about': ...
        //             // case 'contact': ...
        //     }
        // }

        // dd($templateData);

        return view('cherrypik_front_layout.index', compact('pages'));
    }
}
