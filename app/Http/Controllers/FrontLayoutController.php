<?php

namespace App\Http\Controllers;

use App\Models\CherrypikPage;
use Illuminate\Http\Request;

class FrontLayoutController extends Controller
{
    // public function index()
    // {
    //     $pages = CherrypikPage::all();
    //     dd($pages);
    //     return view('cherrypik_front_layout.content', ['pages' => $pages]);
    // }
    
    public function setTheme(Request $request)
    {
        $theme = $request->input('theme', 'dark');
        session(['theme' => $theme]);
        return response()->json(['status' => 'ok']);
    }

    public function getPagesForFrontend()
    {
        try {
            $pages = CherrypikPage::get();

            return response()->json([
                'status' => true,
                'code' => 200,
                'message' => 'All Pages Data Fetch Successfully',
                'results' => $pages
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'code' => 500,
                'message' => 'Error fetching data: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Frontend - Header config (site name, links, cta)
     */
    public function getHeaderConfig()
    {
        try {
            $data = [
                'site_name' => config('app.site_name') ?? config('app.name') ?? 'Cherrypik',
                'site_url' => config('app.url') ?? '/',
                // Defaults; replace with DB-driven settings if available later
                'button_title' => 'Get Started',
                'button_link' => url('/#about'),
                // Optional logo path if you have it configured
                'logo' => null,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Header config retrieved successfully',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching header config: ' . $e->getMessage(),
            ], 500);
        }
    }
}
