import { GridStack } from 'gridstack';
import 'gridstack/dist/gridstack.min.css';

const CANVAS_WIDTH = 878;

window.universLayoutEditor = (initialItems, mode, preview = 'desktop', preset = null) => ({
    grid: null,
    mode,
    items: initialItems,
    preview,
    preset,
    syncing: false,
    pendingSync: null,
    draggedId: null,
    canvasResizeObserver: null,
    renderFrame: null,

    init() {
        this.canvasResizeObserver = new ResizeObserver(() => this.fitCanvasByMode());
        this.$nextTick(() => this.render(this.items, this.mode));
        window.addEventListener('beforeunload', (event) => {
            if (this.$wire.isDirty) {
                event.preventDefault();
                event.returnValue = '';
            }
        });
        this.$wire.on('univers-layout-updated', ({ editorItems, mode, preset, preview }) => {
            const structuralChange = mode !== this.mode
                || preview !== this.preview
                || (preset ?? null) !== (this.preset ?? null);

            this.preset = preset ?? this.preset;
            this.preview = preview ?? this.preview;

            if (! structuralChange) {
                this.items = editorItems;

                if (mode === 'custom' && this.grid && this.preview === 'desktop') {
                    this.syncGridItems(editorItems);
                } else {
                    this.requestRender(editorItems, mode);
                }

                return;
            }

            this.requestRender(editorItems, mode);
        });
    },

    requestRender(items = this.items, mode = this.mode) {
        this.items = items;
        this.mode = mode;
        cancelAnimationFrame(this.renderFrame);
        this.renderFrame = requestAnimationFrame(() => this.render(this.items, this.mode));
    },

    syncGridItems(items) {
        this.syncing = true;
        window.clearTimeout(this.pendingSync);
        this.grid.batchUpdate();

        const nodes = new Map(this.grid.engine.nodes.map((node) => [String(node.id), node]));
        const removedIds = new Set(nodes.keys());

        items.forEach((item) => {
            const id = String(item.univers_id);
            removedIds.delete(id);

            const node = nodes.get(id);

            if (node) {
                this.grid.update(node.el, {
                    x: item.x,
                    y: item.y,
                    w: item.width,
                    h: item.height,
                });
            } else {
                this.grid.addWidget(this.createCustomItem(item));
            }
        });

        removedIds.forEach((id) => {
            const node = nodes.get(id);

            if (node) {
                this.grid.removeWidget(node.el, true, false);
            }
        });

        this.grid.batchUpdate(false);
        this.syncing = false;
    },

    render(items, mode) {
        const element = this.$root.querySelector('#univers-layout-grid');

        if (! element) {
            return;
        }

        window.clearTimeout(this.pendingSync);
        cancelAnimationFrame(this.renderFrame);
        this.stopCanvasObserver();
        this.syncing = true;
        this.items = items;
        this.mode = mode;

        this.destroyGrid();

        const viewport = element.parentElement;
        viewport.classList.remove('univers-editor-grid--custom', 'univers-editor-grid--preset', 'univers-editor-grid--generic');
        viewport.classList.add(`univers-editor-grid--${mode}`);
        this.applyPreview(viewport);
        viewport.style.removeProperty('height');
        viewport.style.removeProperty('overflow');
        viewport.style.removeProperty('overflow-x');
        viewport.style.removeProperty('overflow-y');
        element.style.removeProperty('transform');
        element.style.removeProperty('transform-origin');
        element.style.removeProperty('height');
        element.style.removeProperty('width');
        element.replaceChildren();

        if (mode === 'custom') {
            this.renderCustom(element, viewport, items);
        } else if (mode === 'preset') {
            this.renderPreset(element, viewport, items);
        } else {
            this.renderGeneric(element, items);
        }

        this.syncing = false;
    },

    destroyGrid() {
        if (this.grid) {
            this.grid.destroy(false);
            this.grid = null;
        }
    },

    renderCustom(element, viewport, items) {
        if (this.preview === 'mobile') {
            element.className = 'univers-layout-grid univers-layout-grid--custom';
            items.forEach((item) => element.append(this.createStaticItem(item)));
            this.startCanvasObserver(viewport);
            this.$nextTick(() => this.fitStaticCanvas(element));

            return;
        }

        element.className = 'grid-stack univers-layout-grid univers-editor-grid';
        items.forEach((item) => element.append(this.createCustomItem(item)));
        this.grid = GridStack.init({
            column: 12,
            cellHeight: 'auto',
            margin: 5,
            float: false,
            disableOneColumnMode: true,
            disableResize: false,
            disableDrag: false,
            handle: '.univers-editor-item-content',
        }, element);
        this.grid.on('change', () => this.scheduleSync());
        this.grid.on('dragstop', () => this.scheduleSync());
        this.grid.on('resizestop', () => this.scheduleSync());
    },

    renderPreset(element, viewport, items) {
        element.className = 'univers-layout-grid univers-layout-grid--preset';
        items.forEach((item) => element.append(this.createStaticItem(item, true)));
        this.startCanvasObserver(viewport);
        this.$nextTick(() => this.fitCanvasByMode());
    },

    renderGeneric(element, items) {
        element.className = 'univers-layout-grid univers-layout-grid--generic';
        items.forEach((item) => element.append(this.createGenericItem(item)));
        this.enableGenericSorting(element);
    },

    applyPreview(viewport) {
        viewport.classList.toggle('mx-auto', this.preview === 'mobile');
        viewport.classList.toggle('max-w-sm', this.preview === 'mobile');
    },

    startCanvasObserver(viewport) {
        this.canvasResizeObserver.disconnect();
        this.canvasResizeObserver.observe(viewport);
    },

    stopCanvasObserver() {
        this.canvasResizeObserver.disconnect();
    },

    fitCanvasByMode() {
        const element = this.$root.querySelector('#univers-layout-grid');

        if (! element || this.grid) {
            return;
        }

        if (this.mode === 'preset') {
            this.fitCanvas(element);
        } else if (this.mode === 'custom' && this.preview === 'mobile') {
            this.fitCanvas(element);
        }
    },

    fitCanvas(element) {
        const viewport = element.parentElement;
        const viewportStyles = getComputedStyle(viewport);
        const horizontalChrome = parseFloat(viewportStyles.paddingLeft)
            + parseFloat(viewportStyles.paddingRight)
            + parseFloat(viewportStyles.borderLeftWidth)
            + parseFloat(viewportStyles.borderRightWidth);
        const availableWidth = Math.max(viewport.clientWidth - horizontalChrome, 1);
        const scale = Math.max(0.1, Math.min(1, availableWidth / CANVAS_WIDTH));

        element.style.transform = `scale(${scale})`;
        element.style.transformOrigin = 'top left';

        const verticalChrome = parseFloat(viewportStyles.paddingTop)
            + parseFloat(viewportStyles.paddingBottom)
            + parseFloat(viewportStyles.borderTopWidth)
            + parseFloat(viewportStyles.borderBottomWidth);
        const unscaledHeight = element.getBoundingClientRect().height / scale;

        viewport.style.height = `${unscaledHeight * scale + verticalChrome}px`;
        viewport.style.overflow = 'hidden';

        return scale;
    },

    fitStaticCanvas(element) {
        element.style.width = `${CANVAS_WIDTH}px`;
        element.style.display = 'grid';
        element.style.gridTemplateColumns = 'repeat(12, 1fr)';
        element.style.gap = '10px';
        element.style.alignContent = 'start';
        this.fitCanvas(element);
    },

    createCustomItem(item) {
        const wrapper = this.createTile(item);
        wrapper.className = 'grid-stack-item';
        wrapper.setAttribute('gs-id', item.univers_id);
        wrapper.setAttribute('gs-x', item.x ?? 0);
        wrapper.setAttribute('gs-y', item.y ?? 0);
        wrapper.setAttribute('gs-w', item.width ?? 3);
        wrapper.setAttribute('gs-h', item.height ?? 2);

        return wrapper;
    },

    createStaticItem(item, draggable = false) {
        const wrapper = this.createTile(item);
        wrapper.className = 'univers-editor-item';
        wrapper.style.gridColumn = `${Number(item.x ?? 0) + 1} / span ${item.width ?? 3}`;
        wrapper.style.gridRow = `${Number(item.y ?? 0) + 1} / span ${item.height ?? 2}`;

        if (draggable) {
            this.makePresetDraggable(wrapper);
        }

        return wrapper;
    },

    createGenericItem(item) {
        const wrapper = this.createTile(item);
        wrapper.className = 'univers-generic-item';
        wrapper.style.aspectRatio = '4 / 3';
        this.makeGenericDraggable(wrapper);

        return wrapper;
    },

    makePresetDraggable(wrapper) {
        const content = wrapper.querySelector('.univers-editor-item-content');
        content.draggable = true;
        content.classList.add('cursor-grab');
        content.addEventListener('dragstart', (event) => {
            this.draggedId = String(wrapper.dataset.universId);
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', this.draggedId);
        });
        content.addEventListener('dragover', (event) => event.preventDefault());
        content.addEventListener('drop', (event) => {
            event.preventDefault();
            const targetId = Number(wrapper.dataset.universId);
            const sourceId = Number(event.dataTransfer.getData('text/plain') || this.draggedId);
            if (sourceId && sourceId !== targetId) {
                this.$wire.swapPresetItems(sourceId, targetId);
            }
        });
    },

    makeGenericDraggable(wrapper) {
        const content = wrapper.querySelector('.univers-editor-item-content');
        content.draggable = true;
        content.classList.add('cursor-grab');
    },

    createTile(item) {
        const wrapper = document.createElement('div');
        wrapper.className = 'univers-editor-item';
        wrapper.dataset.universId = item.univers_id;

        const content = document.createElement('div');
        content.className = 'univers-editor-item-content group relative overflow-hidden rounded-lg bg-gray-200 shadow-sm dark:bg-gray-800';
        content.addEventListener('click', () => this.selectTile(item.univers_id));

        const image = document.createElement('img');
        image.src = item.source;
        image.alt = item.title;
        image.className = 'absolute inset-0 block h-full w-full max-w-none object-cover';
        image.style.objectPosition = `${item.focal_x * 100}% ${item.focal_y * 100}%`;

        const footer = document.createElement('div');
        footer.className = 'absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/80 to-transparent p-3 pt-8 text-white';
        footer.innerHTML = '<div class="truncate text-sm font-medium"></div><div class="text-xs opacity-75"></div>';
        footer.firstElementChild.textContent = item.title;
        footer.lastElementChild.textContent = item.status;

        content.append(image, footer);
        wrapper.append(content);

        return wrapper;
    },

    enableGenericSorting(element) {
        let sourceId = null;
        element.querySelectorAll('.univers-generic-item').forEach((item) => {
            item.addEventListener('dragstart', (event) => {
                sourceId = item.dataset.universId;
                event.dataTransfer.effectAllowed = 'move';
            });
            item.addEventListener('dragover', (event) => event.preventDefault());
            item.addEventListener('drop', (event) => {
                event.preventDefault();
                const target = item;
                const source = [...element.children].find((child) => child.dataset.universId === sourceId);
                if (! source || source === target) {
                    return;
                }
                target.before(source);
                this.$wire.reorderGeneric([...element.children].map((child) => Number(child.dataset.universId)));
            });
        });
    },

    selectTile(universId) {
        this.$wire.selectFocalPoint(universId);
    },

    collectGridItems() {
        if (! this.grid) {
            return [];
        }

        return this.grid.save(false)
            .filter((item) => item.id !== undefined && item.id !== null)
            .map((item) => ({
                univers_id: item.id,
                x: item.x ?? 0,
                y: item.y ?? 0,
                width: item.w ?? 1,
                height: item.h ?? 1,
            }));
    },

    saveLayout() {
        window.clearTimeout(this.pendingSync);

        if (this.grid && this.mode === 'custom' && this.preview === 'desktop') {
            this.syncing = true;
            const items = this.collectGridItems();

            this.$wire.setLayoutItems(items).then(() => {
                this.syncing = false;
                return this.$wire.saveLayout();
            });

            return;
        }

        this.$wire.saveLayout();
    },

    scheduleSync() {
        if (! this.grid || this.syncing || this.mode !== 'custom' || this.preview !== 'desktop') {
            return;
        }
        window.clearTimeout(this.pendingSync);
        this.pendingSync = window.setTimeout(() => {
            this.$wire.setLayoutItems(this.collectGridItems());
        }, 150);
    },
});
