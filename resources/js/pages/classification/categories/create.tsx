import AssetCategoryController from '@/actions/App/Http/Controllers/Classification/AssetCategoryController';
import {
    ResourceForm,
    type ParentOption,
} from '@/components/classification/resource-form';
import { usePage } from '@inertiajs/react';
import { index as categoriesIndex } from '@/routes/classifications/categories';

type PageProps = {
    groups: { id: string; code: string; name: string }[];
};

/**
 * Create-kategori page (Admin Tenant).
 *
 * The parent golongan is chosen from the acting tenant's own groups only —
 * the server-side validation rejects any other tenant's parents
 * (fail-closed, watchpoint #30).
 */
export default function ClassificationCategoryCreate() {
    const { groups } = usePage<PageProps>().props;

    const options: ParentOption[] = groups.map((group) => ({
        id: group.id,
        label: `${group.code} — ${group.name}`,
    }));

    return (
        <ResourceForm
            formProps={AssetCategoryController.store.form()}
            codeLabel="Kode kategori"
            nameLabel="Nama kategori"
            parentSelect={{
                fieldName: 'asset_group_id',
                label: 'Golongan',
                options,
            }}
            cancelHref={categoriesIndex().url}
        />
    );
}

ClassificationCategoryCreate.layout = {
    breadcrumbs: [
        { title: 'Klasifikasi', href: '#' },
        { title: 'Kategori', href: categoriesIndex().url },
        { title: 'Tambah', href: '' },
    ],
};
