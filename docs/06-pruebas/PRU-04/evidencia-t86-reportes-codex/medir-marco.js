const marco = document.getElementById("pantalla");
function medir() { const w = marco.contentWindow; const d = marco.contentDocument; const bloque = d.getElementById("pagos-operativas"); if (marco.src.includes("#pagos-operativas") && bloque) w.scrollTo(0, Math.max(0, bloque.getBoundingClientRect().top + w.scrollY - 140)); document.getElementById("medida").textContent = `Marco 375 px · ancho real ${w.innerWidth} px · contenido ${d.documentElement.scrollWidth} px`; }
marco.addEventListener("load", () => setTimeout(medir, 1000));
setTimeout(medir, 3500);
