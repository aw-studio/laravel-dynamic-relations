<?php

namespace Tests\Fixtures;

use AwStudio\DynamicRelations\HasDynamicRelations;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasDynamicRelations;
}
