import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import type { AppLayoutProps } from '@/types';

/**
 * Lapisan aurora sebagai daftar `background-image` (layer pertama paling atas).
 * Ditulis sebagai konstanta, bukan arbitrary value Tailwind, karena empat
 * gradient bertumpuk dengan koma + `var()` tidak terbaca sebagai class.
 *
 * Inti tiap bloom diletakkan di luar viewport (koordinat negatif atau >100%)
 * supaya yang terlihat hanya bagian falloff-nya — atmosfer, bukan sorotan.
 * Radius dan amplitudo diverifikasi lewat model komposit sRGB pada 1440x900:
 * kasus terburuk text-secondary 4.751:1 (light) dan 4.933:1 (dark), keduanya AA.
 * Gradient memakai interpolasi premultiplied sehingga `transparent` tidak
 * memunculkan abu-abu di tengah fade.
 */
const AURORA_LAYERS = [
    'radial-gradient(40rem 32rem at 98% -10%, var(--aurora-teal), transparent 72%)',
    'radial-gradient(56rem 38rem at 38% 118%, var(--aurora-violet), transparent 76%)',
    'radial-gradient(46rem 46rem at 2% -4%, var(--aurora-violet), transparent 74%)',
    'linear-gradient(135deg, var(--bg-base-start) 0%, var(--bg-base-end) 100%)',
].join(', ');

/**
 * Grain statis penekan banding pada ramp aurora yang sangat landai.
 * baseFrequency 0.85 x tile 160px = 136 periode (bilangan bulat), syarat
 * `stitchTiles` menyambung tanpa jahitan; didesaturasi agar netral warna.
 */
const AURORA_GRAIN = `url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='160' height='160'%3E%3Cfilter id='g'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='3' stitchTiles='stitch'/%3E%3CfeColorMatrix type='saturate' values='0'/%3E%3C/filter%3E%3Crect width='160' height='160' filter='url(%23g)'/%3E%3C/svg%3E")`;

/**
 * Identity layer shell: aurora gradient + grain, diam di belakang seluruh chrome.
 *
 * Amplitudo bloom dan opasitas grain datang dari token tema (`aurora.*` di
 * DESIGN.md → `--aurora-*` di app.css) sehingga nilainya satu sumber dan
 * pergantian tema gratis. Nilai itu dikalibrasi terhadap kontras teks di atas
 * panel kaca; jangan diubah di sini tanpa update token source-nya. Layer ini
 * murni dekoratif sehingga tidak pernah menerima pointer event dan tidak
 * menyumbang landmark aksesibilitas.
 *
 * @returns Elemen dekoratif berisi gradient aurora dan lapisan grain.
 */
function AuroraField() {
    return (
        <div
            aria-hidden="true"
            className="pointer-events-none fixed inset-0 z-[-1]"
        >
            <div
                className="absolute inset-0 bg-bg-base-start"
                style={{ backgroundImage: AURORA_LAYERS }}
            />
            <div
                className="absolute inset-0 opacity-(--aurora-grain-opacity)"
                style={{ backgroundImage: AURORA_GRAIN }}
            />
        </div>
    );
}

/**
 * Shell layout utama untuk semua halaman terautentikasi.
 *
 * Menyusun tiga lapisan sesuai lapisan DESIGN.md: aurora sebagai identitas
 * paling belakang, chrome kaca (sidebar + panel konten) di atasnya, dan
 * surface solid milik masing-masing halaman sebagai lapisan data. Area
 * `#app-content` sengaja tidak diberi fill opak supaya aurora tetap terasa
 * menembus panel kaca.
 *
 * @param props.children Konten halaman; dirender di dalam area scroll.
 * @param props.breadcrumbs Jejak navigasi untuk header, default array kosong.
 * @returns Shell aplikasi lengkap dengan skip link dan area konten.
 */
export default function AppSidebarLayout({
    children,
    breadcrumbs = [],
}: AppLayoutProps) {
    return (
        <AppShell>
            {/* Aurora gradient base — identity layer behind all chrome */}
            <AuroraField />

            {/* Skip link: first tabbable element for keyboard users */}
            <a
                href="#app-content"
                className="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:rounded-lg focus:bg-surface-solid focus:px-4 focus:py-2 focus:text-[13px] focus:font-semibold focus:text-text-primary focus:shadow-light focus:ring-1 focus:ring-border-solid dark:focus:shadow-dark"
            >
                Lewati ke konten utama
            </a>

            {/* Glass chrome: sidebar */}
            <AppSidebar />

            {/* Glass content panel (resep di AppContent) mengambang di atas aurora;
                area data di dalamnya tetap solid sesuai pemisahan lapisan DESIGN.md */}
            <AppContent>
                <AppSidebarHeader breadcrumbs={breadcrumbs} />

                <div
                    id="app-content"
                    // focusable target so the skip link actually moves focus (WCAG 2.4.1)
                    tabIndex={-1}
                    className="h-0 min-h-0 flex-1 overflow-y-auto focus:outline-2 focus:-outline-offset-2 focus:outline-accent-primary focus:outline-solid"
                >
                    {children}
                </div>
            </AppContent>
        </AppShell>
    );
}
