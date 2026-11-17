<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Pago extends Model
{
    protected $table = 'pagos';

    protected $primaryKey = 'id_pago';

    protected $fillable = [
        'id_matricula', 'id_usuario', 'fecha_pago', 'monto', 'metodo_pago',
        'tipo_comprobante', 'nro_comprobante', 'estado', 'motivo_anulacion',
        'anulado_por', 'anulado_en',
    ];

    protected function casts(): array
    {
        return [
            'fecha_pago' => 'datetime',
            'monto' => 'decimal:2',
            'anulado_en' => 'datetime',
        ];
    }

    public function matricula(): BelongsTo
    {
        return $this->belongsTo(Matricula::class, 'id_matricula', 'id_matricula');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }

    public function anuladoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'anulado_por', 'id_usuario');
    }

    public function cuotas(): BelongsToMany
    {
        return $this->belongsToMany(Cuota::class, 'pagos_cuotas', 'id_pago', 'id_cuota')
            ->using(PagosCuota::class)
            ->withPivot('monto_aplicado');
    }

    public function anular(User $user, string $motivo): void
    {
        $this->forceFill([
            'estado' => 'anulado',
            'motivo_anulacion' => $motivo,
            'anulado_por' => $user->id_usuario,
            'anulado_en' => now(),
        ])->save();
    }
}
