import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/grupos.js',
                'resources/js/profesores-form.js',
                'resources/js/usuarios.js',
                'resources/js/alumnos-inscripcion.js',
                'resources/js/alumnos-form.js',
                'resources/js/alumnos-show.js',
                'resources/js/clases-form.js',
                'resources/js/cobrar.js',
                'resources/js/configuraciones.js',
                'resources/js/primera-carga.js',
                'resources/js/caja-arqueo.js',
                'resources/js/caja-detalle.js',
                'resources/js/caja-movimiento.js',
                'resources/js/caja-editar.js',
                'resources/js/clases-show.js',
                'resources/js/clases-index.js',
                'resources/js/liquidaciones-create.js',
                'resources/js/liquidaciones-show.js',
                'resources/js/alumnos-index.js',
                'resources/js/cashflow-movimiento.js',
                'resources/js/reportes.js',
                'resources/js/reportes-visual.js',
                'resources/css/reportes-visual.css',
                'resources/css/reportes-analiticos.css',
                'resources/js/reportes-analiticos.js',
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
