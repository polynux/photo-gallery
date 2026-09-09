import { GridStack } from 'gridstack';
import 'gridstack/dist/gridstack.min.css';

window.universLayoutEditor = (initialItems, mode) => ({
    grid: null,
    preview: 'desktop',
    syncingPreview: false,

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

        this.grid.on('change', () => {
            if (this.grid) {
                this.$wire.setLayoutItems(this.grid.save(false).map((item) => ({
                    univers_id: item.id,
                    x: item.x,
                    y: item.y,
                    width: item.w,
                    height: item.h,
                })));
            }
        });
    },

    loadItems(items) {
        if (! this.grid) {
            return;
        }

        this.grid.load(items.map((item) => ({
            id: String(item.univers_id),
            x: item.x,
            y: item.y,
            w: item.width,
            h: item.height,
        })));
    },

    setMode(value) {
        if (! this.grid) {
            return;
        }

        this.grid.enableResize(value === 'custom');
        this.grid.enableMove(true);
    },

    setPreview(value) {
        if (! this.grid) {
            return;
        }

        this.syncingPreview = true;

        if (value === 'mobile') {
            this.grid.column(1, false);
            this.grid.disable();
        } else {
            this.grid.enable();
            this.grid.column(12, false);
        }

        this.syncingPreview = false;
    },
});
