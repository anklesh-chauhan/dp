import * as pdfjsLib from 'pdfjs-dist';
import pdfWorkerUrl from 'pdfjs-dist/build/pdf.worker.min.mjs?url';

pdfjsLib.GlobalWorkerOptions.workerSrc = pdfWorkerUrl;

const viewer = document.getElementById('controlled-pdf-viewer');

if (viewer) {
    const pagesContainer = viewer.querySelector('[data-role="pages-container"]');
    const loading = viewer.querySelector('[data-role="loading"]');
    const pageStatus = viewer.querySelector('[data-role="pages"]');
    const zoomStatus = viewer.querySelector('[data-role="zoom"]');
    const watermark = viewer.dataset.watermark;
    let pdfDocument;
    let scale = 1.25;

    const renderDocument = async () => {
        pagesContainer.replaceChildren();

        for (let pageNumber = 1; pageNumber <= pdfDocument.numPages; pageNumber += 1) {
            const page = await pdfDocument.getPage(pageNumber);
            const viewport = page.getViewport({ scale });
            const pageElement = document.createElement('section');
            const canvas = document.createElement('canvas');
            const context = canvas.getContext('2d', { alpha: false });
            const watermarkElement = document.createElement('div');
            const watermarkText = document.createElement('span');

            pageElement.className = 'viewer-page';
            canvas.width = Math.floor(viewport.width);
            canvas.height = Math.floor(viewport.height);
            canvas.setAttribute('aria-label', `Page ${pageNumber} of ${pdfDocument.numPages}`);
            watermarkElement.className = 'viewer-watermark';
            watermarkText.textContent = watermark;
            watermarkElement.append(watermarkText);
            pageElement.append(canvas, watermarkElement);
            pagesContainer.append(pageElement);

            await page.render({ canvasContext: context, viewport }).promise;
        }

        loading.hidden = true;
        pageStatus.textContent = `${pdfDocument.numPages} page${pdfDocument.numPages === 1 ? '' : 's'}`;
        zoomStatus.textContent = `${Math.round((scale / 1.25) * 100)}%`;
    };

    const showError = (error) => {
        loading.className = 'viewer-error';
        loading.textContent = error?.message?.includes('PDF generation service is not running')
            ? error.message
            : 'The controlled PDF could not be displayed. Your access may have expired.';
        console.error(error);
    };

    const loadPdf = async () => {
        const response = await fetch(viewer.dataset.pdfUrl, {
            credentials: 'include',
            headers: { Accept: 'application/pdf' },
        });

        if (!response.ok) {
            const message = (await response.text()).trim();

            throw new Error(message || `PDF request failed with status ${response.status}.`);
        }

        return pdfjsLib.getDocument({ data: await response.arrayBuffer() }).promise;
    };

    loadPdf()
        .then((document) => {
            pdfDocument = document;
            return renderDocument();
        })
        .catch(showError);

    viewer.querySelector('[data-action="zoom-in"]').addEventListener('click', () => {
        scale = Math.min(2.5, scale + 0.25);
        renderDocument().catch(showError);
    });

    viewer.querySelector('[data-action="zoom-out"]').addEventListener('click', () => {
        scale = Math.max(0.75, scale - 0.25);
        renderDocument().catch(showError);
    });

    document.addEventListener('contextmenu', (event) => event.preventDefault());
    document.addEventListener('keydown', (event) => {
        if ((event.ctrlKey || event.metaKey) && ['p', 's'].includes(event.key.toLowerCase())) {
            event.preventDefault();
        }
    });
}
