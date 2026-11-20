<?php
namespace Modules\Cargo\Models;
use Illuminate\Database\Eloquent\Model;
class Inspection extends Model
{
    protected $table = 'cargo_inspections';
    protected $guarded = ['id'];
    protected $casts = ['checklist' => 'array'];
    public const OUTCOMES = ['passed','passed_conditions','info_required','repack_required','compliance_review','rejected','on_hold'];
    public function package() { return $this->belongsTo(CargoPackage::class, 'package_id'); }
    public function inspector() { return $this->belongsTo(\App\Models\User::class, 'inspected_by'); }
}
