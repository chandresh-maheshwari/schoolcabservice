<?php
namespace App\Http\Controllers;

use App\Helpers\ImageHelper;
use App\Models\CherrypikPage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CherrypikPageController extends Controller
{
    protected function getAvailableTemplates(): array
    {
        $templatesPath = resource_path('views/templates');
        if (! is_dir($templatesPath)) {
            return [];
        }

        $files     = scandir($templatesPath) ?: [];
        $templates = [];
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            if (str_ends_with($file, '.blade.php')) {
                $templates[] = substr($file, 0, -10);
            }
        }
        // dd($templates);
        sort($templates);
        return $templates;
    }

    public function index()
    {
        return view('cherrypik_pages.index');
    }

    // public function list(Request $request)
    // {
    //     $pages = CherrypikPage::where('deleted', 0)->orderBy('id','desc')->get(['id','title','slug','template','status']);
    //     return response()->json(['success'=>true,'data'=>$pages]);
    // }
    public function cherrypikPagesList(Request $request)
    {
        $draw        = $request->input('sEcho');
        $row         = $request->input('iDisplayStart');
        $rowperpage  = $request->input('iDisplayLength');
        $indexColumn = $request->input('iSortCol_0');
        $columnName  = $request->input('mDataProp_' . $indexColumn);

        if (! in_array($columnName, ['id', 'client'])) {
            $columnName = 'id';
        }

        $columnSortOrder = $request->input('sSortDir_0');
        $searchValue     = $request->input('sSearch');

        $pages                 = CherrypikPage::getPagesData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage);
        $totalRecords          = CherrypikPage::count();
        $totalRecordwithFilter = CherrypikPage::getPagesDataTotal($searchValue);

        $data = [];
        foreach ($pages as $page) {
            $imagePath = public_path($page->image);
            if (! file_exists($imagePath) || empty($page->image)) {
                $pageImage = 'images/Default.jpg';
            } else {
                $pageImage = $page->image;
            }
            // dd($page);
            $data[] = [
                'id'                => $page->id,
                'title'             => $page->title,
                'slug'              => $page->slug,
                'template'          => $page->template,
                'description'       => $page->description,
                'image'             => $pageImage,
                // 'image' => $page->image,
                'status'            => $page->status,
                'inner_page_status' => $page->inner_page_status,
            ];
        }

        $output = [
            "draw"            => intval($draw),
            "recordsTotal"    => $totalRecords,
            "recordsFiltered" => $totalRecordwithFilter,
            "data"            => $data,
        ];

        return response()->json($output);
    }

    public function create()
    {
        $templates = $this->getAvailableTemplates();
        return view('cherrypik_pages.create', compact('templates'));
    }

    public function store(Request $request)
    {

        $request->validate([
            'title'       => 'required|string|max:255',
            'template'    => 'required|string',
            'status'      => 'nullable|in:active,inactive',
            'description' => 'nullable|string',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,gif,webp',
        ]);
        $data = $request->only('title', 'template', 'status', 'description', 'hight', 'width');
        // normalize status to 1/0
        $data['status'] = ($request->status === 'active') ? 1 : 0;
        // $data['data'] = $request->input('data');
        $data['deleted'] = 0;
        // auto slug
        $slug = Str::slug($request->title);
        // ensure unique
        $suffix = 1;
        while (\App\Models\CherrypikPage::where('slug', $slug)->exists()) {
            $suffix++;
            $slug = Str::slug($request->title . '-' . $suffix);
        }
        $data['slug'] = $slug;

        $cherrypik_pages = CherrypikPage::create($data);
        // Handle image upload if a new image is provided
        if ($request->hasFile('image')) {

            // ----- SET SIZE FROM TEMPLATE -----
            $template = strtolower($request->template);

            if ($template == 'hero' || $template == 'about_us' || $template == 'feature') {
                $width  = 546;
                $height = 364;

            } elseif ($template == 'why_us') {
                $width  = 451;
                $height = 601;

            } elseif ($template == 'call_to_action') {
                $width  = 450;
                $height = 707;

            } else {
                $width  = 100;
                $height = 100;
            }

            // ----- GET IMAGE SIZE (MANUAL VALIDATION) -----
            list($imgWidth, $imgHeight) = getimagesize($request->file('image')->getRealPath());

            if ($imgWidth < $width || $imgHeight < $height) {
                return response()->json([
                    'success' => false,
                    'message' => "Image must be at least {$width}x{$height} pixels.",
                ], 422);
            }

            // ----- PROCESS IMAGE -----
            $image     = $request->file('image');
            $extension = $image->getClientOriginalExtension();
            $imageName = 'cherrypik_pages_' . $cherrypik_pages->id . '.' . $extension;

            $tmpPath  = $image->getRealPath();
            $destDir  = public_path('storage/cherrypik_pages');
            $destPath = $destDir . '/' . $imageName;

            if (! file_exists($destDir)) {
                mkdir($destDir, 0777, true);
            }

            $success = ImageHelper::cropAndResize($tmpPath, $destPath, $width, $height);

            if (! $success) {
                $cherrypik_pages->delete();
                return response()->json([
                    'success' => false,
                    'message' => "Invalid image type or image too small.",
                ], 422);
            }

            $data['image'] = 'storage/cherrypik_pages/' . $imageName;
        }

        $cherrypik_pages->update($data);

        return response()->json(['success' => true, 'message' => 'Page created Successfully']);
    }

    public function edit($id)
    {
        $page      = CherrypikPage::findOrFail($id);
        $templates = $this->getAvailableTemplates();
        return view('cherrypik_pages.edit', compact('page', 'templates'));
    }

    public function update(Request $request, $id)
    {
        $cherrypik_pages = CherrypikPage::findOrFail($id);

        $request->validate([
            'title'       => 'required|string|max:255',
            'template'    => 'required|string',
            'status'      => 'nullable|in:active,inactive',
            'description' => 'nullable|string',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,gif,webp',
        ]);

        // Prepare data for update
        $data = [
            'title'       => $request->title,
            'template'    => $request->template,
            'status'      => ($request->status === 'active') ? 1 : 0,
            'description' => $request->description,
            'width'       => $request->width,
            'hight'       => $request->hight,
            // Add more fields if needed
        ];

        // Handle image upload if a new image is provided
        if ($request->hasFile('image')) {

            // ----- GET TEMPLATE SIZE -----
            $template = strtolower($request->template);

            if ($template == 'hero' || $template == 'about_us' || $template == 'feature') {
                $width  = 546;
                $height = 364;

            } elseif ($template == 'why_us') {
                $width  = 451;
                $height = 601;

            } elseif ($template == 'call_to_action') {
                $width  = 450;
                $height = 707;

            } else {
                $width  = 100;
                $height = 100;
            }

            // ----- IMAGE DIMENSION VALIDATION -----
            list($imgWidth, $imgHeight) = getimagesize($request->file('image')->getRealPath());

            if ($imgWidth < $width || $imgHeight < $height) {
                return response()->json([
                    'success' => false,
                    'message' => "Image must be at least {$width}x{$height} pixels.",
                ], 422);
            }

            // ----- IMAGE PROCESSING -----
            $image     = $request->file('image');
            $extension = $image->getClientOriginalExtension();
            $imageName = 'cherrypik_pages_' . $cherrypik_pages->id . '.' . $extension;

            $tmpPath  = $image->getRealPath();
            $destDir  = public_path('storage/cherrypik_pages');
            $destPath = $destDir . '/' . $imageName;

            // Create dir if not exists
            if (! file_exists($destDir)) {
                mkdir($destDir, 0777, true);
            }

            // Resize without cropping
            $success = ImageHelper::cropAndResize($tmpPath, $destPath, $width, $height);

            if (! $success) {
                return response()->json([
                    'success' => false,
                    'message' => "Invalid image type or image too small.",
                ], 422);
            }

            // Save new image path
            $data['image'] = 'storage/cherrypik_pages/' . $imageName;
        }

        // Update the page
        $cherrypik_pages->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Page updated Successfully',
        ]);
    }

    public function destroy($id)
    {
        $page          = CherrypikPage::findOrFail($id);
        $page->deleted = 1;
        $page->save();
        return response()->json(['success' => true, 'message' => 'Page deleted Successfully']);
    }

    public function multiDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        if (! is_array($ids) || empty($ids)) {
            return response()->json(['success' => false, 'message' => 'No IDs provided.']);
        }
        CherrypikPage::whereIn('id', $ids)->update(['deleted' => 1]);
        return response()->json(['success' => true, 'message' => 'Selected pages are Deleted Successfully.']);
    }

    public function showFront($slug)
    {
        $page     = CherrypikPage::where(['slug' => $slug, 'deleted' => 0, 'status' => 1])->firstOrFail();
        $template = $page->template ?: 'default';
        $data     = $page->data ?? [];
        return view('pages.show', ['page' => $page, 'template' => $template] + $data);
    }

    public function showByTemplate($template)
    {
        // Try to find a page with this template
        $page = CherrypikPage::where(['template' => $template, 'deleted' => 0, 'status' => 1])
            ->first();

        if ($page) {
            // Use the page data
            $data = $page->data ?? [];
            return view('pages.show', ['page' => $page, 'template' => $template] + $data);
        }

        // If no page found, create a dummy page object for the template
        $dummyPage = (object) [
            'id'          => 0,
            'title'       => ucfirst(str_replace('_', ' ', $template)),
            'description' => 'This is a ' . $template . ' page.',
            'template'    => $template,
            'data'        => [],
        ];

        return view('pages.show', ['page' => $dummyPage, 'template' => $template]);
    }

    public function showBase()
    {
        // Try to find the first available page
        $firstPage = CherrypikPage::where(['deleted' => 0, 'status' => 1])
            ->orderBy('id', 'asc')
            ->first();

        if ($firstPage) {
            // Redirect to the first available page
            return redirect()->route('front.cp.page', ['slug' => $firstPage->slug]);
        }

        // If no pages exist, show a message or redirect to home
        return redirect('/')->with('message', 'No pages available yet.');
    }

    public function toggleStatus($id)
    {
        $page         = CherrypikPage::findOrFail($id);
        $page->status = ! $page->status;
        $page->save();

        return response()->json(['success' => true, 'message' => 'Cherrypik Page status updated Successfully.']);
    }

    public function toggleInnerPageStatus($id)
    {
        $page                    = CherrypikPage::findOrFail($id);
        $page->inner_page_status = ! $page->inner_page_status;
        $page->save();

        return response()->json(['success' => true, 'message' => 'Cherrypik Inner Page status updated Successfully.']);
    }

    public function deleteImage($id)
    {
        $page = CherrypikPage::findOrFail($id);
        if ($page->image) {
            $imagePath = public_path($page->image);
            if (file_exists($imagePath)) {
                @unlink($imagePath);
            }
            $page->image = null;
            $page->save();
            return response()->json(['success' => true, 'message' => 'Image deleted successfully.']);
        }
        return response()->json(['success' => false, 'message' => 'No image to delete.'], 404);
    }

    /**
     * API: Get all non-deleted pages
     */
    public function getAllPages()
    {
        $pages = CherrypikPage::where('deleted', 0)->get();
        return response()->json([
            'success' => true,
            'data'    => $pages,
        ]);
    }

    public function getNavbarPagesForFrontend(Request $request)
    {
        $pages = \App\Models\CherrypikPage::where('inner_page_status', 1)->orderBy('id', 'asc')->get();
        return response()->json(['success' => true, 'data' => $pages]);
    }

    public function deleteNavbarPageFrontend($id, Request $request)
    {
        $page = \App\Models\CherrypikPage::find($id);
        if (! $page) {
            return response()->json(['success' => false, 'message' => 'Page not found']);
        }
        $page->inner_page_status = 0;
        $page->save();
        // Return fresh visible pages list (optional for front-end refresh)
        $pages = \App\Models\CherrypikPage::where('inner_page_status', 1)->orderBy('sort_order', 'asc')->get();
        return response()->json(['success' => true, 'message' => 'Page deleted', 'data' => $pages]);
    }
}
