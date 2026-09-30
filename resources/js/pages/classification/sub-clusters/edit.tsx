import { usePage } from '@inertiajs/react';
import AssetSubClusterController from '@/actions/App/Http/Controllers/Classification/AssetSubClusterController';
import { ResourceForm } from '@/components/classification/resource-form';
import { index as subClustersIndex } from '@/routes/classifications/sub-clusters';

type PageProps = {
    subCluster: {
        id: string;
        code: string;
        name: string;
        cluster: { id: string; code: string; name: string } | null;
    };
};

/**
 * Edit-sub-kelompok page (Admin Tenant).
 *
 * The parent kelompok is never editable — only the code and name travel
 * here. A referenced code edit renders as a validation error under the
 * code field (ADR-0001).
 */
export default function ClassificationSubClusterEdit() {
    const { subCluster } = usePage<PageProps>().props;

    return (
        <ResourceForm
            formProps={AssetSubClusterController.update.form({
                subCluster: subCluster.id,
            })}
            values={{ code: subCluster.code, name: subCluster.name }}
            codeLabel="Kode sub kelompok"
            nameLabel="Nama sub kelompok"
            parentSelect={{
                fieldName: 'asset_cluster_id',
                label: 'Kelompok',
                selectedLabel: subCluster.cluster
                    ? `${subCluster.cluster.code} — ${subCluster.cluster.name}`
                    : '—',
            }}
            cancelHref={subClustersIndex().url}
        />
    );
}

ClassificationSubClusterEdit.layout = {
    breadcrumbs: [
        { title: 'Klasifikasi', href: '#' },
        { title: 'Sub Kelompok', href: subClustersIndex().url },
        { title: 'Edit', href: '' },
    ],
};
