<?php

namespace App\Http\Controllers;

use App\Helpers\ImageHelper;
use App\Models\Category;
use App\Models\CMSCategory;
use App\Models\PortfolioImage;
use App\Models\PortfolioModel;
use App\Models\ServiceModel;
use Illuminate\Http\Request;

class PortfolioController extends Controller
{
    public function index()
    {
        return view('portfolio.index');
    }

    public function create()
    {
        $categories = Category::where('deleted', 0)->get();
        return view('portfolio.create', compact('categories'));
    }

    public function store(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'title' => 'required|string|max:255',
            'images' => 'required|array|min:1',
            'images.*' => 'mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
            'description' => 'required|string',
            'category_id' => 'required',
        ]);

        $data = $request->except('images');
        $portfolio = PortfolioModel::create($data);

        // Save multiple images to portfolio_images table
        if ($request->hasFile('images')) {
            $destDir = public_path('storage/portfolio');
            if (!file_exists($destDir)) {
                mkdir($destDir, 0777, true);
            }

            $imageStatusArr = json_decode($request->input('image_statuses', '[]'), true);
            $mainSet = false;
            foreach ($request->file('images') as $k => $imgFile) {

                $tmpPath = $imgFile->getRealPath();
                $imageName = 'portfolio_' . $portfolio->id . '_' . $k . '.' . $imgFile->getClientOriginalExtension();
                $destPath = $destDir . '/' . $imageName;

                $success = ImageHelper::resizeToPortfolioDimensions($tmpPath, $destPath, 400);
                if ($success) {
                    // dd($imageStatusArr[$k]);
                    $statusData = isset($imageStatusArr[$k-1]) ? $imageStatusArr[$k-1] : ['is_main' => ($mainSet ? 0 : 1), 'status' => 1];
                    $isMain = !empty($statusData['is_main']) && !$mainSet ? 1 : 0;
                    $status = !empty($statusData['status']) ? 1 : 0;

                    $portfolioImage = PortfolioImage::create([
                        'portfolio_id' => $portfolio->id,
                        'image_path' => 'storage/portfolio/' . $imageName,
                        'sort_order' => $k,
                        'is_main' => $isMain,
                        'status' => $status,
                    ]);
                    // dd($portfolioImage);

                    if ($isMain) {
                        $portfolio->update(['image' => $portfolioImage->image_path]);
                        $mainSet = true;
                    }
                }
            }
        }

        return response()->json(['success' => true, 'message' => 'Portfolio Created Successfully.']);
    }

    public function edit($id)
    {
        $categories = Category::where('deleted', 0)->get();
        $portfolio = PortfolioModel::with('images')->findOrFail($id);
        return view('portfolio.edit', compact('portfolio', 'categories'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'image' => 'sometimes|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
            'description' => 'required|string',
            'category_id' => 'required',
            'add_images.*' => 'mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
        ]);

        $portfolio = PortfolioModel::findOrFail($id);
        $data = $request->except(['add_images', 'delete_images']);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $extension = $image->getClientOriginalExtension();
            $imageName = 'portfolio_' . $portfolio->id . '.' . $extension;
            $tmpPath = $image->getRealPath();
            $destDir = public_path('storage/portfolio');
            $destPath = $destDir . '/' . $imageName;

            if (!file_exists($destDir)) {
                mkdir($destDir, 0777, true);
            }

            $success = ImageHelper::resizeToPortfolioDimensions($tmpPath, $destPath, 400);
            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Image must be at least 400 pixels wide and a valid image type.'
                ]);
            }
            $data['image'] = 'storage/portfolio/' . $imageName;
        }

        $portfolio->update($data);

        // Handle existing image status updates
        $updates = json_decode($request->input('existing_image_updates', '[]'), true);
        PortfolioImage::where('portfolio_id', $portfolio->id)->update(['is_main'=>0]);
        foreach ($updates as $img) {
            $row = PortfolioImage::where('id', $img['id'])->where('portfolio_id', $portfolio->id)->first();
            if ($row) {
                $row->is_main = !empty($img['is_main']) ? 1 : 0;
                $row->status = !empty($img['status']) ? 1 : 0;
                $row->save();
                if ($row->is_main) { $portfolio->update(['image'=>$row->image_path]); }
            }
        }

        // Handle additional images
        $newStatusArr = json_decode($request->input('new_image_statuses', '[]'), true);
        $mainSet = PortfolioImage::where('portfolio_id', $portfolio->id)->where('is_main', 1)->exists();
        if ($request->hasFile('add_images')) {
            $destDir = public_path('storage/portfolio');
            if (!file_exists($destDir)) {
                mkdir($destDir, 0777, true);
            }

            $maxSortOrder = $portfolio->images()->max('sort_order') ?? 0;

            foreach ($request->file('add_images') as $k => $imgFile) {
                $statusData = isset($newStatusArr[$k-1]) ? $newStatusArr[$k-1] : ['is_main'=>(!$mainSet && $k-1==0?1:0),'status'=>1];
                $isMain = !empty($statusData['is_main']) && !$mainSet ? 1 : 0;
                $status = !empty($statusData['status']) ? 1 : 0;

                $tmpPath = $imgFile->getRealPath();
                $imageName = 'portfolio_' . $portfolio->id . '_add_' . time() . '_' . $k . '.' . $imgFile->getClientOriginalExtension();
                $destPath = $destDir . '/' . $imageName;

                $success = ImageHelper::resizeToPortfolioDimensions($tmpPath, $destPath, 400);
                if ($success) {
                    $newImage = PortfolioImage::create([
                        'portfolio_id' => $portfolio->id,
                        'image_path' => 'storage/portfolio/' . $imageName,
                        'sort_order' => $maxSortOrder + $k + 1,
                        'is_main' => $isMain,
                        'status' => $status,
                    ]);
                    if ($isMain) { $portfolio->update(['image'=>$newImage->image_path]); $mainSet = true; }
                }
            }
        }

        // Handle main image selection
        if ($request->has('main_image_type')) {
            if ($request->main_image_type === 'existing' && $request->has('main_image_id')) {
                // Set existing image as main
                PortfolioImage::where('id', $request->main_image_id)
                    ->where('portfolio_id', $portfolio->id)
                    ->update(['is_main' => 1]);

                // Update portfolio main image path for backward compatibility
                $mainImage = PortfolioImage::find($request->main_image_id);
                if ($mainImage) {
                    $portfolio->update(['image' => $mainImage->image_path]);
                }
            } elseif ($request->main_image_type === 'new' && $request->has('main_image_index')) {
                // Set new image as main 
                $newImageIndex = $request->main_image_index;
                if (isset($newImageIds[$newImageIndex])) {
                    PortfolioImage::where('id', $newImageIds[$newImageIndex])
                        ->update(['is_main' => 1]);

                    // Update portfolio main image path for backward compatibility
                    $mainImage = PortfolioImage::find($newImageIds[$newImageIndex]);
                    if ($mainImage) {
                        $portfolio->update(['image' => $mainImage->image_path]);
                    }
                }
            }
        }

        // Handle delete images
        if ($request->has('delete_images')) {
            foreach ($request->delete_images as $imageId) {
                $portfolioImage = PortfolioImage::find($imageId);
                if ($portfolioImage && file_exists(public_path($portfolioImage->image_path))) {
                    @unlink(public_path($portfolioImage->image_path));
                }
                $portfolioImage->delete();
            }
        }

        return response()->json(['success' => true, 'message' => 'Portfolio Updated Successfully.']);
    }

    public function destroy($id)
    {
        $portfolio = PortfolioModel::findOrFail($id);
        $portfolio->deleted = 1;
        $portfolio->save();

        return response()->json(['success' => true, 'message' => 'Portfolio Deleted Successfully.']);
    }

    public function portfolioList(Request $request)
    {
        $draw = $request->input('sEcho');
        $row = $request->input('iDisplayStart');
        $rowperpage = $request->input('iDisplayLength');
        $indexColumn = $request->input('iSortCol_0');
        $columnName = $request->input('mDataProp_' . $indexColumn);

        if (!in_array($columnName, ['id', 'title', 'description', 'image', 'category', 'status'])) {
            $columnName = 'id';
        }

        $columnSortOrder = $request->input('sSortDir_0');
        $searchValue = $request->input('sSearch');

        $portfolio = PortfolioModel::getPortfolioData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage);
        $totalRecords = PortfolioModel::count();
        $totalRecordwithFilter = PortfolioModel::getPortfolioDataTotal($searchValue);

        $data = [];
        foreach ($portfolio as $portfolio) {
            $imagePath = public_path($portfolio->image);
            if (!file_exists($imagePath) || empty($portfolio->image)) {
                $portfolioImage = 'images/Default.jpg';
            } else {
                $portfolioImage = $portfolio->image;
            }
            $data[] = [
                'id' => $portfolio->id,
                'title' => $portfolio->title ?? '-',
                'description' => $portfolio->description ?? '-',
                'image' => $portfolioImage ?? '-',
                // 'image' => $portfolio->image ?? '-',
                'name' => $portfolio->name ?? '-',
                'portfolio' => $portfolio->portfolio ?? '-',
                'status' => $portfolio->status ?? '-',
            ];
        }

        $output = [
            "draw" => intval($draw),
            "recordsTotal" => $totalRecords,
            "recordsFiltered" => $totalRecordwithFilter,
            "data" => $data
        ];

        return response()->json($output);
    }

    public function multiDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        if (!is_array($ids) || empty($ids)) {
            return response()->json(['success' => false, 'message' => 'No IDs provided.']);
        }
        PortfolioModel::whereIn('id', $ids)->update(['deleted' => 1]);
        return response()->json(['success' => true, 'message' => 'Selected Data deleted Successfully.']);
    }

    public function toggleStatus($id)
    {
        $portfolio = PortfolioModel::findOrFail($id);
        $portfolio->status = !$portfolio->status;
        $portfolio->save();

        return response()->json(['success' => true, 'message' => 'Status Updated Successfully.']);
    }

    public function deleteImage($id)
    {
        $portfolio = PortfolioModel::findOrFail($id);
        if ($portfolio->image) {
            $imagePath = public_path($portfolio->image);
            if (file_exists($imagePath)) {
                @unlink($imagePath);
            }
            $portfolio->image = null;
            $portfolio->save();
            return response()->json(['success' => true, 'message' => 'Image deleted successfully.']);
        }
        return response()->json(['success' => false, 'message' => 'No image to delete.'], 404);
    }

    public function getActiveCount()
    {
        $activeCount = PortfolioModel::where('deleted', 0)->where('status', 1)->count();
        return response()->json(['count' => $activeCount]);
    }

    /**
     * API: Get all portfolio items for frontend
     */
    // public function getCategoriesForFrontend()
    // {
    //     $items = PortfolioModel::where('deleted', 0)
    //         ->leftJoin('categories', 'portfolio.category_id', 'categories.id')
    //         ->select('portfolio.id', 'portfolio.title', 'portfolio.description', 'portfolio.image', 'categories.name as category')
    //         ->orderBy('portfolio.id', 'desc')
    //         ->get();

    //     return response()->json([
    //         'success' => true,
    //         'data' => $items
    //     ]);
    // }

    /**
     * API: Get all portfolio items and categories for frontend
     */
    // public function getPortfolioAndCategoriesForFrontend()
    // {
    //     try {
    //         $categories = Category::where('deleted', 0)
    //             // ->select('id', 'name', 'category_link', 'category_icon')
    //             ->orderBy('order', 'asc')
    //             ->get();

    //         $portfolios = PortfolioModel::select(
    //             'portfolio.*',
    //             'categories.id as cat_id',
    //             'categories.name',
    //             'categories.category_link',
    //             'categories.category_icon')
    //             ->leftJoin('categories', 'portfolio.category_id', 'categories.id')
    //             ->orderBy('portfolio.id', 'desc')
    //             ->where('portfolio.deleted', 0)
    //             ->where('portfolio.status', 1)
    //             ->get();

    //         // Process portfolio items to include images with proper main image and status filtering
    //         $portfolios = $portfolios->map(function ($portfolio) {
    //             // Get all images for this portfolio
    //             $allImages = PortfolioImage::where('portfolio_id', $portfolio->id)
    //                 ->orderBy('sort_order')
    //                 ->get();

    //             // Get main image (for home page display)
    //             $mainImage = $allImages->where('is_main', 1)->first();
    //             if (!$mainImage && $allImages->count() > 0) {
    //                 // If no main image is set, use the first image
    //                 $mainImage = $allImages->first();
    //             }

    //             // Get active images (for detail page display)
    //             $activeImages = $allImages->where('status', 1);

    //             $portfolio->images = $activeImages; // All active images for detail page
    //             $portfolio->main_image = $mainImage; // Main image for home page

    //             return $portfolio;
    //         });

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Portfolio and categories retrieved successfully',
    //             'data' => [
    //                 'categories' => $categories,
    //                 'portfolios' => $portfolios,
    //             ]
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error retrieving data: ' . $e->getMessage(),
    //             'data' => null
    //         ], 500);
    //     }
    // }

    public function getPortfolioAndCategoriesForFrontend()
{
    try {
        $categories = Category::where('deleted', 0)
            ->orderBy('order', 'asc')
            ->get();

        $portfolios = PortfolioModel::select(
            'portfolio.*',
            'categories.id as cat_id',
            'categories.name',
            'categories.category_link',
            'categories.category_icon'
        )
            ->leftJoin('categories', 'portfolio.category_id', 'categories.id')
            ->orderBy('portfolio.id', 'desc')
            ->where('portfolio.deleted', 0)
            ->where('portfolio.status', 1)
            ->get();

        // Define folder and default image
        $folderPath = public_path('storage/portfolio/');
        $defaultImage = asset('images/Default.jpg');

        // Process portfolio items
        $portfolios = $portfolios->map(function ($portfolio) use ($folderPath, $defaultImage) {
            // ✅ Portfolio main image check
            if (!empty($portfolio->image)) {
                $imageName = basename($portfolio->image);
                $fullImagePath = $folderPath . $imageName;

                if (file_exists($fullImagePath)) {
                    $portfolio->image = asset('storage/portfolio/' . $imageName);
                } else {
                    $portfolio->image = $defaultImage;
                }
            } else {
                $portfolio->image = $defaultImage;
            }

            // ✅ Get all portfolio images
            $allImages = PortfolioImage::where('portfolio_id', $portfolio->id)
                ->orderBy('sort_order')
                ->get();

            // Process each image with existence check
            $allImages = $allImages->map(function ($img) use ($folderPath, $defaultImage) {
                $imageName = basename($img->image_path);
                $fullImagePath = $folderPath . $imageName;

                if (file_exists($fullImagePath)) {
                    $img->image_path = asset('storage/portfolio/' . $imageName);
                } else {
                    $img->image_path = $defaultImage;
                }

                return $img;
            });

            // ✅ Get main image (is_main = 1) or first image
            $mainImage = $allImages->where('is_main', 1)->first();
            if (!$mainImage && $allImages->count() > 0) {
                $mainImage = $allImages->first();
            }

            // ✅ Get active images (status = 1)
            $activeImages = $allImages->where('status', 1);

            $portfolio->images = $activeImages->values(); // reindex array
            $portfolio->main_image = $mainImage;

            return $portfolio;
        });

        return response()->json([
            'success' => true,
            'message' => 'Portfolio and categories retrieved successfully',
            'data' => [
                'categories' => $categories,
                'portfolios' => $portfolios,
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error retrieving data: ' . $e->getMessage(),
            'data' => null
        ], 500);
    }
}


    /**
     * Show single portfolio detail page
     */
    public function show($id)
    {
        $portfolio = PortfolioModel::where('id', $id)
            ->where('deleted', 0)
            ->where('status', 1)
            ->with(['category'])
            ->firstOrFail();

        // Get only active images for detail page
        $portfolio->images = PortfolioImage::where('portfolio_id', $portfolio->id)
            ->where('status', 1)
            ->orderBy('sort_order')
            ->get();

        return view('templates.portfolio-details', compact('portfolio'));
    }

    /**
     * API: Get single portfolio by ID for frontend
     */
    public function getPortfolioById($id)
    {
        try {
            $portfolio = PortfolioModel::where('id', $id)
                ->where('deleted', 0)
                ->where('status', 1)
                ->firstOrFail();

            // Validate image
            $portfolio->image = $this->validateAndGetImageUrl($portfolio->image);

            return response()->json([
                'success' => true,
                'data' => $portfolio
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Portfolio not found.'
            ], 404);
        }
    }

    /**
     * Validate image path and return safe URL with fallback
     */
    private function validateAndGetImageUrl($imagePath)
    {
        // If no image path provided, return default
        if (empty($imagePath)) {
            return 'images/Default.jpg';
        }

        // If it's already an absolute URL, return as is
        if (preg_match('/^https?:\/\//i', $imagePath)) {
            return $imagePath;
        }

        // Clean the path
        $cleanPath = ltrim($imagePath, '/');
        $fullPath = public_path($cleanPath);

        // Check if file exists and is a valid image
        if (file_exists($fullPath) && $this->isValidImage($fullPath)) {
            return $imagePath;
        }

        // Return default image if original doesn't exist or is invalid
        return 'images/Default.jpg';
    }

    /**
     * Check if file is a valid image
     */
    private function isValidImage($filePath)
    {
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        $mimeType = mime_content_type($filePath);
        return in_array($mimeType, $allowedTypes);
    }
}
