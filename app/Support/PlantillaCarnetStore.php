<?php

namespace App\Support;

use App\Models\Configuracion;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PlantillaCarnetStore
{
    private const PREFIX = 'carnet.plantilla.';

    private const DEFAULT_KEY = 'carnet.plantilla.default';

    /** @return Collection<int, array{id: string, config: array<string, mixed>}> */
    public function all(): Collection
    {
        return Configuracion::query()
            ->where('clave', 'like', self::PREFIX.'%')
            ->where('clave', '!=', self::DEFAULT_KEY)
            ->orderBy('clave')
            ->get(['clave', 'valor'])
            ->map(function (Configuracion $setting): array {
                return [
                    'id' => Str::after($setting->clave, self::PREFIX),
                    'config' => json_decode($setting->valor, true, flags: JSON_THROW_ON_ERROR),
                ];
            })->values();
    }

    /** @param array<string, mixed> $config */
    public function save(array $config, ?string $id = null): string
    {
        $id ??= (string) Str::uuid();
        $key = self::PREFIX.$id;

        Configuracion::query()->updateOrCreate(
            ['clave' => $key],
            [
                'valor' => json_encode($config, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                'descripcion' => 'Plantilla de carnet: '.mb_substr((string) $config['nombre'], 0, 150),
            ],
        );

        return $id;
    }

    public function setDefault(string $id): void
    {
        abort_unless(Configuracion::query()->whereKey(self::PREFIX.$id)->exists(), 404);

        Configuracion::query()->updateOrCreate(
            ['clave' => self::DEFAULT_KEY],
            ['valor' => $id, 'descripcion' => 'Plantilla predeterminada para carnets'],
        );
    }

    public function defaultId(): ?string
    {
        $id = Configuracion::query()->whereKey(self::DEFAULT_KEY)->value('valor');

        return is_string($id) && Configuracion::query()->whereKey(self::PREFIX.$id)->exists() ? $id : null;
    }

    /** @return array<string, mixed>|null */
    public function defaultConfig(): ?array
    {
        $id = $this->defaultId();

        return $id ? ($this->all()->firstWhere('id', $id)['config'] ?? null) : null;
    }

    public function delete(string $id): void
    {
        $wasDefault = $this->defaultId() === $id;
        Configuracion::query()->whereKey(self::PREFIX.$id)->delete();

        if ($wasDefault) {
            Configuracion::query()->whereKey(self::DEFAULT_KEY)->delete();
        }
    }
}
