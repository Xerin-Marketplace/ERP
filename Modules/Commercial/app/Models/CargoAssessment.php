<?php

namespace Modules\Commercial\Models;

use Illuminate\Database\Eloquent\Model;

class CargoAssessment extends Model
{
    protected $guarded = ['id'];
    public const OUTCOMES = ['eligible','eligible_conditions','info_required','manual_review','rejected'];
    protected $casts = ['fragile'=>'boolean','liquid'=>'boolean','battery'=>'boolean','perishable'=>'boolean','dangerous_declared'=>'boolean'];
    public function inquiry() { return $this->belongsTo(Inquiry::class); }
    public function assessor() { return $this->belongsTo(\App\Models\User::class, 'assessed_by'); }
}
