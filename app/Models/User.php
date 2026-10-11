<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'telefono',
        'direccion',
        'password',
        'foto_perfil',
        'active',
        'created_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'active' => 'boolean',
        'is_owner' => 'boolean',
    ];

    // URL de foto de perfil
    public function getFotoPerfilUrlAttribute()
    {
        return $this->foto_perfil
            ? asset('storage/'.$this->foto_perfil)
            : asset('Images/empresa-logo.jpg');
    }

    // Helpers de rol
    public function isOwner(): bool
    {
        return $this->is_owner === true;
    }

    /**
     * Personal interno: el propietario o cualquier usuario con un rol activo
     * distinto de cliente. Los roles se crean desde el modulo Roles.
     */
    public function isStaff(): bool
    {
        if ($this->isOwner()) {
            return true;
        }

        $role = $this->staffRole();

        return $role !== null && $role->active;
    }

    public function isClient(): bool
    {
        return $this->hasRole(Role::CLIENT);
    }

    /**
     * Rol interno del usuario (cada usuario tiene un solo rol).
     */
    public function staffRole(): ?Role
    {
        return $this->roles->first(fn ($role) => $role->name !== Role::CLIENT);
    }

    public function createdBy()
    {
        return $this->belongsTo(self::class, 'created_by');
    }

    public function audits()
    {
        return $this->hasMany(UserAudit::class, 'target_user_id');
    }

    // Relaciones
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function assignedOrders()
    {
        return $this->hasMany(Order::class, 'assigned_to');
    }

    public function attendedSales()
    {
        return $this->hasMany(Sale::class, 'attended_by');
    }

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }
}
