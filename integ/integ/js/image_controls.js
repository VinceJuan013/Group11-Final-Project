 // Image viewer functionality
 const initializeImageViewer = () => {
    const image = document.getElementById('xrayImage');
    const zoomIn = document.getElementById('zoomIn');
    const zoomOut = document.getElementById('zoomOut');
    const resetZoom = document.getElementById('resetZoom');

    if (!image || !zoomIn || !zoomOut || !resetZoom) {
        console.log('Image viewer elements not found');
        return;
    }

    let scale = 1;
    let translateX = 0;
    let translateY = 0;

    // Zoom settings
    const ZOOM_SPEED = 0.2; // Increased for more noticeable zoom
    const MAX_ZOOM = 3;
    const MIN_ZOOM = 0.5;

    function updateTransform() {
        requestAnimationFrame(() => {
            image.style.transform = `translate(${translateX}px, ${translateY}px) scale(${scale})`;
        });
    }

    // Initialize image style
    image.style.cursor = 'grab';
    image.style.transformOrigin = 'center';
    image.style.transition = 'transform 0.1s ease-out';

    zoomIn.addEventListener('click', (e) => {
        e.preventDefault();
        if (scale < MAX_ZOOM) {
            scale += ZOOM_SPEED;
            updateTransform();
        }
    });

    zoomOut.addEventListener('click', (e) => {
        e.preventDefault();
        if (scale > MIN_ZOOM) {
            scale -= ZOOM_SPEED;
            updateTransform();
        }
    });

    resetZoom.addEventListener('click', (e) => {
        e.preventDefault();
        scale = 1;
        translateX = 0;
        translateY = 0;
        updateTransform();
    });

    // Pan functionality
    let isDragging = false;
    let startX, startY;

    function startDrag(e) {
        if (e.button !== 0) return; // Only left mouse button
        isDragging = true;
        startX = e.clientX - translateX;
        startY = e.clientY - translateY;
        image.style.cursor = 'grabbing';
        e.preventDefault();
    }

    function drag(e) {
        if (!isDragging) return;
        e.preventDefault();

        translateX = e.clientX - startX;
        translateY = e.clientY - startY;
        updateTransform();
    }

    function endDrag() {
        isDragging = false;
        image.style.cursor = 'grab';
    }

    // Mouse events for dragging
    image.addEventListener('mousedown', startDrag);
    window.addEventListener('mousemove', drag);
    window.addEventListener('mouseup', endDrag);

    // Mouse wheel zoom
    image.addEventListener('wheel', (e) => {
        e.preventDefault();
        const rect = image.getBoundingClientRect();
        const mouseX = e.clientX - rect.left;
        const mouseY = e.clientY - rect.top;

        const delta = -Math.sign(e.deltaY) * ZOOM_SPEED;
        const newScale = Math.min(MAX_ZOOM, Math.max(MIN_ZOOM, scale + delta));

        if (newScale !== scale) {
            scale = newScale;
            updateTransform();
        }
    });

    // Touch events for mobile
    let lastTouchDistance = 0;

    image.addEventListener('touchstart', (e) => {
        if (e.touches.length === 2) {
            e.preventDefault();
            const touch1 = e.touches[0];
            const touch2 = e.touches[1];
            lastTouchDistance = Math.hypot(
                touch2.clientX - touch1.clientX,
                touch2.clientY - touch1.clientY
            );
        }
    });

    image.addEventListener('touchmove', (e) => {
        if (e.touches.length === 2) {
            e.preventDefault();
            const touch1 = e.touches[0];
            const touch2 = e.touches[1];
            const currentDistance = Math.hypot(
                touch2.clientX - touch1.clientX,
                touch2.clientY - touch1.clientY
            );

            const delta = currentDistance - lastTouchDistance;
            const newScale = Math.min(MAX_ZOOM, Math.max(MIN_ZOOM, scale + delta * 0.01));

            if (newScale !== scale) {
                scale = newScale;
                updateTransform();
            }

            lastTouchDistance = currentDistance;
        }
    });
};

// Initialize everything when the DOM is ready
document.addEventListener('DOMContentLoaded', function () {
    initializeImageViewer();
});