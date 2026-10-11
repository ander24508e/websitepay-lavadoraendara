import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/scss/website.scss',
                'resources/scss/checkout.scss',
                'resources/scss/checkout-confirmacion.scss',
                'resources/scss/checkout-comprobante.scss',
                'resources/scss/Catalogo/catalogo-products.scss',
                'resources/scss/Catalogo/catalogo-services.scss',
                'resources/scss/auth/login.scss',
                'resources/scss/auth/register.scss',
                'resources/scss/profile/profile-edit.scss',
                'resources/scss/profile/customer-perfil.scss',
                'resources/scss/profile/customer-compras.scss',
                'resources/scss/carrito.scss',
                'resources/scss/admin/layout.scss',
                'resources/scss/admin/ventas-create.scss',
                'resources/scss/admin/orders-index.scss',
                'resources/scss/admin/orders-show.scss',
                'resources/scss/admin/admin-data-tables.scss',
                'resources/scss/admin/roles.scss',
                'resources/js/app.js'
            ],
            refresh: true,
        }),
    ],
});
