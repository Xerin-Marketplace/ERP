<?php

namespace Modules\SystemAdministration\Models;

use Illuminate\Database\Eloquent\Model;

class DataImportAlias extends Model
{
    protected $table = 'data_import_aliases';
    protected $fillable = ['entity', 'field', 'alias', 'canonical'];
}
