import { Head } from '@inertiajs/react';
import {
    ArrowDownRight,
    ArrowRightLeft,
    ArrowUpRight,
    Building2,
    CalendarClock,
    Car,
    Check,
    ChevronDown,
    ChevronRight,
    ClipboardCheck,
    Download,
    Hand,
    History,
    Laptop,
    MoreHorizontal,
    PackageCheck,
    Pencil,
    Plus,
    QrCode,
    ScanLine,
    Share,
    ShieldAlert,
    Truck,
    Wrench,
    type LucideIcon,
} from 'lucide-react';
import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard } from '@/routes';

type ChartRange = '7d' | '30d' | '90d';
type ChartPoint = {
    label: string;
    registered: number;
    verified: number;
    inactive: number;
};
type MetricTone = 'violet' | 'teal' | 'amber';

type HoveredBar = { index: number; rect: DOMRect } | null;

const chartRanges: Record<ChartRange, { label: string; points: ChartPoint[] }> =
    {
        '7d': {
            label: '7 hari terakhir',
            points: [
                { label: 'Sen', registered: 8, verified: 6, inactive: 1 },
                { label: 'Sel', registered: 10, verified: 7, inactive: 2 },
                { label: 'Rab', registered: 12, verified: 8, inactive: 1 },
                { label: 'Kam', registered: 9, verified: 11, inactive: 2 },
                { label: 'Jum', registered: 15, verified: 9, inactive: 1 },
                { label: 'Sab', registered: 11, verified: 7, inactive: 1 },
                { label: 'Min', registered: 17, verified: 10, inactive: 2 },
            ],
        },
        '30d': {
            label: '30 hari terakhir',
            points: [
                { label: '01–06', registered: 34, verified: 28, inactive: 5 },
                { label: '07–12', registered: 40, verified: 32, inactive: 7 },
                { label: '13–18', registered: 31, verified: 38, inactive: 4 },
                { label: '19–24', registered: 48, verified: 35, inactive: 6 },
                { label: '25–30', registered: 55, verified: 42, inactive: 8 },
            ],
        },
        '90d': {
            label: '90 hari terakhir',
            points: [
                { label: 'Jul', registered: 82, verified: 72, inactive: 12 },
                { label: 'Agu', registered: 96, verified: 84, inactive: 15 },
                { label: 'Sep', registered: 118, verified: 103, inactive: 18 },
            ],
        },
    };

const glassCardClassName =
    'min-w-0 overflow-hidden rounded-2xl border border-border-glass bg-surface-glass text-text-primary shadow-light backdrop-blur-[20px] dark:shadow-dark';
const solidCardClassName =
    'min-w-0 gap-0 rounded-[4px] border border-border-solid bg-surface-solid p-0 text-text-primary shadow-none';

const metricTones: Record<
    MetricTone,
    { iconBg: string; iconText: string; accent: string }
> = {
    violet: {
        iconBg: 'bg-accent-primary/10',
        iconText: 'text-accent-primary',
        accent: 'text-text-secondary',
    },
    teal: {
        iconBg: 'bg-accent-teal/10',
        iconText: 'text-accent-teal',
        accent: 'text-text-secondary',
    },
    amber: {
        iconBg: 'bg-warning/10',
        iconText: 'text-warning',
        accent: 'text-text-secondary',
    },
};

type ChangeTone = 'up' | 'down' | 'neutral';

function StatChangeBadge({
    value,
    tone,
    label,
}: {
    value: string;
    tone: ChangeTone;
    label: string;
}) {
    const toneClasses = {
        up: 'bg-success/10 text-success',
        down: 'bg-danger/10 text-danger',
        neutral: 'bg-surface-solid-alt text-text-secondary',
    } as const;
    const Icon = tone === 'up' ? ArrowUpRight : ArrowDownRight;

    return (
        <span
            className={`inline-flex items-center gap-0.5 rounded-full px-2 py-0.5 text-[10.5px] font-semibold ${toneClasses[tone]}`}
        >
            <Icon className="size-[11px]" strokeWidth={2.4} />
            {value} {label}
        </span>
    );
}

function MetricCard({
    title,
    value,
    changeValue,
    changeTone,
    changeLabel,
    icon: Icon,
    tone,
}: {
    title: string;
    value: string;
    changeValue: string;
    changeTone: ChangeTone;
    changeLabel: string;
    icon: LucideIcon;
    tone: MetricTone;
}) {
    const t = metricTones[tone];

    return (
        <Card className={glassCardClassName}>
            <CardContent className="flex items-start justify-between gap-3 p-4 md:p-5">
                <div className="min-w-0">
                    <p className="text-[11.5px] font-medium tracking-tight text-text-secondary">
                        {title}
                    </p>
                    <p className="mt-2 font-mono text-h2 font-semibold tracking-tight text-text-primary tabular-nums">
                        {value}
                    </p>
                    <div className="mt-2">
                        <StatChangeBadge
                            value={changeValue}
                            tone={changeTone}
                            label={changeLabel}
                        />
                    </div>
                </div>
                <span
                    className={`inline-flex size-10 shrink-0 items-center justify-center rounded-xl ${t.iconBg}`}
                >
                    <Icon
                        aria-hidden="true"
                        className={`size-[18px] ${t.iconText}`}
                        strokeWidth={1.9}
                    />
                </span>
            </CardContent>
        </Card>
    );
}

