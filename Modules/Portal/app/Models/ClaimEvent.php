<?php
namespace Modules\Portal\Models;
use Illuminate\Database\Eloquent\Model;

class ClaimEvent extends Model
{
    protected $table = 'claim_events';
    protected $guarded = ['id', 'reference'];
    public function claim() { return $this->belongsTo(CargoClaim::class, 'claim_id'); }
}
