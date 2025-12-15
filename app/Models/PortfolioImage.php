<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PortfolioImage extends Model
{
    protected $fillable = ['portfolio_id', 'image_path', 'sort_order', 'is_main', 'status'];

    public function portfolio()
    {
        return $this->belongsTo(PortfolioModel::class, 'portfolio_id');
    }
}
