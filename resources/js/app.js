// Livewire 3 incluye Alpine.js: no se importa por separado para evitar dos instancias.
import './bootstrap';

// Descarga de carteleras como imagen (se carga solo cuando hace falta).
window.downloadPoster = async (node, filename) => {
    const { toPng } = await import('html-to-image');
    await document.fonts.ready;
    const dataUrl = await toPng(node, { pixelRatio: 1, cacheBust: true, width: node.offsetWidth, height: node.offsetHeight });
    const a = document.createElement('a');
    a.href = dataUrl;
    a.download = filename;
    a.click();
};
