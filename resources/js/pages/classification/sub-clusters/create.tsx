import AssetSubClusterController from '@/actions/App/Http/Controllers/Classification/AssetSubClusterController';
import {
    ResourceForm,
    type ParentOption,
} from '@/components/classification/resource-form';
import { usePage } from '@inertiajs/react';
import { index as subClustersIndex } from '@/routes/classifications/sub-clusters';

type PageProps = {
    clusters: { id: string; code: string; name: string }[];
};

/**
 * Create-sub-kelompok page (Admin Tenant).
 *
 * The parent kelompok is chosen from the acting tenant's own clusters only —
 * the server-side validation rejects any other tenant's parents
 * (fail-closed, watchpoint #30).
 */
export default function ClassificationSubClusterCreate() {
    const { clusters } = usePage<PageProps>().props;

    const options: ParentOption[] = clusters.map((cluster) => ({
        id: cluster.id,
        label: `${cluster.code} — ${cluster.name}`,
    }));

    return (
        <ResourceForm
            formProps={AssetSubClusterController.store.form()}
            codeLabel="Kode sub kelompok"
            nameLabel="Nama sub kelompok"
            parentSelect={{
                fieldName: 'asset_cluster_id',
                label: 'Kelompok',
                options,
            }}
            cancelHref={subClustersIndex().url}
        />
    );
}

ClassificationSubClusterCreate.layout = {
    breadcrumbs: [
        { title: 'Klasifikasi', href: '#' },
        { title: 'Sub Kelompok', href: subClustersIndex().url },
        { title: 'Tambah', href: '' },
    ],
};
