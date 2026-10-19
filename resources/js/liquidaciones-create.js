// Nueva liquidación: profesor elegido, aviso sin asistencia e incluir hoy (T73, antes incrustado).
(function () {
    /* Datos de clases sin asistencia por profesor (inyectados desde PHP) */
    var profesoresData = JSON.parse(document.getElementById('liquidacion-create-datos').dataset.profesores);

    var avisoEl   = document.getElementById('aviso-sin-asistencia');
    var textoEl   = document.getElementById('aviso-sin-asistencia-texto');
    var linkEl    = document.getElementById('aviso-sin-asistencia-link');

    function actualizarAvisoSinAsistencia(profesorId) {
        if (!avisoEl) return;
        var data = profesoresData[profesorId];
        if (!data || data.sin_asistencia === 0) {
            avisoEl.style.display = 'none';
            return;
        }
        var n   = data.sin_asistencia;
        var txt = data.nombre + ' tiene ' + n + ' clase' + (n === 1 ? '' : 's')
                + ' sin asistencia cargada que no se incluir' + (n === 1 ? 'á' : 'án')
                + ' en esta liquidación. Podés cargar las asistencias antes de continuar.';
        textoEl.textContent = txt;
        linkEl.href = '/clases?estado=finalizada&profesor_id=' + profesorId;
        avisoEl.style.display = 'flex';
    }

    /* Hover / selected highlight + aviso sin asistencia */
    document.querySelectorAll('.profesor-radio-row input[type=radio]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            document.querySelectorAll('.profesor-radio-row').forEach(function (row) {
                row.classList.remove('selected');
            });
            if (this.checked) {
                this.closest('.profesor-radio-row').classList.add('selected');
                actualizarAvisoSinAsistencia(this.value);
            }
        });
    });

    /* Mostrar aviso si hay un profesor pre-seleccionado (old()) */
    var checkedRadio = document.querySelector('.profesor-radio-row input[type=radio]:checked');
    if (checkedRadio) {
        actualizarAvisoSinAsistencia(checkedRadio.value);
    }

    /* Incluir hoy toggle */
    window.toggleIncluirHoy = function (incluir) {
        document.getElementById('incluir-hoy').value = incluir ? '1' : '0';

        var btnIncluir   = document.getElementById('btn-incluir');
        var btnNoIncluir = document.getElementById('btn-no-incluir');
        if (!btnIncluir) return;

        if (incluir) {
            btnIncluir.style.background    = 'var(--color-warning)';
            btnIncluir.style.color         = '#fff';
            btnIncluir.style.border        = 'none';
            btnNoIncluir.style.background  = 'transparent';
            btnNoIncluir.style.border      = '1px solid var(--color-warning)';
            btnNoIncluir.style.color       = 'var(--color-warning)';
        } else {
            btnNoIncluir.style.background  = 'var(--color-warning)';
            btnNoIncluir.style.color       = '#fff';
            btnNoIncluir.style.border      = 'none';
            btnIncluir.style.background    = 'transparent';
            btnIncluir.style.border        = '1px solid var(--color-warning)';
            btnIncluir.style.color         = 'var(--color-warning)';
        }
    };
})();