function HeroChartPanel({
    points,
    rangeLabel,
    range,
    onRangeChange,
}: {
    points: ChartPoint[];
    rangeLabel: string;
    range: ChartRange;
    onRangeChange: (range: ChartRange) => void;
}) {
    const totals = points.map((p) => p.registered + p.verified + p.inactive);
    const maxValue = Math.max(...totals, 1);
    const axisLabels = [maxValue, Math.round(maxValue / 2), 0];
    const [hovered, setHovered] = useState<HoveredBar>(null);
    const [hoverIndex, setHoverIndex] = useState<number | null>(null);
    const ranges: ChartRange[] = ['7d', '30d', '90d'];

    return (
        <Card className={glassCardClassName}>
            <CardHeader className="flex flex-row items-start justify-between gap-4 px-4 pt-4 pb-0 md:px-6 md:pt-5">
                <div className="min-w-0">
                    <CardTitle className="text-h3 font-medium tracking-tight text-text-primary">
                        Nilai aset overview
                    </CardTitle>
                    <p className="mt-1 text-[11.5px] text-text-secondary">
                        Ringkasan · {rangeLabel}
                    </p>
                </div>
                <div className="flex shrink-0 items-center gap-2">
                    <div className="inline-flex items-center gap-0.5 rounded-full border border-border-glass/70 bg-surface-solid/50 p-1 backdrop-blur-[8px]">
                        {ranges.map((r) => (
                            <button
                                key={r}
                                type="button"
                                onClick={() => onRangeChange(r)}
                                className={`h-11 rounded-full px-2.5 text-[11px] font-semibold transition-all sm:h-7 ${
                                    r === range
                                        ? 'bg-accent-primary text-white shadow-[0_2px_8px_rgba(138,108,255,0.35)]'
                                        : 'text-text-secondary hover:text-text-primary'
                                }`}
                            >
                                {r}
                            </button>
                        ))}
                    </div>
                    <button
                        type="button"
                        aria-label="Bagikan ringkasan"
                        className="inline-flex size-11 items-center justify-center rounded-full border border-border-glass/60 bg-surface-solid/40 text-text-secondary transition-all hover:bg-surface-solid hover:text-text-primary sm:size-9"
                    >
                        <Share className="size-[16px]" strokeWidth={1.9} />
                    </button>
                    <button
                        type="button"
                        aria-label="Unduh laporan"
                        className="inline-flex size-11 items-center justify-center rounded-full border border-border-glass/60 bg-surface-solid/40 text-text-secondary transition-all hover:bg-surface-solid hover:text-text-primary sm:size-9"
                    >
                        <Download className="size-[16px]" strokeWidth={1.9} />
                    </button>
                </div>
            </CardHeader>

            <CardContent className="px-4 pt-5 pb-4 md:px-6 md:pt-6 md:pb-5">
                <div className="mb-5 flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <p className="font-mono text-display font-semibold tracking-tight text-text-primary tabular-nums">
                            Rp 12,45 M
                        </p>
                        <p className="mt-1 text-[11.5px] text-text-secondary">
                            nilai buku aset dalam workspace contoh
                        </p>
                    </div>
                    <div className="inline-flex items-center gap-1 rounded-full bg-success/10 px-2.5 py-1 text-[11px] font-semibold text-success">
                        <ArrowUpRight
                            aria-hidden="true"
                            className="size-3"
                            strokeWidth={2.2}
                        />
                        8,4% dibanding periode lalu
                    </div>
                </div>

                <div className="grid grid-cols-[2.25rem_minmax(0,1fr)] gap-3">
                    <div className="flex h-44 flex-col justify-between pb-5 text-right font-mono text-mono-data text-text-secondary tabular-nums">
                        {axisLabels.map((label, index) => (
                            <span
                                key={`${label}-${index}`}
                                className="text-[10.5px]"
                            >
                                {label}
                            </span>
                        ))}
                    </div>
                    <div className="relative h-44">
                        <div
                            aria-hidden="true"
                            className="absolute inset-x-0 top-0 bottom-5 flex flex-col justify-between"
                        >
                            {[0, 1, 2].map((line) => (
                                <span
                                    key={line}
                                    className="border-t border-dashed border-border-solid/60"
                                />
                            ))}
                        </div>

                        <div
                            className="absolute inset-x-0 top-0 bottom-5 grid items-end gap-2 sm:gap-3"
                            style={{
                                gridTemplateColumns: `repeat(${points.length}, minmax(0, 1fr))`,
                            }}
                        >
                            {points.map((point, i) => {
                                const total = totals[i];
                                const safeTotal = Math.max(total, 1);
                                const pct = (total / maxValue) * 100;
                                const isActive = hoverIndex === i;

                                return (
                                    <div
                                        key={point.label}
                                        className="relative flex h-full items-end justify-center"
                                        onMouseEnter={(e) => {
                                            setHoverIndex(i);
                                            setHovered({
                                                index: i,
                                                rect: e.currentTarget.getBoundingClientRect(),
                                            });
                                        }}
                                        onMouseLeave={() => {
                                            setHoverIndex(null);
                                            setHovered(null);
                                        }}
                                    >
                                        <div
                                            className={`flex w-full max-w-[34px] flex-col-reverse overflow-hidden rounded-t-lg bg-surface-solid-alt/70 transition-opacity ${
                                                isActive
                                                    ? 'opacity-100'
                                                    : 'opacity-95'
                                            }`}
                                            style={{
                                                height: `${pct}%`,
                                            }}
                                        >
                                            <span
                                                className="bg-accent-primary transition-all"
                                                style={{
                                                    height: `${
                                                        (point.registered /
                                                            safeTotal) *
                                                        100
                                                    }%`,
                                                }}
                                            />
                                            <span
                                                className="bg-accent-teal transition-all"
                                                style={{
                                                    height: `${
                                                        (point.verified /
                                                            safeTotal) *
                                                        100
                                                    }%`,
                                                }}
                                            />
                                            <span
                                                className="bg-warning transition-all"
                                                style={{
                                                    height: `${
                                                        (point.inactive /
                                                            safeTotal) *
                                                        100
                                                    }%`,
                                                }}
                                            />
                                        </div>
                                    </div>
                                );
                            })}
                        </div>

                        <div
                            className="absolute inset-x-0 bottom-0 grid h-5 items-end gap-2 text-center text-[10.5px] font-medium text-text-secondary/80 sm:gap-3"
                            style={{
                                gridTemplateColumns: `repeat(${points.length}, minmax(0, 1fr))`,
                            }}
                        >
                            {points.map((point) => (
                                <span key={point.label}>{point.label}</span>
                            ))}
                        </div>

                        {hoverIndex !== null && hovered && (
                            <div
                                aria-hidden="true"
                                className="pointer-events-none absolute z-10 -translate-x-1/2 -translate-y-[calc(100%+12px)] rounded-xl border border-border-glass bg-surface-solid px-3 py-2 shadow-light"
                                style={{
                                    left: `${
                                        ((hoverIndex + 0.5) / points.length) *
                                        100
                                    }%`,
                                }}
                            >
                                <p className="mb-1.5 border-b border-border-solid/60 pb-1 text-[10.5px] font-semibold text-text-secondary">
                                    {points[hoverIndex].label}
                                </p>
                                <ul className="space-y-1 text-[11px]">
                                    <li className="flex items-center gap-1.5">
                                        <span className="size-2 rounded-[2px] bg-accent-primary" />
                                        <span className="text-text-secondary">
                                            Terdaftar
                                        </span>
                                        <span className="ml-auto font-mono font-semibold text-text-primary tabular-nums">
                                            {points[hoverIndex].registered}
                                        </span>
                                    </li>
                                    <li className="flex items-center gap-1.5">
                                        <span className="size-2 rounded-[2px] bg-accent-teal" />
                                        <span className="text-text-secondary">
                                            Diverifikasi
                                        </span>
                                        <span className="ml-auto font-mono font-semibold text-text-primary tabular-nums">
                                            {points[hoverIndex].verified}
                                        </span>
                                    </li>
                                    <li className="flex items-center gap-1.5">
                                        <span className="size-2 rounded-[2px] bg-warning" />
                                        <span className="text-text-secondary">
                                            Nonaktif
                                        </span>
                                        <span className="ml-auto font-mono font-semibold text-text-primary tabular-nums">
                                            {points[hoverIndex].inactive}
                                        </span>
                                    </li>
                                </ul>
                            </div>
                        )}
                    </div>
                </div>

                <ul className="sr-only">
                    {points.map((point) => (
                        <li key={point.label}>
                            {point.label}: {point.registered} aset didaftarkan,{' '}
                            {point.verified} diverifikasi, dan {point.inactive}{' '}
                            dinonaktifkan.
                        </li>
                    ))}
                </ul>

                <div className="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 border-t border-border-solid/60 pt-3 text-[11px] text-text-secondary">
                    <span className="inline-flex items-center gap-1.5">
                        <span
                            aria-hidden="true"
                            className="size-2 rounded-[2px] bg-accent-primary"
                        />
                        Terdaftar
                    </span>
                    <span className="inline-flex items-center gap-1.5">
                        <span
                            aria-hidden="true"
                            className="size-2 rounded-[2px] bg-accent-teal"
                        />
                        Diverifikasi
                    </span>
                    <span className="inline-flex items-center gap-1.5">
                        <span
                            aria-hidden="true"
                            className="size-2 rounded-[2px] bg-warning"
                        />
                        Nonaktif
                    </span>
                </div>
            </CardContent>
        </Card>
    );
}

