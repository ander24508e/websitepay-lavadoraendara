<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    /**
     * Unico rol reservado por el sistema: identifica a los clientes.
     */
    public const CLIENT = 'cliente';

    protected $casts = [
        'active' => 'boolean',
        'is_system' => 'boolean',
    ];

    public static function client(): self
    {
        return static::query()->firstOrCreate(
            ['name' => self::CLIENT, 'guard_name' => 'web'],
            [
                'description' => 'Clientes de la tienda y del lavado. Rol reservado del sistema.',
                'active' => true,
                'is_system' => true,
            ]
        );
    }

    public function scopeManageable(Builder $query): Builder
    {
        return $query->where('is_system', false);
    }

    public function scopeAssignable(Builder $query): Builder
    {
        return $query->manageable()->where('active', true);
    }

    public function isClient(): bool
    {
        return $this->name === self::CLIENT;
    }
}
