import { Head } from "@inertiajs/react";
import {
    ArrowDownLeft,
    ArrowUpRight,
    CalendarClock,
    Check,
    ChevronDown,
    ClipboardCheck,
    History,
    PackageCheck,
    ShieldAlert,
    Wrench,
    type LucideIcon,
} from "lucide-react";
import { useState } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { dashboard } from "@/routes";

type ChartRange = "7d" | "30d" | "90d";
type ChartPoint = {
    label: string;
    registered: number;
    verified: number;
    inactive: number;
};
type MetricTone = "violet" | "teal" | "amber";
type MovementStatus = "Terverifikasi" | "Dipindahkan" | "Menunggu";

type Movement = {
    assetCode: string;
    assetName: string;
    owner: string;
    date: string;
    dateTime: string;
    status: MovementStatus;
};

const chartRanges: Record<ChartRange, { label: string; points: ChartPoint[] }> =
    {
        "7d": {
            label: "7 hari terakhir",
            points: [
                { label: "Sen", registered: 8, verified: 6, inactive: 1 },
                { label: "Sel", registered: 10, verified: 7, inactive: 2 },
                { label: "Rab", registered: 12, verified: 8, inactive: 1 },
                { label: "Kam", registered: 9, verified: 11, inactive: 2 },
                { label: "Jum", registered: 15, verified: 9, inactive: 1 },
                { label: "Sab", registered: 11, verified: 7, inactive: 1 },
                { label: "Min", registered: 17, verified: 10, inactive: 2 },
            ],
        },
        "30d": {
            label: "30 hari terakhir",
            points: [
                { label: "01–06", registered: 34, verified: 28, inactive: 5 },
                { label: "07–12", registered: 40, verified: 32, inactive: 7 },
                { label: "13–18", registered: 31, verified: 38, inactive: 4 },
                { label: "19–24", registered: 48, verified: 35, inactive: 6 },
                { label: "25–30", registered: 55, verified: 42, inactive: 8 },
            ],
        },
        "90d": {
            label: "90 hari terakhir",
            points: [
                { label: "Jul", registered: 82, verified: 72, inactive: 12 },
                { label: "Agu", registered: 96, verified: 84, inactive: 15 },
                { label: "Sep", registered: 118, verified: 103, inactive: 18 },
            ],
        },
    };

const categories = [
    { name: "Perangkat IT", count: "474 aset", value: 38, tone: "violet" },
    { name: "Kendaraan", count: "300 aset", value: 24, tone: "teal" },
    { name: "Mesin & alat", count: "237 aset", value: 19, tone: "amber" },
    { name: "Fasilitas", count: "150 aset", value: 12, tone: "violet" },
    { name: "Lainnya", count: "87 aset", value: 7, tone: "teal" },
] as const;

const recentMovements: Movement[] = [
    {
        assetCode: "AST-IT-0421",
        assetName: "Laptop ThinkPad T14",
        owner: "Dina Mahendra · Finance",
        date: "23 Sep 2026",
        dateTime: "2026-09-23",
        status: "Terverifikasi",
    },
    {
        assetCode: "AST-VH-0182",
        assetName: "Toyota Innova Zenix",
        owner: "Pool kendaraan · Jakarta",
        date: "22 Sep 2026",
        dateTime: "2026-09-22",
        status: "Dipindahkan",
    },
    {
        assetCode: "AST-OF-0315",
        assetName: "Epson WorkForce Pro",
        owner: "Ruang administrasi",
        date: "21 Sep 2026",
        dateTime: "2026-09-21",
        status: "Menunggu",
    },
    {
        assetCode: "AST-MC-0098",
        assetName: "Hydraulic press HP-20",
        owner: "Workshop · Bekasi",
        date: "20 Sep 2026",
        dateTime: "2026-09-20",
        status: "Terverifikasi",
    },
];

const metricTones: Record<MetricTone, { icon: string; change: string }> = {
    violet: {
        icon: "bg-accent-primary/10 text-accent-primary",
        change: "text-text-secondary",
    },
    teal: {
        icon: "bg-accent-teal/10 text-accent-teal",
        change: "text-text-secondary",
    },
    amber: {
        icon: "bg-warning/10 text-warning",
        change: "text-text-secondary",
    },
};

