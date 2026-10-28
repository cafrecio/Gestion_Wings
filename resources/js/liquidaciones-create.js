// Nueva liquidación: profesor elegido, aviso sin asistencia e incluir hoy (T73, antes incrustado; T87 conteos por mes).
(function () {
    /* Datos de clases sin asistencia y liquidables por profesor y período */
    var datosEl = document.getElementById('liquidacion-create-datos');
    var profesoresData = datosEl ? JSON.parse(datosEl.dataset.profesores || '{}') : {};

    var avisoEl   = document.getElementById('aviso-sin-asistencia');
    var textoEl   = document.getElementById('aviso-sin-asistencia-texto');
    var linkEl    = document.getElementById('aviso-sin-asistencia-link');
    var mesEl     = document.getElementById('mes');
    var anioEl    = document.getElementById('anio');

    function obtenerDatosPeriodo(profesorId, anio, mes) {
        var data = profesoresData[profesorId];
        if (!data) return { liquidables: 0, sin_asistencia: 0, nombre: '', total_pendientes: 0 };
        var clave = anio + '-' + parseInt(mes, 10);
        var p = (data.periodos && data.periodos[clave]) ? data.periodos[clave] : {};
        var fecha = String(anio).padStart(4, '0') + '-' + String(mes).padStart(2, '0') + '-01';
        var vigencia = (data.vigencias || []).find(function (v) { return v.desde <= fecha; });
        return {
            tipo: vigencia ? vigencia.tipo : data.tipo_base,
            sin_porcentaje: !!data.sin_porcentaje,
            nombre: data.nombre,
            liquidables: (typeof p.liquidables === 'number') ? p.liquidables : 0,
            sin_asistencia: (typeof p.sin_asistencia === 'number') ? p.sin_asistencia : 0,
            total_pendientes: data.total_pendientes || 0
        };
    }

    function actualizarAvisoSinAsistencia(profesorId) {
        if (!avisoEl) return;
        var mes = mesEl ? mesEl.value : '';
        var anio = anioEl ? anioEl.value : '';
        var info = obtenerDatosPeriodo(profesorId, anio, mes);

        if (!info || info.sin_asistencia === 0) {
            avisoEl.style.display = 'none';
            return;
        }
        var n   = info.sin_asistencia;
        var txt = info.nombre + ' tiene ' + n + ' clase' + (n === 1 ? '' : 's')
                + ' sin asistencia cargada que no se incluir' + (n === 1 ? 'á' : 'án')
                + ' en esta liquidación. Podés cargar las asistencias antes de continuar.';
        textoEl.textContent = txt;
        linkEl.href = '/clases?estado=finalizada&profesor_id=' + profesorId;
        avisoEl.style.display = 'flex';
    }

    function actualizarConteos() {
        var mes = mesEl ? mesEl.value : '';
        var anio = anioEl ? anioEl.value : '';

        document.querySelectorAll('.profesor-radio-row').forEach(function (row) {
            var radio = row.querySelector('input[type=radio][name=profesor_id]');
            if (!radio) return;
            var profId = radio.value;
            var info = obtenerDatosPeriodo(profId, anio, mes);
            var tipoEl = row.querySelector('.profesor-tipo');
            if (tipoEl) tipoEl.textContent = info.tipo === 'HORA' ? 'Por hora' : 'Por comisión';
            var porcentajeEl = row.querySelector('.profesor-sin-porcentaje');
            if (porcentajeEl) porcentajeEl.hidden = info.tipo !== 'COMISION' || !info.sin_porcentaje;

            var countEl = row.querySelector('.profesor-clases-count');
            if (countEl) {
                if (radio.disabled) {
                    countEl.textContent = 'Sin clases pendientes';
                    countEl.style.color = 'var(--color-text-muted)';
                    countEl.style.fontWeight = '500';
                } else {
                    var n = info.liquidables;
                    countEl.textContent = n + ' ' + (n === 1 ? 'clase liquidable' : 'clases liquidables');
                    if (n > 0) {
                        countEl.style.color = 'var(--color-success)';
                        countEl.style.fontWeight = '600';
                    } else {
                        countEl.style.color = 'var(--color-text-muted)';
                        countEl.style.fontWeight = '500';
                    }
                }
            }

            var sinAsisEl = row.querySelector('.profesor-sin-asistencia-aviso');
            if (sinAsisEl) {
                var s = info.sin_asistencia;
                if (s > 0) {
                    sinAsisEl.style.display = 'block';
                    sinAsisEl.textContent = '\u26A0 ' + s + ' ' + (s === 1 ? 'clase sin asistencia' : 'clases sin asistencia') + ' \u2014 no se liquidar\u00E1n';
                } else {
                    sinAsisEl.style.display = 'none';
                }
            }
        });

        var checkedRadio = document.querySelector('.profesor-radio-row input[type=radio]:checked');
        if (checkedRadio) {
            actualizarAvisoSinAsistencia(checkedRadio.value);
        } else if (avisoEl) {
            avisoEl.style.display = 'none';
        }
    }

    if (mesEl) {
        mesEl.addEventListener('change', actualizarConteos);
    }
    if (anioEl) {
        anioEl.addEventListener('input', actualizarConteos);
        anioEl.addEventListener('change', actualizarConteos);
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
