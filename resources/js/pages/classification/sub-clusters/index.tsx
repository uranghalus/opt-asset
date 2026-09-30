import { usePage } from '@inertiajs/react';
import {
    ResourceIndex,
    type ResourceIndexRow,
} from '@/components/classification/resource-index';
import {
    create as subClustersCreate,
    destroy as subClustersDestroy,
    edit as subClustersEdit,
    index as subClustersIndex,
} from '@/routes/classifications/sub-clusters';

type PageProps = {
    subClusters: {
        data: {
            id: string;
            code: string;
            name: string;
            cluster: { id: string; code: string; name: string } | null;
        }[];
        total: number;
        from: number | null;
        current_page: number;
        last_page: number;
    };
};

/**
 * Asset sub-cluster (sub kelompok) index — the fourth level of the chain.
 *
 * Delegates to the shared classification index; the parent column shows the
 * owning kelompok per row (eager-loaded server-side, no client fetch).
 */
export default function ClassificationSubClusterIndex() {
    const { subClusters } = usePage<PageProps>().props;

    const rows: ResourceIndexRow[] = subClusters.data.map((subCluster) => ({
        id: subCluster.id,
        code: subCluster.code,
        name: subCluster.name,
        parentLabel: subCluster.cluster
            ? `${subCluster.cluster.code} — ${subCluster.cluster.name}`
            : null,
    }));

    return (
        <ResourceIndex
            title="Sub Kelompok"
            description="Level keempat rantai klasifikasi — setiap sub kelompok menumpuk di bawah satu kelompok."
            rows={rows}
            total={subClusters.total}
            from={subClusters.from}
            currentPage={subClusters.current_page}
            lastPage={subClusters.last_page}
            pageHref={(page) => subClustersIndex({ query: { page } }).url}
            onlyKeys={['subClusters']}
            createHref={subClustersCreate().url}
            createLabel="Tambah sub kelompok"
            editHref={(id) => subClustersEdit({ subCluster: id }).url}
            deleteHref={(id) => subClustersDestroy({ subCluster: id }).url}
            emptyState={{
                title: 'Belum ada sub kelompok',
                description:
                    'Buat sub kelompok di bawah kelompok — item aset menumpuk di bawahnya.',
            }}
            labels={{
                singular: 'sub kelompok',
                code: 'Kode',
                parent: 'Kelompok',
            }}
        />
    );
}

ClassificationSubClusterIndex.layout = {
    breadcrumbs: [
        { title: 'Klasifikasi', href: '#' },
        { title: 'Sub Kelompok', href: subClustersIndex().url },
    ],
};