const glassCardClassName =
    "min-w-0 gap-0 rounded-2xl border-border-glass bg-surface-glass p-0 text-text-primary shadow-light backdrop-blur-[20px] dark:shadow-dark";
const solidCardClassName =
    "min-w-0 gap-0 rounded-[4px] border-border-solid bg-surface-solid p-0 text-text-primary shadow-none";

function MetricCard({
    title,
    value,
    note,
    icon: Icon,
    tone,
}: {
    title: string;
    value: string;
    note: string;
    icon: LucideIcon;
    tone: MetricTone;
}) {
    const toneClasses = metricTones[tone];

    return (
        <Card className={glassCardClassName}>
            <CardContent className="flex items-start justify-between gap-3 p-4">
                <div className="min-w-0">
                    <p className="text-xs font-medium text-text-secondary">
                        {title}
                    </p>
                    <p className="mt-2 font-mono text-2xl font-medium tabular-nums tracking-tight text-text-primary">
                        {value}
                    </p>
                    <p className={`mt-1 text-xs ${toneClasses.change}`}>
                        {note}
                    </p>
                </div>
                <span
                    className={`inline-flex size-9 shrink-0 items-center justify-center rounded-lg ${toneClasses.icon}`}
                >
                    <Icon
                        aria-hidden="true"
                        className="size-4"
                        strokeWidth={1.8}
                    />
                </span>
            </CardContent>
        </Card>
    );
}

