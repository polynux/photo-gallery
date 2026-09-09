import { GridStack } from 'gridstack';
import 'gridstack/dist/gridstack.min.css';

window.universLayoutEditor = (initialItems, mode, preview = 'desktop', preset = null) => ({
    grid: null,
    mode,
    items: initialItems,
    preview,
    preset,
    syncing: false,
    pendingSync: null,
    draggedId: null,
    presetResizeObserver: null,
    renderFrame: null,

    init() {
        this.$nextTick(() => this.render(this.items, this.mode));
        window.addEventListener('beforeunload', (event) => {
            if (this.$wire.isDirty) {
                event.preventDefault();
                event.returnValue = '';
            }
        });
        this.presetResizeObserver = new ResizeObserver(() => this.fitPresetCanvas());
        this.$wire.on('univers-layout-updated', ({ editorItems, mode, preset, preview }) => {
            this.preset = preset ?? this.preset;
            this.preview = preview ?? this.preview;
            this.requestRender(editorItems, mode);
        });
    },

    requestRender(items = this.items, mode = this.mode) {
        this.items = items;
        this.mode = mode;
        cancelAnimationFrame(this.renderFrame);
        this.renderFrame = requestAnimationFrame(() => this.render(this.items, this.mode));
    },

    render(items, mode) {
        const element = this.$root.querySelector('#univers-layout-grid');

        if (! element) {
            return;
        }

        window.clearTimeout(this.pendingSync);
        cancelAnimationFrame(this.renderFrame);
        this.stopPresetObserver();
        this.syncing = true;
        this.items = items;
        this.mode = mode;

        if (this.grid) {
            this.grid.destroy(false);
            this.grid = null;
        }

        const viewport = element.parentElement;
        viewport.classList.remove('univers-editor-grid--custom', 'univers-editor-grid--preset', 'univers-editor-grid--generic', 'mx-auto', 'max-w-sm');
        viewport.classList.add(`univers-editor-grid--${mode}`);
        if (this.preview === 'mobile') {
            viewport.classList.add('mx-auto', 'max-w-sm');
        }
        viewport.style.removeProperty('height');
        viewport.style.removeProperty('overflow');
        viewport.style.removeProperty('overflow-x');
        viewport.style.removeProperty('overflow-y');
        element.style.removeProperty('transform');
        element.style.removeProperty('transform-origin');
        element.style.removeProperty('height');
        element.style.removeProperty('width');
        element.replaceChildren();
        element.className = mode === 'custom'
            ? 'grid-stack univers-layout-grid univers-editor-grid'
            : `univers-layout-grid univers-layout-grid--${mode}`;

        if (mode === 'custom') {
            items.forEach((item) => element.append(this.createGridItem(item)));
            this.grid = GridStack.init({
                column: 12,
                cellHeight: 'auto',
                margin: 5,
                float: false,
                disableOneColumnMode: true,
                disableResize: false,
                disableDrag: false,
                handle: '.grid-stack-item-content',
            }, element);
            this.grid.on('change', () => this.scheduleSync());
            this.grid.on('dragstop', () => this.scheduleSync());
            this.grid.on('resizestop', () => this.scheduleSync());
        } else if (mode === 'preset') {
            items.forEach((item) => element.append(this.createPresetItem(item)));
            this.startPresetObserver(viewport);
            this.$nextTick(() => this.fitPresetCanvas());
        } else {
            items.forEach((item) => element.append(this.createGenericItem(item)));
            this.enableGenericSorting(element);
        }

        this.syncing = false;
    },

    startPresetObserver(viewport) {
        this.stopPresetObserver();
        this.presetResizeObserver = new ResizeObserver(() => this.fitPresetCanvas());
        this.presetResizeObserver.observe(viewport);
    },

    stopPresetObserver() {
        this.presetResizeObserver?.disconnect();
        this.presetResizeObserver = null;
    },

    fitPresetCanvas() {
        const element = this.$root.querySelector('#univers-layout-grid');

        if (! element || this.mode !== 'preset') {
            return;
        }

        const viewport = element.parentElement;
        const viewportStyles = getComputedStyle(viewport);
        const horizontalChrome = parseFloat(viewportStyles.paddingLeft)
            + parseFloat(viewportStyles.paddingRight)
            + parseFloat(viewportStyles.borderLeftWidth)
            + parseFloat(viewportStyles.borderRightWidth);
        const availableWidth = Math.max(viewport.clientWidth - horizontalChrome, 1);
        const canvasWidth = 878;
        const scale = Math.max(0.1, Math.min(1, availableWidth / canvasWidth));

        element.style.transform = `scale(${scale})`;
        element.style.transformOrigin = 'top left';
        const styles = getComputedStyle(viewport);
        const verticalChrome = parseFloat(styles.paddingTop)
            + parseFloat(styles.paddingBottom)
            + parseFloat(styles.borderTopWidth)
            + parseFloat(styles.borderBottomWidth);

        viewport.style.height = `${element.offsetHeight * scale + verticalChrome}px`;
        viewport.style.overflow = 'hidden';
    },

    createGridItem(item) {
        const wrapper = this.createPresetItem(item);
        wrapper.className = 'grid-stack-item';
        wrapper.setAttribute('gs-x', item.x ?? 0);
        wrapper.setAttribute('gs-y', item.y ?? 0);
        wrapper.setAttribute('gs-w', item.width ?? 4);
        wrapper.setAttribute('gs-h', item.height ?? 3);
        wrapper.querySelector('.univers-editor-item-content').classList.remove('cursor-grab');
        return wrapper;
    },

    createPresetItem(item) {
        const wrapper = document.createElement('div');
        wrapper.className = 'univers-editor-item';
        wrapper.dataset.universId = item.univers_id;
        wrapper.style.gridColumn = `${Number(item.x ?? 0) + 1} / span ${item.width ?? 4}`;
        wrapper.style.gridRow = `${Number(item.y ?? 0) + 1} / span ${item.height ?? 3}`;

        const content = document.createElement('div');
        content.className = 'univers-editor-item-content group relative overflow-hidden rounded-lg bg-gray-200 shadow-sm dark:bg-gray-800';
        content.addEventListener('click', () => this.selectTile(item.univers_id));

        if (this.mode === 'preset') {
            content.draggable = true;
            content.classList.add('cursor-grab');
            content.addEventListener('dragstart', (event) => {
                this.draggedId = String(item.univers_id);
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', this.draggedId);
            });
            content.addEventListener('dragover', (event) => event.preventDefault());
            content.addEventListener('drop', (event) => {
                event.preventDefault();
                const targetId = Number(item.univers_id);
                const sourceId = Number(event.dataTransfer.getData('text/plain') || this.draggedId);
                if (sourceId && sourceId !== targetId) {
                    this.$wire.swapPresetItems(sourceId, targetId);
                }
            });
        }

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

    createGenericItem(item) {
        const wrapper = this.createPresetItem(item);
        wrapper.className = 'univers-generic-item';
        wrapper.style.gridColumn = '';
        wrapper.style.gridRow = '';
        const content = wrapper.querySelector('.univers-editor-item-content');
        content.draggable = true;
        content.classList.add('cursor-grab');
        wrapper.style.aspectRatio = '4 / 3';
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

    saveLayout() {
        window.clearTimeout(this.pendingSync);

        if (this.grid && this.mode === 'custom' && this.preview === 'desktop') {
            this.syncing = true;
            const items = this.grid.save(false).map((item) => ({
                univers_id: item.id,
                x: item.x,
                y: item.y,
                width: item.w,
                height: item.h,
            }));

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
            this.$wire.setLayoutItems(this.grid.save(false).map((item) => ({
                univers_id: item.id,
                x: item.x,
                y: item.y,
                width: item.w,
                height: item.h,
            })));
        }, 150);
    },
});
