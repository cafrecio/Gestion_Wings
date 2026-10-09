import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/sidebar.css',
                'resources/js/app.js',
                'resources/js/grupos.js',
                'resources/js/usuarios.js',
                'resources/js/alumnos-inscripcion.js',
                'resources/js/alumnos-form.js',
                'resources/js/clases-form.js',
                'resources/js/cobrar.js',
                'resources/js/configuraciones.js',
                'resources/js/primera-carga.js',
                'resources/js/caja-arqueo.js',
                'resources/js/caja-movimiento.js',
                'resources/js/reportes.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
