import { GridStack } from 'gridstack';
import 'gridstack/dist/gridstack.min.css';

window.universLayoutEditor = (initialItems, mode) => ({
    grid: null,
    preview: 'desktop',
    syncingPreview: false,
    syncingItems: false,
    desktopItems: null,
    pendingSync: null,

    init() {
        this.$nextTick(() => this.initializeGrid(initialItems, mode));
        this.$wire.$watch('mode', (value) => this.setMode(value));
        this.$wire.$watch('preview', (value) => {
            this.preview = value;
            this.setPreview(value);
        });
        this.$wire.on('univers-layout-updated', ({ items, mode }) => {
            this.setMode(mode);
            this.loadItems(items);
        });
    },

    initializeGrid(items, currentMode) {
        const element = this.$root.querySelector('#univers-layout-grid');

        if (! element) {
            return;
        }

        this.grid = GridStack.init({
            column: 12,
            cellHeight: 28,
            margin: 5,
            float: false,
            disableOneColumnMode: true,
            disableResize: currentMode !== 'custom',
            disableDrag: false,
            handle: '.grid-stack-item-content',
        }, element);

        this.grid.on('change', () => this.scheduleSync());
        this.grid.on('dragstop', () => this.scheduleSync());
        this.grid.on('resizestop', () => this.scheduleSync());
    },

    loadItems(items) {
        if (! this.grid) {
            return;
        }

        this.syncingItems = true;
        this.grid.batchUpdate();

        items.forEach((item) => {
            const element = this.grid.engine.nodes.find((node) => node.id === String(item.univers_id))?.el;

            if (element) {
                this.grid.update(element, {
                    x: item.x,
                    y: item.y,
                    w: item.width,
                    h: item.height,
                });
            }
        });

        this.grid.batchUpdate(false);
        this.syncingItems = false;
    },

    scheduleSync() {
        if (! this.grid || this.syncingItems || this.preview === 'mobile') {
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
        }, 100);
    },

    setMode(value) {
        if (! this.grid) {
            return;
        }

        this.grid.enableResize(value === 'custom');
        this.grid.enableMove(true);

        if (value === 'custom') {
            this.grid.setStatic(false);
        }
    },

    setPreview(value) {
        if (! this.grid) {
            return;
        }

        this.syncingPreview = true;

        if (value === 'mobile') {
            this.desktopItems = this.grid.save(false).map((item) => ({
                id: item.id,
                x: item.x,
                y: item.y,
                w: item.w,
                h: item.h,
            }));
            this.grid.column(1, false);
            this.grid.disable();
        } else {
            this.grid.enable();
            this.grid.column(12, false);

            if (this.desktopItems) {
                this.grid.load(this.desktopItems);
                this.desktopItems = null;
            }
        }

        this.syncingPreview = false;
    },
});
