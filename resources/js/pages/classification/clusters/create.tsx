import AssetClusterController from '@/actions/App/Http/Controllers/Classification/AssetClusterController';
import {
    ResourceForm,
    type ParentOption,
} from '@/components/classification/resource-form';
import { usePage } from '@inertiajs/react';
import { index as clustersIndex } from '@/routes/classifications/clusters';

type PageProps = {
    categories: { id: string; code: string; name: string }[];
};

/**
 * Create-kelompok page (Admin Tenant).
 *
 * The parent kategori is chosen from the acting tenant's own categories
 * only — the server-side validation rejects any other tenant's parents
 * (fail-closed, watchpoint #30).
 */
export default function ClassificationClusterCreate() {
    const { categories } = usePage<PageProps>().props;

    const options: ParentOption[] = categories.map((category) => ({
        id: category.id,
        label: `${category.code} — ${category.name}`,
    }));

    return (
        <ResourceForm
            formProps={AssetClusterController.store.form()}
            codeLabel="Kode kelompok"
            nameLabel="Nama kelompok"
            parentSelect={{
                fieldName: 'asset_category_id',
                label: 'Kategori',
                options,
            }}
            cancelHref={clustersIndex().url}
        />
    );
}

ClassificationClusterCreate.layout = {
    breadcrumbs: [
        { title: 'Klasifikasi', href: '#' },
        { title: 'Kelompok', href: clustersIndex().url },
        { title: 'Tambah', href: '' },
    ],
};
