<?php
namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerAcquisition extends Model
{
    const CHANNELS = ['direct','website','referral','agent','broker','corporate','social','walk_in','other'];
    protected $fillable = ['customer_id', 'channel', 'campaign_id', 'partner_id', 'officer_id',
        'registered_at', 'first_quotation_at', 'first_accepted_at', 'first_shipment_at', 'first_revenue_at'];
    protected $casts = ['registered_at' => 'datetime', 'first_quotation_at' => 'datetime',
        'first_accepted_at' => 'datetime', 'first_shipment_at' => 'datetime', 'first_revenue_at' => 'datetime'];

    public function customer() { return $this->belongsTo(\Modules\Commercial\Models\Customer::class); }
    public function campaign() { return $this->belongsTo(CommercialCampaign::class, 'campaign_id'); }
    public function partner()  { return $this->belongsTo(CommercialPartner::class, 'partner_id'); }
    public function officer()  { return $this->belongsTo(\App\Models\User::class, 'officer_id'); }
}
