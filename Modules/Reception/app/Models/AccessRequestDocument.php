<?php

namespace Modules\Reception\Models;

use Illuminate\Database\Eloquent\Model;

class AccessRequestDocument extends Model
{
    protected $table = 'access_request_documents';

    protected $fillable = ['request_id', 'file_id', 'category'];

    protected $casts = [
    ];

    public function request()
    {
        return $this->belongsTo(\Modules\Reception\Models\AccessRequest::class, 'request_id');
    }

    public function file()
    {
        return $this->belongsTo(\App\Models\ManagedFile::class, 'file_id');
    }
}
