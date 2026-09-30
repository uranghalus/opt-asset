import { usePage } from '@inertiajs/react';
import AssetClusterController from '@/actions/App/Http/Controllers/Classification/AssetClusterController';
import { ResourceForm } from '@/components/classification/resource-form';
import { index as clustersIndex } from '@/routes/classifications/clusters';

type PageProps = {
    cluster: {
        id: string;
        code: string;
        name: string;
        category: { id: string; code: string; name: string } | null;
    };
};

/**
 * Edit-kelompok page (Admin Tenant).
 *
 * The parent kategori is never editable — only the code and name travel
 * here. A referenced code edit renders as a validation error under the
 * code field (ADR-0001).
 */
export default function ClassificationClusterEdit() {
    const { cluster } = usePage<PageProps>().props;

    return (
        <ResourceForm
            formProps={AssetClusterController.update.form({
                cluster: cluster.id,
            })}
            values={{ code: cluster.code, name: cluster.name }}
            codeLabel="Kode kelompok"
            nameLabel="Nama kelompok"
            parentSelect={{
                fieldName: 'asset_category_id',
                label: 'Kategori',
                selectedLabel: cluster.category
                    ? `${cluster.category.code} — ${cluster.category.name}`
                    : '—',
            }}
            cancelHref={clustersIndex().url}
        />
    );
}

ClassificationClusterEdit.layout = {
    breadcrumbs: [
        { title: 'Klasifikasi', href: '#' },
        { title: 'Kelompok', href: clustersIndex().url },
        { title: 'Edit', href: '' },
    ],
};
