{{--
    Alpine theme store — registered via a classic, non-deferred inline script
    so the `alpine:init` listener is guaranteed to attach BEFORE the deferred
    Alpine CDN bundle boots. (A Vite module script cannot guarantee this
    ordering relative to a classic `defer` script, which caused the store to
    be undefined when Alpine evaluated directives.)

    Single source of truth for theme, dark mode, table style, and print
    orientation. Persists to localStorage; the anti-FOUT script in <head>
    applies stored values before first paint.
--}}
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.store('theme', {
            current:      localStorage.getItem('pt-theme')         || 'indigo',
            tableStyle:   localStorage.getItem('pt-table-style')   || 'boxed',
            orientation:  localStorage.getItem('pt-orientation')   || 'portrait',
            customAccent: localStorage.getItem('pt-custom-accent') || null,

            themes: [
                { id: 'indigo',   label: 'Indigo',   swatch: 'oklch(50% 0.24 262)' },
                { id: 'emerald',  label: 'Emerald',  swatch: 'oklch(44% 0.18 162)' },
                { id: 'sunset',   label: 'Sunset',   swatch: 'oklch(56% 0.22 30)'  },
                { id: 'mono-pro', label: 'Mono Pro', swatch: '#0d0d0d'             },
                { id: 'ocean',    label: 'Ocean',    swatch: 'oklch(48% 0.22 230)' },
                { id: 'oxblood',  label: 'Oxblood',  swatch: 'oklch(34% 0.20 14)'  },
                { id: 'grape',    label: 'Grape',    swatch: 'oklch(50% 0.30 290)' },
            ],

            tableStyles: [
                { id: 'clean',         label: 'Clean'         },
                { id: 'ruled',         label: 'Ruled'         },
                { id: 'boxed',         label: 'Boxed'         },
                { id: 'striped',       label: 'Striped'       },
                { id: 'shaded-header', label: 'Shaded Header' },
            ],

            setTheme(name) {
                document.documentElement.classList.add('theme-transitioning');
                this.current = name;
                this.customAccent = null;
                localStorage.setItem('pt-theme', name);
                localStorage.removeItem('pt-custom-accent');
                document.documentElement.setAttribute('data-theme', name);
                document.documentElement.style.removeProperty('--accent');
                document.documentElement.style.removeProperty('--accent-subtle');
                setTimeout(() => document.documentElement.classList.remove('theme-transitioning'), 400);
            },

            setCustomAccent(hex) {
                this.customAccent = hex;
                localStorage.setItem('pt-custom-accent', hex);
                document.documentElement.style.setProperty('--accent', hex);
                document.documentElement.style.setProperty('--accent-subtle', hex + '18');
            },

            setTableStyle(style) {
                this.tableStyle = style;
                localStorage.setItem('pt-table-style', style);
            },

            setOrientation(value) {
                this.orientation = value;
                localStorage.setItem('pt-orientation', value);
                document.documentElement.setAttribute('data-orientation', value);
                let styleTag = document.getElementById('pt-orientation-style');
                if (!styleTag) {
                    styleTag = document.createElement('style');
                    styleTag.id = 'pt-orientation-style';
                    document.head.appendChild(styleTag);
                }
                styleTag.textContent = value === 'landscape'
                    ? '@media print { @page { size: A4 landscape; } }'
                    : '@media print { @page { size: A4 portrait; } }';
            },
        });
    });
</script>
