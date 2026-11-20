<?php
namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;

class ContractVersion extends Model
{
    protected $fillable = ['contract_id', 'version', 'terms', 'status', 'created_by'];
    protected $casts = ['terms' => 'array'];

    public function contract() { return $this->belongsTo(CommercialContract::class); }
}