// ============== TICKET 04 ==============

type AssetCardTab = 'featured' | 'all';

const assignees = [
    {
        initials: 'DA',
        name: 'Dewi',
        tone: 'bg-accent-primary/15 text-accent-primary',
    },
    {
        initials: 'EL',
        name: 'Elang',
        tone: 'bg-accent-teal/15 text-accent-teal',
    },
    { initials: 'LE', name: 'Lestari', tone: 'bg-warning/15 text-warning' },
    { initials: 'AM', name: 'Amar', tone: 'bg-danger/15 text-danger' },
    {
        initials: 'AN',
        name: 'Anisa',
        tone: 'bg-accent-primary/15 text-accent-primary',
    },
    {
        initials: 'SI',
        name: 'Sifa',
        tone: 'bg-accent-teal/15 text-accent-teal',
    },
];

function QuickAction({
    icon: Icon,
    label,
}: {
    icon: LucideIcon;
    label: string;
}) {
    return (
        <button
            type="button"
            className="group flex min-w-0 flex-1 flex-col items-center gap-1.5 rounded-lg border border-border-solid bg-surface-solid p-2.5 text-text-secondary transition-colors hover:border-accent-primary/30 hover:text-accent-primary"
        >
            <span className="inline-flex size-9 items-center justify-center rounded-md bg-surface-solid-alt group-hover:bg-accent-primary/10 group-hover:text-accent-primary">
                <Icon className="size-[17px]" strokeWidth={1.9} />
            </span>
            <span className="text-[10.5px] font-semibold tracking-tight">
                {label}
            </span>
        </button>
    );
}

