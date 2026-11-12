<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vaga extends Model
{
    protected $table = 'vagas';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'remota' => 'boolean',
            'publicada_em' => 'immutable_datetime',
            'vista_em' => 'immutable_datetime',
        ];
    }
}
