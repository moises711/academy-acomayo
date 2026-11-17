<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Cuota extends Model
{
    protected $table = 'cuotas';

    protected $primaryKey = 'id_cuota';

    protected $fillable = [
        'id_matricula', 'concepto', 'numero_cuota', 'monto_original',
        'monto_descuento', 'monto_final', 'fecha_vencimiento', 'estado_pago',
    ];

    protected function casts(): array
    {
        return [
            'monto_original' => 'decimal:2',
            'monto_descuento' => 'decimal:2',
            'monto_final' => 'decimal:2',
            'fecha_vencimiento' => 'date',
        ];
    }

    public function matricula(): BelongsTo
    {
        return $this->belongsTo(Matricula::class, 'id_matricula', 'id_matricula');
    }

    public function pagos(): BelongsToMany
    {
        return $this->belongsToMany(Pago::class, 'pagos_cuotas', 'id_cuota', 'id_pago')
            ->using(PagosCuota::class)
            ->withPivot('monto_aplicado');
    }
}
