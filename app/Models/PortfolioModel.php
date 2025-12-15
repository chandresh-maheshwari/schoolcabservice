<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PortfolioModel extends Model
{
    use HasFactory;
    protected $table = 'portfolio';

    protected $fillable = [
        'title',
        'short_desc',
        'description',
        'image',
        'portfolio_info_title_1',
        'portfolio_info_1',
        'portfolio_info_title_2',
        'portfolio_info_2',
        'portfolio_info_title_3',
        'portfolio_info_3',
        'portfolio_info_title_4',
        'portfolio_info_4',
        'button_title',
        'button_link',
        'category_id',
        'status',
        'deleted'
    ];

    /**
     * Get the category that owns the portfolio.
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function images()
    {
        return $this->hasMany(PortfolioImage::class, 'portfolio_id')->orderBy('sort_order');
    }

    public static function getPortfolioData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage)
    {
        $columnSortOrder = in_array($columnSortOrder, ['asc', 'desc']) ? $columnSortOrder : 'asc';

        $query = DB::table('portfolio')
        ->leftJoin('categories', 'portfolio.category_id', 'categories.id')
            ->where('portfolio.deleted', 0)
            ->select('portfolio.id', 'portfolio.title', 'portfolio.description', 'portfolio.image', 'portfolio.status', 'categories.name')
            ->when($searchValue, function ($query, $searchValue) {
                return $query->where(function ($q) use ($searchValue) {
                    $q->where('portfolio.title', 'like', '%' . $searchValue . '%')
                      ->orWhere('portfolio.description', 'like', '%' . $searchValue . '%')
                      ->orWhere('categories.name', 'like', '%' . $searchValue . '%');
                });
            })
            ->orderBy($columnName, $columnSortOrder)
            ->skip($row)
            ->take($rowperpage)
            ->get();

        return $query;
    }

    public static function getPortfolioDataTotal($searchValue)
    {
        $query = DB::table('portfolio')
        ->leftJoin('categories', 'portfolio.category_id', 'categories.id')
            ->select('portfolio.id', 'portfolio.title', 'portfolio.description', 'portfolio.image', 'portfolio.status', 'categories.name')

            ->when($searchValue, function ($query, $searchValue) {
                return $query->where(function ($q) use ($searchValue) {
                    $q->where('portfolio.title', 'like', '%' . $searchValue . '%')
                      ->orWhere('portfolio.description', 'like', '%' . $searchValue . '%')
                      ->orWhere('categories.name', 'like', '%' . $searchValue . '%');
                });
            })
            ->where('portfolio.deleted', 0)
            ->count();

        return $query;
    }

}
