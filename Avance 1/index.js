// ===========================
// CAROUSEL HERO DINÁMICO
// ===========================

const cardCarousel  = document.getElementById('cardCarousel');
const heroBg        = document.getElementById('heroBg');
const heroTitulo    = document.getElementById('heroTitulo');
const heroSede      = document.getElementById('heroSede');
const heroAnio      = document.getElementById('heroAnio');
const heroBtnExplore = document.getElementById('heroBtnExplore');

// Leer datos de cada slide del carousel oculto
function getDatosSlide(index) {
    const items = cardCarousel.querySelectorAll('.carousel-item');
    if (!items[index]) return null;
    const img = items[index].querySelector('img');
    return {
        id:     img ? img.src.match(/id=(\d+)/)?.[1] : null,
        nombre: items[index].closest ? null : null,
        src:    img ? img.src : null
    };
}

// Actualizar hero con datos del slide activo
function actualizarHero() {
    const activeItem = cardCarousel.querySelector('.carousel-item.active');
    if (!activeItem) return;

    const img    = activeItem.querySelector('img');
    const nombre = activeItem.querySelector('.card-overlay-text span')?.textContent || '';

    // Buscar id del mundial en la URL de la imagen
    const match  = img?.src.match(/id=(\d+)/);
    const id     = match ? match[1] : null;

    // Cambiar fondo con transición
    if (img) {
        heroBg.style.opacity = '0';
        setTimeout(() => {
            heroBg.style.backgroundImage = `url('${img.src}')`;
            heroBg.style.opacity = '1';
        }, 300);
    }

    // Cambiar texto con fade
    heroTitulo.style.opacity = '0';
    heroSede.style.opacity   = '0';
    heroAnio.style.opacity   = '0';

    setTimeout(() => {
        // Extraer sede y anio del nombre
        // Los datos están en el carousel oculto
        const hiddenItems = document.querySelectorAll('#heroCarousel .carousel-item');
        let sede = '', anio = '';

        hiddenItems.forEach(item => {
            if (item.dataset.id === id) {
                sede = item.dataset.sede;
                anio = item.dataset.anio;
            }
        });

        heroTitulo.textContent = 'Explora ' + sede;
        heroSede.textContent   = nombre;
        heroAnio.textContent   = anio;

        if (id) {
            heroBtnExplore.href = 'detalle_mundial.php?id=' + id;
        }

        heroTitulo.style.opacity = '1';
        heroSede.style.opacity   = '1';
        heroAnio.style.opacity   = '1';
    }, 300);
}

// Inicializar con el primer slide
actualizarHero();

// Escuchar cambios del carousel
cardCarousel.addEventListener('slid.bs.carousel', actualizarHero);

// Auto-play manual sincronizado
setInterval(() => {
    const next = cardCarousel.querySelector('.carousel-control-next');
    if (next) next.click();
}, 4000);