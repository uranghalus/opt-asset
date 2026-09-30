import { usePage } from '@inertiajs/react';
import {
    ResourceIndex,
    type ResourceIndexRow,
} from '@/components/classification/resource-index';
import {
    create as clustersCreate,
    destroy as clustersDestroy,
    edit as clustersEdit,
    index as clustersIndex,
} from '@/routes/classifications/clusters';

type PageProps = {
    clusters: {
        data: {
            id: string;
            code: string;
            name: string;
            category: { id: string; code: string; name: string } | null;
        }[];
        total: number;
        from: number | null;
        current_page: number;
        last_page: number;
    };
};

/**
 * Asset cluster (kelompok) index — the third level of the chain.
 *
 * Delegates to the shared classification index; the parent column shows the
 * owning kategori per row (eager-loaded server-side, no client fetch).
 */
export default function ClassificationClusterIndex() {
    const { clusters } = usePage<PageProps>().props;

    const rows: ResourceIndexRow[] = clusters.data.map((cluster) => ({
        id: cluster.id,
        code: cluster.code,
        name: cluster.name,
        parentLabel: cluster.category
            ? `${cluster.category.code} — ${cluster.category.name}`
            : null,
    }));

    return (
        <ResourceIndex
            title="Kelompok"
            description="Level ketiga rantai klasifikasi — setiap kelompok menumpuk di bawah satu kategori."
            rows={rows}
            total={clusters.total}
            from={clusters.from}
            currentPage={clusters.current_page}
            lastPage={clusters.last_page}
            pageHref={(page) => clustersIndex({ query: { page } }).url}
            onlyKeys={['clusters']}
            createHref={clustersCreate().url}
            createLabel="Tambah kelompok"
            editHref={(id) => clustersEdit({ cluster: id }).url}
            deleteHref={(id) => clustersDestroy({ cluster: id }).url}
            emptyState={{
                title: 'Belum ada kelompok',
                description:
                    'Buat kelompok di bawah kategori — sub kelompok menumpuk di bawahnya.',
            }}
            labels={{
                singular: 'kelompok',
                code: 'Kode',
                parent: 'Kategori',
            }}
        />
    );
}

ClassificationClusterIndex.layout = {
    breadcrumbs: [
        { title: 'Klasifikasi', href: '#' },
        { title: 'Kelompok', href: clustersIndex().url },
    ],
};
