@extends('layouts.admin')

@section('title', 'Mi Empresa')

@section('content')
    <div class="mb-8">
        <div class="flex items-center gap-3 mb-2">
            <x-heroicon-o-building-office class="w-8 h-8 text-gray-800" />
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Mi Empresa</h2>
            </div>
        </div>
    </div>

    @if (session('status') === 'empresa-updated')
        <div id="empresa-alert"
            class="mb-6 bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded-xl flex items-center justify-between">
            <div class="flex items-center gap-2">
                <x-heroicon-o-check-circle class="w-5 h-5" />
                <span>La informacion de la empresa se guardo correctamente.</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()"
                class="text-green-700 hover:text-green-900 text-lg leading-none">×</button>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.empresa.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <div
            class="sticky top-4 z-20 bg-white/95 backdrop-blur border border-gray-200 rounded-2xl shadow-sm px-4 py-3 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs uppercase tracking-[0.2em] text-gray-400 font-semibold">Edicion rapida</p>
                <h3 class="text-lg font-semibold text-gray-800">{{ old('nombre', $empresa->nombre_corto) }}</h3>
                <p class="text-sm text-gray-500">Usa las pestañas para moverte entre contacto, contenido y mapa.</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.dashboard') }}"
                    class="px-4 py-2.5 rounded-xl bg-gray-100 text-gray-700 font-medium text-sm hover:bg-gray-200 transition">
                    Cancelar
                </a>
                @can('company.update')
                <button type="submit"
                    class="px-5 py-2.5 rounded-xl bg-gray-900 text-white font-medium text-sm hover:bg-gray-700 transition flex items-center gap-2">
                    <x-heroicon-o-check class="w-4 h-4" />
                    Guardar Cambios
                </button>
                @endcan
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start">
            <!-- Columna izquierda: vista previa y resumen (sin cambios) -->
            <aside class="xl:col-span-4 space-y-6 xl:sticky xl:top-28">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-[0.2em] mb-4">Vista previa</p>
                    <div class="flex flex-col items-center text-center">
                        <img src="{{ $empresa->logo_url }}" id="logo-preview" alt="Logo actual"
                            class="w-32 h-32 rounded-2xl object-contain border border-gray-200 shadow-sm bg-gray-50 p-3 mb-3">
                        <h4 id="preview-name" class="text-xl font-bold text-gray-800">
                            {{ old('nombre', $empresa->nombre_corto) }}</h4>
                        <p id="preview-slogan" class="text-sm text-gray-500 mt-2">
                            {{ old('eslogan', $empresa->eslogan_texto) }}</p>
                    </div>

                    <input type="file" id="logo" name="logo" accept="image/*" class="hidden"
                        onchange="previewLogo(this)">
                    <button type="button" onclick="document.getElementById('logo').click()"
                        class="mt-5 w-full bg-gray-900 text-white py-2.5 rounded-xl hover:bg-gray-700 transition font-medium text-sm flex items-center justify-center gap-2">
                        <x-heroicon-o-cloud-arrow-up class="w-4 h-4" />
                        Cambiar Logo
                    </button>
                    <p id="file-name" class="text-xs text-gray-400 text-center mt-2">
                        {{ $empresa->logo ? basename($empresa->logo) : 'Ningun archivo seleccionado' }}
                    </p>
                    @error('logo')
                        <p class="text-red-500 text-xs mt-2 text-center">{{ $message }}</p>
                    @enderror
                    @if ($empresa->logo && auth()->user()->can('company.update'))
                        <button type="button" onclick="document.getElementById('delete-logo-form').submit()"
                            class="mt-4 w-full text-red-500 hover:text-red-700 text-sm font-medium py-2.5 rounded-xl hover:bg-red-50 transition flex items-center justify-center gap-2">
                            <x-heroicon-o-trash class="w-4 h-4" />
                            Eliminar logo actual
                        </button>
                    @endif
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-[0.2em] mb-4">Resumen</p>
                    <div class="space-y-4 text-sm">
                        <div>
                            <p class="text-gray-400 mb-1">Correo</p>
                            <p id="preview-email" class="text-gray-700 break-all">
                                {{ old('correo', $empresa->correo_contacto) }}</p>
                        </div>
                        <div>
                            <p class="text-gray-400 mb-1">Telefono</p>
                            <p id="preview-phone" class="text-gray-700">{{ old('telefono', $empresa->telefono_contacto) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-gray-400 mb-1">Direccion</p>
                            <p id="preview-address" class="text-gray-700">
                                {{ old('direccion', $empresa->direccion_completa) }}</p>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Columna derecha: pestañas (contacto, contenido, ubicación) -->
            <section class="xl:col-span-8 space-y-6">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-3">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <button type="button"
                            class="empresa-tab-btn rounded-xl px-4 py-3 text-sm font-semibold bg-gray-900 text-white"
                            data-tab="contacto">1. Contacto</button>
                        <button type="button"
                            class="empresa-tab-btn rounded-xl px-4 py-3 text-sm font-semibold bg-gray-100 text-gray-600"
                            data-tab="contenido">2. Contenido Web</button>
                        <button type="button"
                            class="empresa-tab-btn rounded-xl px-4 py-3 text-sm font-semibold bg-gray-100 text-gray-600"
                            data-tab="ubicacion">3. Google Maps</button>
                    </div>
                </div>

                <!-- Panel Contacto -->
                <div class="empresa-panel bg-white rounded-2xl shadow-sm border border-gray-100 p-6" data-panel="contacto">
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800">Datos principales</h3>
                        <p class="text-sm text-gray-500">Nombre, contacto y direccion del negocio.</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre de la empresa *</label>
                            <input type="text" name="nombre" id="nombre" value="{{ old('nombre', $empresa->nombre) }}"
                                class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-gray-400 text-sm @error('nombre') border-red-400 @enderror"
                                placeholder="Ej: Mi negocio">
                            @error('nombre')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Telefono</label>
                            <input type="tel" name="telefono" id="telefono"
                                value="{{ old('telefono', $empresa->telefono) }}"
                                class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-gray-400 text-sm @error('telefono') border-red-400 @enderror"
                                placeholder="Ej: +593 99 999 9999">
                            @error('telefono')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Correo</label>
                            <input type="email" name="correo" id="correo"
                                value="{{ old('correo', $empresa->correo) }}"
                                class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-gray-400 text-sm @error('correo') border-red-400 @enderror"
                                placeholder="Ej: contacto@negocio.com">
                            @error('correo')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ciudad o zona</label>
                            <input type="text" name="ciudad" id="ciudad"
                                value="{{ old('ciudad', $empresa->ciudad) }}"
                                class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-gray-400 text-sm @error('ciudad') border-red-400 @enderror"
                                placeholder="Ej: Cayambe, Ecuador">
                            @error('ciudad')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Horario</label>
                            <input type="text" name="horario" value="{{ old('horario', $empresa->horario) }}"
                                class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-gray-400 text-sm @error('horario') border-red-400 @enderror"
                                placeholder="Ej: Lunes a Sabado: 8:00 - 18:00">
                            @error('horario')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Direccion</label>
                            <textarea name="direccion" id="direccion" rows="4"
                                class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-gray-400 text-sm resize-none @error('direccion') border-red-400 @enderror"
                                placeholder="Ej: Cayambe, Pichincha, Ecuador">{{ old('direccion', $empresa->direccion) }}</textarea>
                            @error('direccion')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Facebook</label>
                            <input type="url" name="facebook_url"
                                value="{{ old('facebook_url', $empresa->facebook_url) }}"
                                class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-gray-400 text-sm @error('facebook_url') border-red-400 @enderror"
                                placeholder="https://facebook.com/tu-pagina">
                            @error('facebook_url')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Instagram</label>
                            <input type="url" name="instagram_url"
                                value="{{ old('instagram_url', $empresa->instagram_url) }}"
                                class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-gray-400 text-sm @error('instagram_url') border-red-400 @enderror"
                                placeholder="https://instagram.com/tu-cuenta">
                            @error('instagram_url')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">TikTok</label>
                            <input type="url" name="tiktok_url"
                                value="{{ old('tiktok_url', $empresa->tiktok_url) }}"
                                class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-gray-400 text-sm @error('tiktok_url') border-red-400 @enderror"
                                placeholder="https://tiktok.com/@tu-cuenta">
                            @error('tiktok_url')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Link de WhatsApp</label>
                            <input type="url" name="whatsapp_url"
                                value="{{ old('whatsapp_url', $empresa->whatsapp_url) }}"
                                class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-gray-400 text-sm @error('whatsapp_url') border-red-400 @enderror"
                                placeholder="https://wa.me/593... o https://api.whatsapp.com/send?...">
                            <p class="text-xs text-gray-400 mt-1">Si lo dejas vacio, se usa automaticamente el numero de
                                telefono.</p>
                            @error('whatsapp_url')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Panel Contenido Web -->
                <div class="empresa-panel hidden bg-white rounded-2xl shadow-sm border border-gray-100 p-6"
                    data-panel="contenido">
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800">Textos del sitio web</h3>
                        <p class="text-sm text-gray-500">Edita lo que aparece en portada, footer y resumen del negocio.</p>
                    </div>
                    <div class="grid grid-cols-1 gap-5">
                        <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4">
                            <h4 class="text-sm font-semibold text-gray-700 mb-3">Paleta de marca (3 colores)</h4>
                            <p class="text-xs text-gray-500 mb-4">Jerarquia automatica: Color 1 manda en botones y
                                acciones, Color 2 solo destaca etiquetas y pequenos acentos, Color 3 ayuda con contraste,
                                lineas y apoyo visual.</p>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Color primario</label>
                                    <input type="color" name="color_primario"
                                        value="{{ old('color_primario', $empresa->color_primario_hex) }}"
                                        class="h-12 w-full border border-gray-300 rounded-xl bg-white p-1 cursor-pointer @error('color_primario') border-red-400 @enderror">
                                    @error('color_primario')
                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Color secundario</label>
                                    <input type="color" name="color_secundario"
                                        value="{{ old('color_secundario', $empresa->color_secundario_hex) }}"
                                        class="h-12 w-full border border-gray-300 rounded-xl bg-white p-1 cursor-pointer @error('color_secundario') border-red-400 @enderror">
                                    @error('color_secundario')
                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Color terciario</label>
                                    <input type="color" name="color_terciario"
                                        value="{{ old('color_terciario', $empresa->color_terciario_hex) }}"
                                        class="h-12 w-full border border-gray-300 rounded-xl bg-white p-1 cursor-pointer @error('color_terciario') border-red-400 @enderror">
                                    @error('color_terciario')
                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Eslogan</label>
                            <input type="text" name="eslogan" id="eslogan"
                                value="{{ old('eslogan', $empresa->eslogan) }}"
                                class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-gray-400 text-sm @error('eslogan') border-red-400 @enderror"
                                placeholder="Ej: Servicio profesional de lavado y lubricacion">
                            @error('eslogan')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Descripcion corta</label>
                            <textarea name="descripcion_corta" rows="4"
                                class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-gray-400 text-sm resize-none @error('descripcion_corta') border-red-400 @enderror"
                                placeholder="Texto principal que se mostrara en la portada">{{ old('descripcion_corta', $empresa->descripcion_corta) }}</textarea>
                            @error('descripcion_corta')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Descripcion del footer</label>
                            <textarea name="descripcion_footer" rows="4"
                                class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-gray-400 text-sm resize-none @error('descripcion_footer') border-red-400 @enderror"
                                placeholder="Texto corto para el pie de pagina">{{ old('descripcion_footer', $empresa->descripcion_footer) }}</textarea>
                            @error('descripcion_footer')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Resumen de servicios</label>
                            <input type="text" name="servicios_resumen"
                                value="{{ old('servicios_resumen', $empresa->servicios_resumen) }}"
                                class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-gray-400 text-sm @error('servicios_resumen') border-red-400 @enderror"
                                placeholder="Ej: Lavado - Lubricacion - Mantenimiento">
                            @error('servicios_resumen')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Panel Google Maps -->
                <div class="empresa-panel hidden bg-white rounded-2xl shadow-sm border border-gray-100 p-6"
                    data-panel="ubicacion">
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800">Google Maps y vista previa</h3>
                        <p class="text-sm text-gray-500">Pega el iframe completo o solo la URL. Si lo dejas vacio, el mapa
                            se genera con la direccion o la ciudad.</p>
                    </div>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Iframe o URL de Google Maps</label>
                            <textarea name="ubicacion_embed" id="ubicacion_embed" rows="10"
                                class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-gray-400 text-sm resize-none @error('ubicacion_embed') border-red-400 @enderror"
                                placeholder='Ejemplo: <iframe src="..."></iframe> o https://www.google.com/maps/embed?...'>{{ old('ubicacion_embed', $empresa->ubicacion_embed) }}</textarea>
                            @error('ubicacion_embed')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                            <div
                                class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                                Consejo: si copias el bloque completo de Google Maps, el sistema sacara automaticamente el
                                enlace util del mapa.
                            </div>
                        </div>
                        <div>
                            <div class="rounded-2xl overflow-hidden border border-gray-200 bg-gray-50 h-[360px]">
                                <iframe id="map-preview" src="{{ $empresa->ubicacion_mapa_url }}" class="w-full h-full"
                                    loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                            </div>
                            <p class="text-xs text-gray-400 mt-3">La vista previa usa primero el campo del mapa. Si esta
                                vacio, usa la direccion o la ciudad.</p>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </form>

    @if ($empresa->logo)
        <form id="delete-logo-form" action="{{ route('admin.empresa.deleteLogo') }}" method="POST" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    @endif
@endsection

@push('scripts')
    <script>
        function previewLogo(input) {
            if (!input.files || !input.files[0]) return;
            const file = input.files[0];
            if (file.size > 4 * 1024 * 1024) {
                alert('La imagen es muy grande. Maximo 4MB.');
                input.value = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = e => {
                document.getElementById('logo-preview').src = e.target.result;
            };
            reader.readAsDataURL(file);
            document.getElementById('file-name').textContent = file.name;
        }

        function extractMapSrc(value) {
            if (!value) return '';
            const raw = value.trim();
            if (!raw) return '';
            const iframeMatch = raw.match(/src=["']([^"']+)["']/i);
            if (iframeMatch) return iframeMatch[1];
            if (raw.includes('google.com/maps/embed') || raw.includes('output=embed') || raw.includes('google.com/maps?q='))
                return raw;
            return `https://www.google.com/maps?q=${encodeURIComponent(raw)}&output=embed`;
        }

        function updateMapPreview() {
            const mapInput = document.getElementById('ubicacion_embed').value;
            const direccion = document.getElementById('direccion').value;
            const ciudad = document.getElementById('ciudad').value;
            const fallback = direccion.trim() || ciudad.trim() || 'Cayambe, Ecuador';
            const finalSrc = extractMapSrc(mapInput) ||
                `https://www.google.com/maps?q=${encodeURIComponent(fallback)}&output=embed`;
            document.getElementById('map-preview').src = finalSrc;
        }

        function wirePreview(id, targetId, fallback = '') {
            const input = document.getElementById(id);
            const target = document.getElementById(targetId);
            if (!input || !target) return;
            const apply = () => {
                const value = input.value.trim();
                target.textContent = value || fallback;
            };
            input.addEventListener('input', apply);
            apply();
        }

        document.addEventListener('DOMContentLoaded', () => {
            const alert = document.getElementById('empresa-alert');
            if (alert) {
                setTimeout(() => {
                    alert.style.transition = 'opacity 0.4s';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 400);
                }, 4000);
            }
            const tabButtons = document.querySelectorAll('.empresa-tab-btn');
            const panels = document.querySelectorAll('.empresa-panel');

            function activateTab(tab) {
                tabButtons.forEach(btn => {
                    const active = btn.dataset.tab === tab;
                    btn.classList.toggle('bg-gray-900', active);
                    btn.classList.toggle('text-white', active);
                    btn.classList.toggle('bg-gray-100', !active);
                    btn.classList.toggle('text-gray-600', !active);
                });
                panels.forEach(panel => {
                    panel.classList.toggle('hidden', panel.dataset.panel !== tab);
                });
            }
            tabButtons.forEach(btn => {
                btn.addEventListener('click', () => activateTab(btn.dataset.tab));
            });
            activateTab('contacto');
            wirePreview('nombre', 'preview-name', 'Mi negocio');
            wirePreview('eslogan', 'preview-slogan', 'Servicio profesional para el cuidado de tu vehiculo');
            wirePreview('correo', 'preview-email', 'contacto@negocio.com');
            wirePreview('telefono', 'preview-phone', '+593 99 999 9999');
            wirePreview('direccion', 'preview-address', 'Cayambe, Ecuador');

            ['ubicacion_embed', 'direccion', 'ciudad'].forEach(id => {
                const field = document.getElementById(id);
                if (field) field.addEventListener('input', updateMapPreview);
            });
            updateMapPreview();
        });
    </script>
@endpush
