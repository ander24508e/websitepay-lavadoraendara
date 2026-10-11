<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VehicleSpecification;
use Illuminate\Http\Request;
use App\Models\Role;

class ClientesController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $clientes = User::query()
            ->role('cliente')
            ->withCount('orders')
            ->withSum('orders as total_compras', 'total')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('telefono', 'like', "%{$search}%");
                });
            })
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total_clientes' => (int) User::query()->role('cliente')->count(),
            'nuevos_mes' => (int) User::query()->role('cliente')->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
            'clientes_con_compras' => (int) User::query()->role('cliente')->has('orders')->count(),
            'ingreso_total_clientes' => (float) (\App\Models\Order::query()->whereHas('user.roles', fn ($q) => $q->where('name', 'cliente'))->sum('total')),
        ];

        return view('admin.clientes.index', compact('clientes', 'stats', 'search'));
    }

    public function show(User $cliente)
    {
        abort_unless($cliente->hasRole('cliente'), 404);

        $cliente->load([
            'orders' => fn ($q) => $q->with(['items.itemable', 'transaction'])->orderBy('id'),
            'vehicles' => fn ($q) => $q
                ->with(['specification.brand:id,name', 'specification.model:id,name', 'specification.type:id,name'])
                ->orderBy('id'),
        ]);

        $resumen = [
            'total_ordenes' => (int) $cliente->orders->count(),
            'total_pagado' => (float) $cliente->orders->where('status', 'paid')->sum('total'),
            'total_reservado' => (int) $cliente->orders->where('status', 'reserved')->count(),
            'ultima_compra' => $cliente->orders->first()?->created_at,
        ];

        $vehicleSpecifications = VehicleSpecification::query()
            ->where('active', true)
            ->with(['brand:id,name', 'model:id,name,vehicle_brand_id', 'type:id,name'])
            ->ordered()
            ->get(['id', 'vehicle_brand_id', 'vehicle_model_id', 'vehicle_type_id', 'active']);

        return view('admin.clientes.show', compact('cliente', 'resumen', 'vehicleSpecifications'));
    }

    public function create()
    {
        return view('admin.clientes.create');
    }

    public function store(Request $request)
    {
        $cliente = $this->createClienteFromRequest($request);

        return redirect()->route('admin.clientes.show', $cliente)->with('success', 'Cliente creado correctamente.');
    }

    public function quickStore(Request $request)
    {
        $cliente = $this->createClienteFromRequest($request);

        return response()->json([
            'message' => 'Cliente creado correctamente.',
            'cliente' => [
                'id' => $cliente->id,
                'name' => $cliente->name,
                'email' => $cliente->email,
                'label' => "{$cliente->name} ({$cliente->email})",
            ],
        ], 201);
    }

    private function createClienteFromRequest(Request $request): User
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'direccion' => ['nullable', 'string', 'max:500'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $cliente = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'telefono' => trim((string) ($data['telefono'] ?? '')) ?: null,
            'direccion' => trim((string) ($data['direccion'] ?? '')) ?: null,
            'password' => $data['password'],
        ]);

        $cliente->syncRoles([Role::client()]);

        return $cliente;
    }

    public function edit(User $cliente)
    {
        abort_unless($cliente->hasRole('cliente'), 404);
        return view('admin.clientes.edit', compact('cliente'));
    }

    public function update(Request $request, User $cliente)
    {
        abort_unless($cliente->hasRole('cliente'), 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $cliente->id],
            'telefono' => ['nullable', 'string', 'max:50'],
            'direccion' => ['nullable', 'string', 'max:500'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'telefono' => trim((string) ($data['telefono'] ?? '')) ?: null,
            'direccion' => trim((string) ($data['direccion'] ?? '')) ?: null,
        ];

        if (!empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        $cliente->update($payload);

        return redirect()->route('admin.clientes.show', $cliente)->with('success', 'Cliente actualizado correctamente.');
    }

    public function destroy(User $cliente)
    {
        abort_unless($cliente->hasRole('cliente'), 404);
        $cliente->delete();

        return redirect()->route('admin.clientes.index')->with('success', 'Cliente eliminado correctamente.');
    }
}