function ChartPanel({
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
    const maxValue = Math.max(
        ...points.map(
            (point) => point.registered + point.verified + point.inactive,
        ),
    );
    const axisLabels = [maxValue, Math.round(maxValue / 2), 0];

    return (
        <Card className={glassCardClassName}>
            <CardHeader className="flex flex-row items-start justify-between gap-4 px-4 pb-0 pt-4 md:px-5 md:pt-5">
                <div className="min-w-0">
                    <CardTitle className="text-base font-semibold tracking-tight text-text-primary">
                        Pergerakan aset
                    </CardTitle>
                    <p className="mt-1 text-xs text-text-secondary">
                        Pendaftaran dan verifikasi · {rangeLabel}
                    </p>
                </div>
                <label className="relative shrink-0">
                    <span className="sr-only">
                        Rentang grafik pergerakan aset
                    </span>
                    <select
                        className="h-9 appearance-none rounded-lg border border-border-solid bg-surface-solid px-3 pr-8 text-xs font-medium text-text-primary outline-none transition-colors focus-visible:border-accent-primary focus-visible:ring-2 focus-visible:ring-accent-primary/30"
                        value={range}
                        onChange={(event) =>
                            onRangeChange(event.target.value as ChartRange)
                        }
                    >
                        <option value="7d">7 hari</option>
                        <option value="30d">30 hari</option>
                        <option value="90d">90 hari</option>
                    </select>
                    <ChevronDown
                        aria-hidden="true"
                        className="pointer-events-none absolute right-2.5 top-1/2 size-3.5 -translate-y-1/2 text-text-secondary"
                    />
                </label>
            </CardHeader>
            <CardContent className="px-4 pb-4 pt-5 md:px-5 md:pb-5">
                <div className="mb-4 flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <p className="font-mono text-display font-medium tracking-tight text-text-primary">
                            1.248
                        </p>
                        <p className="mt-1 text-xs text-text-secondary">
                            aset tercatat dalam contoh workspace
                        </p>
                    </div>
                    <div className="inline-flex items-center gap-1 rounded-full bg-success/10 px-2.5 py-1 text-xs font-medium text-text-primary">
                        <ArrowUpRight
                            aria-hidden="true"
                            className="size-3.5 text-success"
                        />
                        8,4% dibanding periode lalu
                    </div>
                </div>
                <div className="grid grid-cols-[2rem_minmax(0,1fr)] gap-3">
                    <div className="flex h-40 flex-col justify-between pb-6 text-right font-mono text-mono-data tabular-nums text-text-secondary">
                        {axisLabels.map((label, index) => (
                            <span key={`${label}-${index}`}>{label}</span>
                        ))}
                    </div>
                    <div className="relative h-40">
                        <div
                            aria-hidden="true"
                            className="absolute inset-x-0 bottom-6 top-0 flex flex-col justify-between"
                        >
                            {[0, 1, 2].map((line) => (
                                <span
                                    key={line}
                                    className="border-t border-border-solid/80"
                                />
                            ))}
                        </div>
                        <div
                            aria-hidden="true"
                            className="absolute inset-x-0 bottom-6 top-0 grid items-end gap-2 sm:gap-4"
                            style={{
                                gridTemplateColumns: `repeat(${points.length}, minmax(0, 1fr))`,
                            }}
                        >
                            {points.map((point) => {
                                const total =
                                    point.registered +
                                    point.verified +
                                    point.inactive;
                                const safeTotal = Math.max(total, 1);

                                return (
                                    <div
                                        key={point.label}
                                        className="flex h-full items-end justify-center"
                                    >
                                        <div
                                            className="flex w-full max-w-8 flex-col-reverse overflow-hidden rounded-t-[4px] bg-surface-solid-alt"
                                            style={{
                                                height: `${(total / maxValue) * 100}%`,
                                            }}
                                        >
                                            <span
                                                className="bg-accent-primary"
                                                style={{
                                                    height: `${(point.registered / safeTotal) * 100}%`,
                                                }}
                                            />
                                            <span
                                                className="bg-accent-teal"
                                                style={{
                                                    height: `${(point.verified / safeTotal) * 100}%`,
                                                }}
                                            />
                                            <span
                                                className="bg-warning"
                                                style={{
                                                    height: `${(point.inactive / safeTotal) * 100}%`,
                                                }}
                                            />
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                        <div
                            aria-hidden="true"
                            className="absolute inset-x-0 bottom-0 grid h-5 items-end gap-2 text-center text-small text-text-secondary sm:gap-4"
                            style={{
                                gridTemplateColumns: `repeat(${points.length}, minmax(0, 1fr))`,
                            }}
                        >
                            {points.map((point) => (
                                <span key={point.label}>{point.label}</span>
                            ))}
                        </div>
                    </div>
                </div>
                <ul className="sr-only">
                    {points.map((point) => (
                        <li key={point.label}>
                            {point.label}: {point.registered} aset didaftarkan,{" "}
                            {point.verified} diverifikasi, dan {point.inactive}{" "}
                            dinonaktifkan.
                        </li>
                    ))}
                </ul>
                <div className="mt-4 flex flex-wrap gap-x-4 gap-y-2 border-t border-border-solid/80 pt-3 text-small text-text-secondary">
                    <Legend color="bg-accent-primary" label="Terdaftar" />
                    <Legend color="bg-accent-teal" label="Diverifikasi" />
                    <Legend color="bg-warning" label="Nonaktif" />
                </div>
            </CardContent>
        </Card>
    );
}

function Legend({ color, label }: { color: string; label: string }) {
    return (
        <span className="inline-flex items-center gap-1.5">
            <span
                aria-hidden="true"
                className={`size-2 rounded-[2px] ${color}`}
            />
            {label}
        </span>
    );
}

function CategoryPanel() {
    return (
        <Card className={glassCardClassName}>
            <CardHeader className="px-4 pb-1 pt-4 md:px-5 md:pt-5">
                <CardTitle className="text-base font-semibold tracking-tight text-text-primary">
                    Sebaran kategori
                </CardTitle>
                <p className="mt-1 text-xs text-text-secondary">
                    Komposisi aset berdasarkan jumlah
                </p>
            </CardHeader>
            <CardContent className="space-y-4 px-4 pb-4 pt-3 md:px-5 md:pb-5">
                {categories.map((category) => (
                    <div key={category.name}>
                        <div className="mb-1.5 flex items-center justify-between gap-3 text-xs">
                            <span className="font-medium text-text-primary">
                                {category.name}
                            </span>
                            <span className="font-mono tabular-nums text-text-secondary">
                                {category.count}
                            </span>
                        </div>
                        <div
                            aria-label={`${category.name}, ${category.value}%`}
                            className="h-2 overflow-hidden rounded-full bg-surface-solid-alt"
                            role="img"
                        >
                            <div
                                className={`h-full rounded-full ${category.tone === "violet" ? "bg-accent-primary" : category.tone === "teal" ? "bg-accent-teal" : "bg-warning"}`}
                                style={{ width: `${category.value}%` }}
                            />
                        </div>
                    </div>
                ))}
                <p className="border-t border-border-glass pt-3 text-small leading-relaxed text-text-secondary">
                    Persentase dan jumlah ditampilkan sebagai data contoh.
                </p>
            </CardContent>
        </Card>
    );
}

function AttentionPanel() {
    const attentionItems = [
        {
            title: "Validasi aset baru",
            detail: "18 item menunggu pemeriksaan",
            icon: ShieldAlert,
            tone: "amber",
        },
        {
            title: "Jadwal pemeliharaan",
            detail: "7 aset perlu ditinjau minggu ini",
            icon: Wrench,
            tone: "violet",
        },
        {
            title: "Audit lokasi",
            detail: "3 lokasi belum dikonfirmasi",
            icon: ClipboardCheck,
            tone: "teal",
        },
    ] as const;

    return (
        <Card className={glassCardClassName}>
            <CardHeader className="px-4 pb-1 pt-4 md:px-5 md:pt-5">
                <div className="flex items-center justify-between gap-3">
                    <div>
                        <CardTitle className="text-base font-semibold tracking-tight text-text-primary">
                            Perlu tindak lanjut
                        </CardTitle>
                        <p className="mt-1 text-xs text-text-secondary">
                            Prioritas operasional
                        </p>
                    </div>
                    <span className="rounded-full bg-accent-primary/10 px-2 py-1 font-mono text-xs font-medium tabular-nums text-text-primary">
                        28
                    </span>
                </div>
            </CardHeader>
            <CardContent className="space-y-1 px-3 pb-3 pt-2 md:px-4 md:pb-4">
                {attentionItems.map(({ title, detail, icon: Icon, tone }) => (
                    <div
                        key={title}
                        className="flex items-center gap-3 rounded-xl px-2 py-3"
                    >
                        <span
                            className={`inline-flex size-9 shrink-0 items-center justify-center rounded-lg ${metricTones[tone].icon}`}
                        >
                            <Icon
                                aria-hidden="true"
                                className="size-4"
                                strokeWidth={1.8}
                            />
                        </span>
                        <div className="min-w-0 flex-1">
                            <p className="text-xs font-medium text-text-primary">
                                {title}
                            </p>
                            <p className="mt-1 text-[11px] text-text-secondary">
                                {detail}
                            </p>
                        </div>
                    </div>
                ))}
            </CardContent>
        </Card>
    );
}

function ActivityPanel() {
    return (
        <Card className={solidCardClassName}>
            <CardHeader className="flex flex-row items-start justify-between gap-3 px-4 pb-1 pt-4 md:px-5 md:pt-5">
                <div>
                    <CardTitle className="text-base font-semibold tracking-tight text-text-primary">
                        Aktivitas aset
                    </CardTitle>
                    <p className="mt-1 text-xs text-text-secondary">
                        Perubahan terakhir di workspace contoh
                    </p>
                </div>
                <History
                    aria-hidden="true"
                    className="mt-0.5 size-4 shrink-0 text-text-secondary"
                    strokeWidth={1.8}
                />
            </CardHeader>
            <CardContent className="px-0 pb-1 pt-3">
                <div className="overflow-x-auto">
                    <table className="w-full min-w-[34rem] border-collapse text-left text-xs">
                        <caption className="sr-only">
                            Aktivitas aset contoh dalam workspace ilustratif
                        </caption>
                        <thead>
                            <tr className="border-y border-border-solid bg-surface-solid-alt text-[10px] uppercase tracking-wide text-text-secondary">
                                <th
                                    scope="col"
                                    className="px-4 py-2.5 font-medium md:px-5"
                                >
                                    Aset
                                </th>
                                <th
                                    scope="col"
                                    className="px-4 py-2.5 font-medium"
                                >
                                    Penanggung jawab
                                </th>
                                <th
                                    scope="col"
                                    className="px-4 py-2.5 font-medium"
                                >
                                    Aktivitas
                                </th>
                                <th
                                    scope="col"
                                    className="px-4 py-2.5 text-right font-medium md:px-5"
                                >
                                    Tanggal
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {recentMovements.map((movement) => (
                                <tr
                                    key={movement.assetCode}
                                    className="border-b border-border-solid/80 last:border-b-0"
                                >
                                    <td className="px-4 py-3 md:px-5">
                                        <div className="min-w-0">
                                            <p className="font-medium text-text-primary">
                                                {movement.assetName}
                                            </p>
                                            <p className="mt-1 font-mono text-[10px] tabular-nums text-text-secondary">
                                                {movement.assetCode}
                                            </p>
                                        </div>
                                    </td>
                                    <td className="px-4 py-3 text-text-secondary">
                                        {movement.owner}
                                    </td>
                                    <td className="px-4 py-3">
                                        <StatusLabel status={movement.status} />
                                    </td>
                                    <td className="px-4 py-3 text-right text-text-secondary md:px-5">
                                        <time dateTime={movement.dateTime}>
                                            {movement.date}
                                        </time>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                <p className="px-4 py-3 text-[11px] text-text-secondary md:px-5">
                    Data tabel bersifat ilustratif dan bukan catatan
                    operasional.
                </p>
            </CardContent>
        </Card>
    );
}

function StatusLabel({ status }: { status: MovementStatus }) {
    const statusClasses: Record<MovementStatus, string> = {
        Terverifikasi: "bg-success/10",
        Dipindahkan: "bg-accent-primary/10",
        Menunggu: "bg-warning/10",
    };
    const statusIconClasses: Record<MovementStatus, string> = {
        Terverifikasi: "text-success",
        Dipindahkan: "text-accent-primary",
        Menunggu: "text-warning",
    };
    const statusIcons: Record<MovementStatus, LucideIcon> = {
        Terverifikasi: Check,
        Dipindahkan: ArrowDownLeft,
        Menunggu: CalendarClock,
    };
    const Icon = statusIcons[status];

    return (
        <span
            className={`inline-flex items-center gap-1 rounded-full px-2 py-1 text-[10px] font-medium text-text-primary ${statusClasses[status]}`}
        >
            <Icon
                aria-hidden="true"
                className={`size-3 ${statusIconClasses[status]}`}
            />
            {status}
        </span>
    );
}

function AuditSchedule() {
    const audits = [
        {
            location: "Kantor pusat · Jakarta",
            assets: "42 aset",
            due: "25 Sep",
            dateTime: "2026-09-25",
        },
        {
            location: "Gudang · Bekasi",
            assets: "28 aset",
            due: "29 Sep",
            dateTime: "2026-09-29",
        },
        {
            location: "Cabang · Bandung",
            assets: "16 aset",
            due: "03 Okt",
            dateTime: "2026-10-03",
        },
    ];

    return (
        <Card className={glassCardClassName}>
            <CardHeader className="px-4 pb-1 pt-4 md:px-5 md:pt-5">
                <div className="flex items-center gap-2">
                    <CalendarClock
                        aria-hidden="true"
                        className="size-4 text-accent-primary"
                        strokeWidth={1.8}
                    />
                    <CardTitle className="text-base font-semibold tracking-tight text-text-primary">
                        Jadwal audit
                    </CardTitle>
                </div>
                <p className="mt-1 text-xs text-text-secondary">
                    Agenda contoh yang akan datang
                </p>
            </CardHeader>
            <CardContent className="px-4 pb-4 pt-3 md:px-5 md:pb-5">
                <div className="space-y-0">
                    {audits.map((audit, index) => (
                        <div
                            key={audit.location}
                            className="relative flex gap-3 pb-4 last:pb-0"
                        >
                            {index < audits.length - 1 && (
                                <span
                                    aria-hidden="true"
                                    className="absolute bottom-0 left-[7px] top-4 w-px bg-border-solid"
                                />
                            )}
                            <span className="relative mt-1 inline-flex size-4 shrink-0 items-center justify-center rounded-full border border-accent-primary/40 bg-surface-glass">
                                {index === 0 ? (
                                    <span className="size-1.5 rounded-full bg-accent-primary" />
                                ) : (
                                    <span className="size-1 rounded-full bg-text-secondary/60" />
                                )}
                            </span>
                            <div className="min-w-0 flex-1">
                                <div className="flex items-start justify-between gap-2">
                                    <p className="text-xs font-medium text-text-primary">
                                        {audit.location}
                                    </p>
                                    <time
                                        className="shrink-0 font-mono text-[10px] tabular-nums text-text-secondary"
                                        dateTime={audit.dateTime}
                                    >
                                        {audit.due}
                                    </time>
                                </div>
                                <p className="mt-1 text-[11px] text-text-secondary">
                                    {audit.assets} perlu dicocokkan
                                </p>
                            </div>
                        </div>
                    ))}
                </div>
                <div className="mt-3 flex items-center gap-2 border-t border-border-glass pt-3 text-[11px] text-text-secondary">
                    <PackageCheck
                        aria-hidden="true"
                        className="size-3.5 text-accent-teal"
                    />
                    Agenda dan jumlah aset adalah data contoh.
                </div>
            </CardContent>
        </Card>
    );
}

export default function Dashboard() {
    const [range, setRange] = useState<ChartRange>("7d");
    const chart = chartRanges[range];

    return (
        <>
            <Head title="Dashboard" />
            <main className="min-w-0 space-y-4 p-4 font-sans text-text-primary sm:p-5 lg:p-6">
                <header className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <div className="mb-2 inline-flex items-center gap-1.5 rounded-full border border-accent-primary/20 bg-accent-primary/10 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.08em] text-text-primary">
                            <span
                                aria-hidden="true"
                                className="size-1.5 rounded-full bg-accent-primary"
                            />
                            Pratinjau data
                        </div>
                        <h1 className="text-2xl font-semibold tracking-tight text-text-primary sm:text-[1.75rem]">
                            Ringkasan aset
                        </h1>
                        <p className="mt-1 max-w-2xl text-sm text-text-secondary">
                            Pantau nilai, kondisi, dan pergerakan aset dalam
                            satu tampilan.
                        </p>
                    </div>
                    <span className="inline-flex h-9 w-fit items-center gap-2 rounded-lg border border-border-solid bg-surface-solid px-3 text-xs font-medium text-text-primary">
                        <CalendarClock
                            aria-hidden="true"
                            className="size-3.5 text-text-secondary"
                        />
                        Periode contoh
                    </span>
                </header>

                <div className="grid min-w-0 grid-cols-1 gap-4 2xl:grid-cols-[minmax(0,1.65fr)_minmax(18rem,0.85fr)]">
                    <div className="grid min-w-0 gap-4">
                        <div className="grid min-w-0 gap-4 xl:grid-cols-[minmax(0,1.65fr)_minmax(15rem,0.82fr)]">
                            <ChartPanel
                                points={chart.points}
                                rangeLabel={chart.label}
                                range={range}
                                onRangeChange={setRange}
                            />
                            <div className="grid gap-4 sm:grid-cols-3 xl:grid-cols-1">
                                <MetricCard
                                    title="Nilai buku aset"
                                    value="Rp 12,45 M"
                                    note="Nilai tercatat ilustratif"
                                    icon={ClipboardCheck}
                                    tone="violet"
                                />
                                <MetricCard
                                    title="Dalam pemeliharaan"
                                    value="18"
                                    note="Contoh aset aktif"
                                    icon={Wrench}
                                    tone="amber"
                                />
                                <MetricCard
                                    title="Verifikasi selesai"
                                    value="94,2%"
                                    note="Capaian data contoh"
                                    icon={PackageCheck}
                                    tone="teal"
                                />
                            </div>
                        </div>

                        <div className="grid min-w-0 gap-4 lg:grid-cols-2">
                            <CategoryPanel />
                            <AttentionPanel />
                        </div>
                    </div>

                    <div className="grid min-w-0 content-start gap-4">
                        <ActivityPanel />
                        <AuditSchedule />
                    </div>
                </div>
            </main>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: "Dashboard",
            href: dashboard(),
        },
    ],
};
