<?php

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogsActivity;
use Spatie\Activitylog\Traits\LogsActivity as LogsActivityTrait;

class TestProduct extends Model implements LogsActivity
{
    use HasFactory, LogsActivityTrait;

    protected $table = 'test_products';

    protected $fillable = [
        'name',
        'price',
        'stock_quantity',
        'category',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected static function newFactory()
    {
        return \Workbench\Database\Factories\TestProductFactory::new();
    }
}