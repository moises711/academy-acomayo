<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class PagosCuota extends Pivot
{
    protected $table = 'pagos_cuotas';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['id_pago', 'id_cuota', 'monto_aplicado'];
}