function AssetCardPanel() {
    const [tab, setTab] = useState<AssetCardTab>('featured');

    return (
        <Card className={glassCardClassName}>
            <CardHeader className="flex flex-row items-start justify-between gap-3 px-4 pt-4 pb-0 md:px-5 md:pt-5">
                <div className="min-w-0">
                    <CardTitle className="text-h3 font-medium tracking-tight text-text-primary">
                        Kartu aset
                    </CardTitle>
                    <p className="mt-1 text-[11.5px] text-text-secondary">
                        Aksi cepat
                    </p>
                </div>
                <button
                    type="button"
                    className="inline-flex h-8 shrink-0 items-center gap-1 rounded-full border border-border-glass px-2.5 text-[11px] font-semibold text-text-secondary transition-all hover:border-accent-primary/30 hover:text-accent-primary"
                >
                    <Plus className="size-[13px]" strokeWidth={2.4} />
                    Tambah aset
                </button>
            </CardHeader>

            <CardContent className="space-y-4 px-4 pb-4 md:px-5 md:pb-5">
                <div className="inline-flex items-center gap-0.5 rounded-lg border border-border-solid/70 bg-surface-solid/60 p-1">
                    <button
                        type="button"
                        onClick={() => setTab('featured')}
                        className={`h-11 rounded-md px-3 text-[11px] font-semibold transition-colors sm:h-7 ${
                            tab === 'featured'
                                ? 'bg-accent-primary text-white shadow-[0_3px_10px_rgba(138,108,255,0.35)]'
                                : 'text-text-secondary hover:text-text-primary'
                        }`}
                    >
                        Aset unggulan
                    </button>
                    <button
                        type="button"
                        onClick={() => setTab('all')}
                        className={`h-11 rounded-md px-3 text-[11px] font-semibold transition-colors sm:h-7 ${
                            tab === 'all'
                                ? 'bg-surface-solid-alt text-text-primary'
                                : 'text-text-secondary hover:text-text-primary'
                        }`}
                    >
                        Semua aset
                    </button>
                </div>

                <div className="relative overflow-hidden rounded-xl shadow-[0_14px_36px_rgba(138,108,255,0.22)]">
                    <div className="relative aspect-[1.66/1] w-full bg-[linear-gradient(135deg,var(--accent-primary)_0%,var(--accent-teal)_100%)] text-white">
                        <div
                            aria-hidden="true"
                            className="absolute -top-10 -right-10 size-48 rounded-full bg-white/10 blur-2xl"
                        />
                        <div
                            aria-hidden="true"
                            className="absolute -bottom-12 -left-12 size-52 rounded-full bg-black/10 blur-2xl"
                        />

                        <div className="relative z-10 flex h-full flex-col justify-between p-4 md:p-5">
                            <div className="flex items-start justify-between">
                                <div>
                                    <span className="font-mono text-[10.5px] font-semibold tracking-[0.12em] text-white/70 uppercase">
                                        Aset Tetap · Perangkat IT
                                    </span>
                                    <p className="mt-1.5 text-[15px] leading-snug font-semibold tracking-tight">
                                        Laptop ThinkPad T14
                                    </p>
                                </div>
                                <span className="inline-flex size-9 items-center justify-center rounded-lg bg-white/15 text-white">
                                    <Building2
                                        aria-hidden="true"
                                        className="size-[18px]"
                                        strokeWidth={1.9}
                                    />
                                </span>
                            </div>

                            <div>
                                <p className="font-mono text-[15px] font-semibold tracking-[0.18em] text-white/95 tabular-nums">
                                    AST-2024-••••-0091
                                </p>
                                <div className="mt-3 flex items-end justify-between">
                                    <div>
                                        <p className="text-[9.5px] font-medium tracking-[0.14em] text-white/60 uppercase">
                                            Penanggung jawab
                                        </p>
                                        <p className="mt-0.5 text-[12px] font-semibold tracking-tight">
                                            Dewi Anggraini
                                        </p>
                                    </div>
                                    <span className="rounded-full bg-white/15 px-2.5 py-1 text-[10px] font-semibold text-white">
                                        Aktif
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="grid grid-cols-5 gap-2">
                    <QuickAction icon={Plus} label="Tambah" />
                    <QuickAction icon={ArrowRightLeft} label="Transfer" />
                    <QuickAction icon={Hand} label="Pinjam" />
                    <QuickAction icon={History} label="Riwayat" />
                    <QuickAction icon={MoreHorizontal} label="Lainnya" />
                </div>

                <div>
                    <div className="mb-2 flex items-center justify-between">
                        <p className="text-[11.5px] font-semibold tracking-tight text-text-primary">
                            Penanggung jawab cepat
                        </p>
                    </div>
                    <div className="flex items-center gap-2 overflow-x-auto pb-1">
                        {assignees.map((a) => (
                            <div
                                key={a.name}
                                className="flex shrink-0 flex-col items-center gap-1"
                                title={a.name}
                            >
                                <span
                                    className={`inline-flex size-10 items-center justify-center rounded-full ring-2 ring-surface-glass ${a.tone}`}
                                >
                                    <span className="text-[11px] font-bold">
                                        {a.initials}
                                    </span>
                                </span>
                                <span className="text-[10px] font-medium text-text-secondary">
                                    {a.name}
                                </span>
                            </div>
                        ))}
                        <div className="flex shrink-0 flex-col items-center gap-1">
                            <button
                                type="button"
                                aria-label="Penanggung jawab lainnya"
                                className="inline-flex size-10 items-center justify-center rounded-full border border-dashed border-border-solid text-text-secondary transition-colors hover:border-accent-primary/40 hover:text-accent-primary"
                            >
                                <Plus
                                    aria-hidden="true"
                                    className="size-4"
                                    strokeWidth={2.2}
                                />
                            </button>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}

// ============== TICKET 05 ==============

function ProgressLimitCard() {
    const current = 86;
    const max = 100;
    const pct = (current / max) * 100;

    return (
        <Card className={glassCardClassName}>
            <CardHeader className="flex flex-row items-start justify-between gap-3 px-4 pt-4 pb-0 md:px-5 md:pt-5">
                <div className="min-w-0">
                    <CardTitle className="text-h3 font-medium tracking-tight text-text-primary">
                        Limit aset dipinjamkan
                    </CardTitle>
                    <p className="mt-1 text-[11.5px] text-text-secondary">
                        Peminjaman jangka pendek
                    </p>
                </div>
                <button
                    type="button"
                    aria-label="Ubah limit"
                    className="inline-flex size-8 items-center justify-center rounded-full text-text-secondary transition-colors hover:bg-surface-solid-alt hover:text-text-primary"
                >
                    <Pencil className="size-[15px]" strokeWidth={1.9} />
                </button>
            </CardHeader>

            <CardContent className="px-4 pt-3 pb-4 md:px-5 md:pt-4 md:pb-5">
                <div className="space-y-2">
                    <div
                        className="h-3 w-full overflow-hidden rounded-full bg-surface-solid-alt"
                        role="progressbar"
                        aria-valuemin={0}
                        aria-valuemax={max}
                        aria-valuenow={current}
                        aria-label="Limit aset dipinjamkan"
                    >
                        <div
                            className="h-full rounded-full bg-[linear-gradient(90deg,var(--accent-teal)_0%,var(--success)_100%)]"
                            style={{ width: `${pct}%` }}
                        />
                    </div>
                    <div className="flex items-center justify-between font-mono text-[12px] font-semibold text-text-primary tabular-nums">
                        <span>86 aset</span>
                        <span className="text-text-secondary">
                            dari 100 aset
                        </span>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}

function QuickTipsCard() {
    const cells = [
        'bg-accent-primary/10',
        'bg-accent-primary/15',
        'bg-accent-primary/25',
        'bg-accent-teal/10',
        'bg-accent-teal/18',
        'bg-accent-teal/25',
    ];

    return (
        <Card className={glassCardClassName}>
            <CardContent className="relative overflow-hidden px-4 py-4 md:px-5 md:py-5">
                <div
                    aria-hidden="true"
                    className="absolute top-3 right-3 grid grid-cols-3 grid-rows-2 gap-[3px] opacity-80"
                >
                    {cells.map((c, i) => (
                        <span
                            key={i}
                            className={`size-[18px] rounded-[3px] ${c}`}
                        />
                    ))}
                </div>

                <div className="max-w-[78%] pr-2">
                    <CardTitle className="text-h3 font-medium tracking-tight text-text-primary">
                        Optimalkan manajemen aset
                    </CardTitle>
                    <p className="mt-2 text-[12px] leading-relaxed text-text-secondary">
                        Jadwalkan opname Q4 dan tinjau jadwal penyusutan untuk
                        menekan nilai buku hantu.
                    </p>
                    <button
                        type="button"
                        className="mt-3 inline-flex items-center gap-1 text-[12px] font-semibold text-accent-primary transition-colors hover:text-accent-primary/80"
                    >
                        Baca selengkapnya
                        <ChevronRight
                            aria-hidden="true"
                            className="size-3"
                            strokeWidth={2.2}
                        />
                    </button>
                </div>
            </CardContent>
        </Card>
    );
}

// ============== TICKET 06a ==============

const categorySegments = [
    { label: 'Perangkat IT', pct: 34, color: 'var(--accent-primary)' },
    { label: 'Kendaraan', pct: 22, color: 'var(--accent-teal)' },
    { label: 'Mesin produksi', pct: 15, color: 'var(--warning)' },
    { label: 'Fasilitas gedung', pct: 12, color: 'var(--success)' },
    { label: 'Furniture', pct: 7, color: 'var(--text-secondary)' },
    {
        label: 'Aset tak berwujud',
        pct: 6,
        color: 'color-mix(in srgb, var(--accent-primary) 55%, transparent)',
    },
    {
        label: 'Lainnya',
        pct: 4,
        color: 'color-mix(in srgb, var(--text-secondary) 30%, transparent)',
    },
];

function CategoryAnalysisCard() {
    return (
        <Card className={glassCardClassName}>
            <CardHeader className="flex flex-row items-start justify-between gap-3 px-4 pt-4 pb-0 md:px-5 md:pt-5">
                <div className="min-w-0">
                    <CardTitle className="text-h3 font-medium tracking-tight text-text-primary">
                        Analisis kategori
                    </CardTitle>
                    <p className="mt-1 text-[11.5px] text-text-secondary">
                        Ringkasan sebaran
                    </p>
                </div>
                <label className="relative shrink-0">
                    <span className="sr-only">Pilih bulan</span>
                    <select
                        className="h-11 appearance-none rounded-lg border border-border-glass bg-surface-solid/70 px-2.5 pr-7 text-[11px] font-semibold text-text-primary transition-colors outline-none focus-visible:border-accent-primary focus-visible:ring-2 focus-visible:ring-accent-primary/30 sm:h-8"
                        defaultValue="Sep"
                    >
                        <option value="Sep">September</option>
                        <option value="Agu">Agustus</option>
                        <option value="Jul">Juli</option>
                    </select>
                    <ChevronDown
                        aria-hidden="true"
                        className="pointer-events-none absolute top-1/2 right-2 size-3 -translate-y-1/2 text-text-secondary"
                    />
                </label>
            </CardHeader>

            <CardContent className="px-4 pt-3 pb-4 md:px-5 md:pt-4 md:pb-5">
                <p className="font-mono text-display font-semibold tracking-tight text-text-primary tabular-nums">
                    Rp 8,45 M
                </p>
                <p className="mt-1 text-[11.5px] text-text-secondary">
                    total nilai buku
                </p>

                <div className="mt-3 flex h-3 w-full overflow-hidden rounded-full">
                    {categorySegments.map((seg, i) => (
                        <span
                            key={seg.label}
                            style={{
                                width: `${seg.pct}%`,
                                backgroundColor: seg.color,
                            }}
                            className={
                                i === 0
                                    ? 'rounded-l-full'
                                    : i === categorySegments.length - 1
                                      ? 'rounded-r-full'
                                      : ''
                            }
                        />
                    ))}
                </div>

                <ul className="mt-4 space-y-2.5">
                    {categorySegments.map((seg) => (
                        <li
                            key={seg.label}
                            className="flex items-center justify-between gap-3"
                        >
                            <span className="flex min-w-0 items-center gap-2">
                                <span
                                    className="inline-flex size-3 shrink-0 rounded-[3px]"
                                    style={{ backgroundColor: seg.color }}
                                />
                                <span className="truncate text-[11.5px] font-medium text-text-primary">
                                    {seg.label}
                                </span>
                            </span>
                            <span className="shrink-0 font-mono text-[11.5px] font-semibold text-text-secondary tabular-nums">
                                {seg.pct}%
                            </span>
                        </li>
                    ))}
                </ul>
            </CardContent>
        </Card>
    );
}

// ============== TICKET 06b ==============

const gaugeFillStops = [
    { offset: '0%', color: 'var(--warning)' },
    { offset: '50%', color: 'var(--accent-teal)' },
    { offset: '100%', color: 'var(--success)' },
];

const gaugeTrackStops = [
    {
        offset: '0%',
        color: 'color-mix(in srgb, var(--text-secondary) 8%, transparent)',
    },
    {
        offset: '100%',
        color: 'color-mix(in srgb, var(--text-secondary) 16%, transparent)',
    },
];

function PortfolioHealthGauge() {
    const coverage = 75;
    const radius = 64;
    const stroke = 14;
    const normalizedRadius = radius - stroke / 2;
    const circumference = Math.PI * normalizedRadius;
    const filled = (coverage / 100) * circumference;
    const trackId = 'gauge-track';
    const fillId = 'gauge-fill';

    return (
        <Card className={glassCardClassName}>
            <CardHeader className="flex flex-row items-start justify-between gap-3 px-4 pt-4 pb-0 md:px-5 md:pt-5">
                <div className="min-w-0">
                    <CardTitle className="text-h3 font-medium tracking-tight text-text-primary">
                        Kesehatan portofolio
                    </CardTitle>
                    <p className="mt-1 text-[11.5px] text-text-secondary">
                        Status terkini
                    </p>
                </div>
                <label className="relative shrink-0">
                    <span className="sr-only">Rentang waktu</span>
                    <select
                        className="h-11 appearance-none rounded-lg border border-border-glass bg-surface-solid/70 px-2.5 pr-7 text-[11px] font-semibold text-text-primary transition-colors outline-none focus-visible:border-accent-primary focus-visible:ring-2 focus-visible:ring-accent-primary/30 sm:h-8"
                        defaultValue="30d"
                    >
                        <option value="30d">30d</option>
                        <option value="90d">90d</option>
                        <option value="365d">1y</option>
                    </select>
                    <ChevronDown
                        aria-hidden="true"
                        className="pointer-events-none absolute top-1/2 right-2 size-3 -translate-y-1/2 text-text-secondary"
                    />
                </label>
            </CardHeader>

            <CardContent className="px-4 pt-3 pb-4 md:px-5 md:pt-4 md:pb-5">
                <div className="mb-2 flex items-end justify-between gap-3">
                    <div>
                        <p className="font-mono text-display font-semibold tracking-tight text-text-primary tabular-nums">
                            Rp 15,78 M
                        </p>
                        <div className="mt-1 inline-flex items-center gap-0.5 rounded-full bg-success/10 px-2 py-0.5 text-[10.5px] font-semibold text-success">
                            <ArrowUpRight
                                aria-hidden="true"
                                className="size-[11px]"
                                strokeWidth={2.4}
                            />
                            17,5% dari bulan lalu
                        </div>
                    </div>
                </div>

                <div className="relative mx-auto mt-1 w-full max-w-[240px]">
                    <svg
                        viewBox={`0 0 ${radius * 2} ${radius + stroke}`}
                        className="w-full"
                        role="img"
                        aria-label="Cakupan target penilaian aset"
                    >
                        <defs>
                            <linearGradient
                                id={fillId}
                                x1="0%"
                                x2="100%"
                                y1="0%"
                                y2="0%"
                            >
                                {gaugeFillStops.map((s) => (
                                    <stop
                                        key={s.offset}
                                        offset={s.offset}
                                        stopColor={s.color}
                                    />
                                ))}
                            </linearGradient>
                            <linearGradient
                                id={trackId}
                                x1="0%"
                                x2="100%"
                                y1="0%"
                                y2="0%"
                            >
                                {gaugeTrackStops.map((s) => (
                                    <stop
                                        key={s.offset}
                                        offset={s.offset}
                                        stopColor={s.color}
                                    />
                                ))}
                            </linearGradient>
                        </defs>
                        <circle
                            cx={radius}
                            cy={radius}
                            r={normalizedRadius}
                            stroke={`url(#${trackId})`}
                            strokeWidth={stroke}
                            strokeLinecap="round"
                            fill="none"
                            strokeDasharray={circumference}
                            strokeDashoffset={0}
                            transform={`rotate(-180 ${radius} ${radius})`}
                            className="dark:opacity-40"
                        />
                        <circle
                            cx={radius}
                            cy={radius}
                            r={normalizedRadius}
                            stroke={`url(#${fillId})`}
                            strokeWidth={stroke}
                            strokeLinecap="round"
                            fill="none"
                            strokeDasharray={circumference}
                            strokeDashoffset={circumference - filled}
                            transform={`rotate(-180 ${radius} ${radius})`}
                            style={{
                                transition: 'stroke-dashoffset 600ms ease',
                            }}
                        />
                    </svg>
                    <div className="pointer-events-none absolute inset-0 flex flex-col items-center justify-end pb-1">
                        <p className="font-mono text-[38px] leading-none font-bold text-text-primary tabular-nums">
                            {coverage}%
                        </p>
                        <p className="mt-1 text-[10.5px] font-medium text-text-secondary">
                            cakupan target penilaian
                        </p>
                    </div>
                </div>

                <p className="mt-4 border-t border-border-glass/60 pt-3 text-[11px] leading-relaxed text-text-secondary">
                    Berdasarkan metrik gabungan 30 hari terakhir.
                </p>
            </CardContent>
        </Card>
    );
}

// ============== TICKET 06c ==============

type GoalDef = {
    title: string;
    pct: number;
    label: string;
    meta: string;
    icon: LucideIcon;
    chipBg: string;
    chipText: string;
    barGradient: string;
};

const goals: GoalDef[] = [
    {
        title: 'Audit aset Q4',
        pct: 70,
        label: '700 / 1.000 aset',
        meta: 'Tersisa 2 bulan',
        icon: ClipboardCheck,
        chipBg: 'bg-warning/15',
        chipText: 'text-warning',
        barGradient:
            'bg-[linear-gradient(90deg,var(--warning),var(--accent-teal))]',
    },
    {
        title: 'Opname inventaris',
        pct: 26,
        label: '320 / 1.248 aset',
        meta: 'Tersisa 6 minggu',
        icon: PackageCheck,
        chipBg: 'bg-accent-teal/15',
        chipText: 'text-accent-teal',
        barGradient:
            'bg-[linear-gradient(90deg,var(--accent-teal),var(--success))]',
    },
    {
        title: 'Mutasi antar-cabang',
        pct: 30,
        label: '12 / 40 aset',
        meta: 'Tersisa 4 bulan',
        icon: Car,
        chipBg: 'bg-accent-primary/15',
        chipText: 'text-accent-primary',
        barGradient:
            'bg-[linear-gradient(90deg,color-mix(in srgb,var(--accent-primary) 55%,transparent),var(--accent-primary))]',
    },
    {
        title: 'Sertifikasi aset tetap',
        pct: 63,
        label: '63 / 100 dokumen',
        meta: 'Tersisa 8 bulan',
        icon: Building2,
        chipBg: 'bg-danger/10',
        chipText: 'text-danger',
        barGradient:
            'bg-[linear-gradient(90deg,color-mix(in srgb,var(--danger) 55%,transparent),var(--danger))]',
    },
];

function GoalTrackerCard() {
    return (
        <Card className={glassCardClassName}>
            <CardHeader className="flex flex-row items-start justify-between gap-3 px-4 pt-4 pb-0 md:px-5 md:pt-5">
                <div className="min-w-0">
                    <CardTitle className="text-h3 font-medium tracking-tight text-text-primary">
                        Pelacakan target
                    </CardTitle>
                </div>
                <button
                    type="button"
                    className="inline-flex h-8 shrink-0 items-center gap-1 rounded-full border border-border-glass px-2.5 text-[11px] font-semibold text-text-secondary transition-all hover:border-accent-primary/30 hover:text-accent-primary"
                >
                    <Plus className="size-[12px]" strokeWidth={2.4} />
                    Tambah target
                </button>
            </CardHeader>

            <CardContent className="space-y-4 px-4 pt-3 pb-4 md:px-5 md:pt-4 md:pb-5">
                {goals.map((g) => (
                    <GoalRow key={g.title} goal={g} />
                ))}
            </CardContent>
        </Card>
    );
}

function GoalRow({ goal }: { goal: GoalDef }) {
    const Icon = goal.icon;

    return (
        <div className="flex items-start gap-3">
            <span
                className={`mt-0.5 inline-flex size-9 shrink-0 items-center justify-center rounded-xl ${goal.chipBg}`}
            >
                <Icon
                    aria-hidden="true"
                    className={`size-[17px] ${goal.chipText}`}
                    strokeWidth={1.9}
                />
            </span>
            <div className="min-w-0 flex-1">
                <div className="flex items-start justify-between gap-2">
                    <p className="truncate text-[12px] font-semibold tracking-tight text-text-primary">
                        {goal.title}
                    </p>
                    <p className="shrink-0 font-mono text-[11.5px] font-semibold text-text-primary tabular-nums">
                        {goal.label}
                    </p>
                </div>
                <div
                    className="mt-2 h-2 w-full overflow-hidden rounded-full bg-surface-solid-alt"
                    role="progressbar"
                    aria-label={goal.title}
                    aria-valuenow={goal.pct}
                    aria-valuemin={0}
                    aria-valuemax={100}
                >
                    <div
                        className={`h-full rounded-full ${goal.barGradient}`}
                        style={{ width: `${goal.pct}%` }}
                    />
                </div>
                <p className="mt-1 text-[10.5px] font-medium text-text-secondary">
                    {goal.meta}
                </p>
            </div>
        </div>
    );
}

// ============== TICKET 07 ==============

type ActivityStatus = 'Selesai' | 'Dalam proses';

type ActivityRow = {
    id: string;
    title: string;
    meta: string;
    amount: string;
    delta: 'pos' | 'neg';
    status: ActivityStatus;
    icon: LucideIcon;
    tileBg: string;
    tileText: string;
};

const activities: ActivityRow[] = [
    {
        id: '1',
        title: 'Terima Laptop ThinkPad',
        meta: '25 Sep 2026 · AST-2024-0231',
        amount: '+Rp 18,5 jt',
        delta: 'pos',
        status: 'Selesai',
        icon: Laptop,
        tileBg: 'bg-accent-primary/12',
        tileText: 'text-accent-primary',
    },
    {
        id: '2',
        title: 'Servis printer cabang',
        meta: '24 Sep 2026 · AST-2023-1188',
        amount: 'Rp 1,2 jt',
        delta: 'neg',
        status: 'Dalam proses',
        icon: Wrench,
        tileBg: 'bg-warning/12',
        tileText: 'text-warning',
    },
    {
        id: '3',
        title: 'Mutasi mobil pool',
        meta: '23 Sep 2026 · B 1234 XYZ',
        amount: 'Rp 0',
        delta: 'neg',
        status: 'Selesai',
        icon: ArrowRightLeft,
        tileBg: 'bg-accent-teal/12',
        tileText: 'text-accent-teal',
    },
    {
        id: '4',
        title: 'Audit gudang pusat',
        meta: '21 Sep 2026 · 42 aset dicek',
        amount: '42 aset',
        delta: 'neg',
        status: 'Selesai',
        icon: Building2,
        tileBg: 'bg-danger/10',
        tileText: 'text-danger',
    },
    {
        id: '5',
        title: 'Generate label barcode',
        meta: '20 Sep 2026 · 12 label',
        amount: '12 label',
        delta: 'neg',
        status: 'Selesai',
        icon: QrCode,
        tileBg: 'bg-success/12',
        tileText: 'text-success',
    },
    {
        id: '6',
        title: 'Scan opname rutin',
        meta: '15 Sep 2026 · Sesi pagi',
        amount: '128 scan',
        delta: 'pos',
        status: 'Selesai',
        icon: ScanLine,
        tileBg: 'bg-accent-primary/12',
        tileText: 'text-accent-primary',
    },
    {
        id: '7',
        title: 'Pengajuan disposal CRT',
        meta: '12 Sep 2026 · Menunggu approval',
        amount: '2 aset',
        delta: 'neg',
        status: 'Dalam proses',
        icon: ShieldAlert,
        tileBg: 'bg-danger/10',
        tileText: 'text-danger',
    },
    {
        id: '8',
        title: 'Pengadaan forklift gudang',
        meta: '10 Sep 2026 · AST-2026-0004',
        amount: '+Rp 210 jt',
        delta: 'pos',
        status: 'Selesai',
        icon: Truck,
        tileBg: 'bg-accent-teal/12',
        tileText: 'text-accent-teal',
    },
];

const activityStatusTones = {
    Selesai: 'bg-success/10 text-success',
    'Dalam proses': 'bg-warning/12 text-warning',
} as const;

function StatusBadge({ status }: { status: ActivityStatus }) {
    return (
        <span
            role="status"
            className={`inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold ${activityStatusTones[status]}`}
        >
            {status === 'Selesai' && (
                <Check
                    aria-hidden="true"
                    className="size-[10px]"
                    strokeWidth={3}
                />
            )}
            {status}
        </span>
    );
}

function ActivityHistoryList() {
    return (
        <Card className={glassCardClassName}>
            <CardHeader className="flex flex-row items-start justify-between gap-3 px-4 pt-4 pb-0 md:px-5 md:pt-5">
                <div className="min-w-0">
                    <CardTitle className="text-h3 font-medium tracking-tight text-text-primary">
                        Riwayat aktivitas
                    </CardTitle>
                </div>
                <label className="relative shrink-0">
                    <span className="sr-only">Rentang aktivitas</span>
                    <select
                        className="h-11 appearance-none rounded-full border border-border-glass bg-surface-solid/70 px-3 pr-7 text-[11px] font-semibold text-text-primary transition-colors outline-none focus-visible:border-accent-primary focus-visible:ring-2 focus-visible:ring-accent-primary/30 sm:h-8"
                        defaultValue="7d"
                    >
                        <option value="7d">7d</option>
                        <option value="30d">30d</option>
                        <option value="all">Semua</option>
                    </select>
                    <ChevronDown
                        aria-hidden="true"
                        className="pointer-events-none absolute top-1/2 right-2 size-3 -translate-y-1/2 text-text-secondary"
                    />
                </label>
            </CardHeader>

            <CardContent className="p-0">
                <div className="max-h-[520px] overflow-y-auto bg-surface-solid">
                    <div className="space-y-2 px-4 py-3 md:px-5 md:py-4">
                        <div className="grid grid-cols-[2.5rem_minmax(0,1fr)_auto] items-center gap-2 px-1 pb-2 text-[10.5px] font-semibold text-text-secondary">
                            <span />
                            <span>Aktivitas</span>
                            <span className="text-right">Nilai</span>
                        </div>

                        <ul className="divide-y divide-border-solid/40">
                            {activities.map((a) => {
                                const Icon = a.icon;

                                return (
                                    <li
                                        key={a.id}
                                        className="grid grid-cols-[2.5rem_minmax(0,1fr)_auto] items-center gap-2 px-1.5 py-2 transition-colors hover:bg-surface-solid-alt"
                                    >
                                        <span
                                            className={`inline-flex size-10 items-center justify-center rounded-lg ${a.tileBg}`}
                                        >
                                            <Icon
                                                aria-hidden="true"
                                                className={`size-[17px] ${a.tileText}`}
                                                strokeWidth={1.9}
                                            />
                                        </span>
                                        <div className="min-w-0">
                                            <p className="truncate text-[12.5px] font-semibold tracking-tight text-text-primary">
                                                {a.title}
                                            </p>
                                            <span className="mt-0.5 block truncate font-mono text-[10.5px] text-text-secondary">
                                                {a.meta}
                                            </span>
                                        </div>
                                        <div className="flex shrink-0 flex-col items-end gap-1">
                                            <p
                                                className={`font-mono text-[12px] font-semibold tabular-nums ${
                                                    a.delta === 'pos'
                                                        ? 'text-success'
                                                        : 'text-text-primary'
                                                }`}
                                            >
                                                {a.amount}
                                            </p>
                                            <StatusBadge status={a.status} />
                                        </div>
                                    </li>
                                );
                            })}
                        </ul>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}

export default function Dashboard() {
    const [range, setRange] = useState<ChartRange>('7d');
    const chart = chartRanges[range];

    return (
        <>
            <Head title="Dashboard" />
            <div className="min-w-0 space-y-4 p-4 font-sans text-text-primary sm:p-5 lg:p-6 xl:space-y-5">
                <header className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <div className="mb-2 inline-flex items-center gap-1.5 rounded-full border border-accent-primary/20 bg-accent-primary/10 px-2.5 py-1 text-[10.5px] font-semibold text-text-primary">
                            <span
                                aria-hidden="true"
                                className="size-1.5 rounded-full bg-accent-primary"
                            />
                            Pratinjau data
                        </div>
                        <h1 className="text-display font-semibold tracking-tight text-text-primary">
                            Ringkasan aset
                        </h1>
                        <p className="mt-1 max-w-2xl text-[13px] leading-relaxed text-text-secondary">
                            Pantau nilai, kondisi, dan pergerakan aset dalam
                            satu tampilan.
                        </p>
                    </div>
                    <span className="inline-flex h-9 w-fit items-center gap-2 rounded-full border border-border-glass bg-surface-glass px-3.5 text-[12px] font-medium text-text-primary shadow-light backdrop-blur-[10px]">
                        <CalendarClock
                            aria-hidden="true"
                            className="size-[15px] text-text-secondary"
                            strokeWidth={1.9}
                        />
                        Periode contoh
                    </span>
                </header>

                <div className="grid min-w-0 grid-cols-1 gap-4 xl:gap-5 2xl:grid-cols-[minmax(0,1.65fr)_minmax(18rem,0.85fr)]">
                    <div className="grid min-w-0 gap-4 xl:gap-5">
                        <div className="grid min-w-0 gap-4 xl:grid-cols-[minmax(0,1.65fr)_minmax(15rem,0.82fr)] xl:gap-5">
                            <HeroChartPanel
                                points={chart.points}
                                rangeLabel={chart.label}
                                range={range}
                                onRangeChange={setRange}
                            />
                            <div className="grid gap-4 sm:grid-cols-3 xl:grid-cols-1 xl:gap-5">
                                <MetricCard
                                    title="Total aset terdaftar"
                                    value="1.248"
                                    changeValue="5,1%"
                                    changeTone="up"
                                    changeLabel="dari bulan lalu"
                                    icon={PackageCheck}
                                    tone="violet"
                                />
                                <MetricCard
                                    title="Terverifikasi"
                                    value="94,2%"
                                    changeValue="15,5%"
                                    changeTone="down"
                                    changeLabel="dari bulan lalu"
                                    icon={ClipboardCheck}
                                    tone="teal"
                                />
                                <MetricCard
                                    title="Dalam perawatan"
                                    value="18 aset"
                                    changeValue="20,7%"
                                    changeTone="up"
                                    changeLabel="dari bulan lalu"
                                    icon={Wrench}
                                    tone="amber"
                                />
                            </div>
                        </div>

                        <div className="grid min-w-0 gap-4 lg:grid-cols-2 xl:gap-5">
                            <ProgressLimitCard />
                            <QuickTipsCard />
                        </div>

                        <div className="grid min-w-0 gap-4 xl:grid-cols-3 xl:gap-5">
                            <CategoryAnalysisCard />
                            <PortfolioHealthGauge />
                            <GoalTrackerCard />
                        </div>
                    </div>

                    <div className="grid min-w-0 content-start gap-4 xl:gap-5">
                        <AssetCardPanel />
                        <ActivityHistoryList />
                    </div>
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
