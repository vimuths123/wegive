<?php

namespace App\Models;

use App\Jobs\ProcessSalesforceGAUIntegration;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fund extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'active'];


    public static function boot()
    {
        parent::boot();

        self::updated(
            function ($model) {
                $s = $model->organization->salesforceIntegration;
                if ($s && $s->enabled) {
                    ProcessSalesforceGAUIntegration::dispatch($s, $model);
                }
            }
        );

        self::created(function ($model) {
            $s = $model->organization->salesforceIntegration;
            if ($s && $s->enabled) {
                if (!$model->salesforce_id) {
                    ProcessSalesforceGAUIntegration::dispatch($s, $model);
                }
            }
        });
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }


    public function donors()
    {
        $fund_id = $this->id;
        return User::whereHas('transactions', function ($query) use ($fund_id) {
            $query->where('fund_id', $fund_id);
        });
    }
}
