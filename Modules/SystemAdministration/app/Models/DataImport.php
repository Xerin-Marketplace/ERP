<?php

namespace Modules\SystemAdministration\Models;

use Illuminate\Database\Eloquent\Model;

class DataImport extends Model
{
    protected $table = 'data_imports';
    protected $guarded = ['id'];
    protected $casts = ['mapping' => 'array', 'errors' => 'array'];

    public function file() { return $this->belongsTo(\App\Models\ManagedFile::class); }
    public function user() { return $this->belongsTo(\App\Models\User::class); }
}
