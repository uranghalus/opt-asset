import { usePage } from '@inertiajs/react';
import AssetCategoryController from '@/actions/App/Http/Controllers/Classification/AssetCategoryController';
import { ResourceForm } from '@/components/classification/resource-form';
import { index as categoriesIndex } from '@/routes/classifications/categories';

type PageProps = {
    category: {
        id: string;
        code: string;
        name: string;
        group: { id: string; code: string; name: string } | null;
    };
};

/**
 * Edit-kategori page (Admin Tenant).
 *
 * The parent golongan is never editable — only the code and name travel
 * here. A referenced code edit renders as a validation error under the
 * code field (ADR-0001).
 */
export default function ClassificationCategoryEdit() {
    const { category } = usePage<PageProps>().props;

    return (
        <ResourceForm
            formProps={AssetCategoryController.update.form({
                category: category.id,
            })}
            values={{ code: category.code, name: category.name }}
            codeLabel="Kode kategori"
            nameLabel="Nama kategori"
            parentSelect={{
                fieldName: 'asset_group_id',
                label: 'Golongan',
                selectedLabel: category.group
                    ? `${category.group.code} — ${category.group.name}`
                    : '—',
            }}
            cancelHref={categoriesIndex().url}
        />
    );
}

ClassificationCategoryEdit.layout = {
    breadcrumbs: [
        { title: 'Klasifikasi', href: '#' },
        { title: 'Kategori', href: categoriesIndex().url },
        { title: 'Edit', href: '' },
    ],
};
