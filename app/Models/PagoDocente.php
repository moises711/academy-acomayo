<?php

namespace App\Models;

use Database\Factories\PagoDocenteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoDocente extends Model
{
    /** @use HasFactory<PagoDocenteFactory> */
    use HasFactory;

    protected $table = 'pagos_docentes';

    protected $primaryKey = 'id_pago_docente';

    protected $fillable = [
        'id_ciclo', 'id_profesor', 'id_usuario', 'monto', 'fecha_pago', 'periodo', 'metodo_pago',
        'nro_comprobante', 'estado', 'motivo_anulacion', 'anulado_por', 'anulado_en',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'fecha_pago' => 'date',
            'anulado_en' => 'datetime',
        ];
    }

    public function ciclo(): BelongsTo
    {
        return $this->belongsTo(Ciclo::class, 'id_ciclo', 'id_ciclo');
    }

    public function profesor(): BelongsTo
    {
        return $this->belongsTo(Profesor::class, 'id_profesor', 'id_profesor');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }

    public function anulador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'anulado_por', 'id_usuario');
    }
}
