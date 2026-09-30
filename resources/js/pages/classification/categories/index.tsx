import { usePage } from '@inertiajs/react';
import {
    ResourceIndex,
    type ResourceIndexRow,
} from '@/components/classification/resource-index';
import {
    create as categoriesCreate,
    destroy as categoriesDestroy,
    edit as categoriesEdit,
    index as categoriesIndex,
} from '@/routes/classifications/categories';

type PageProps = {
    categories: {
        data: {
            id: string;
            code: string;
            name: string;
            group: { id: string; code: string; name: string } | null;
        }[];
        total: number;
        from: number | null;
        current_page: number;
        last_page: number;
    };
};

/**
 * Asset category (kategori) index — the second level of the chain.
 *
 * Delegates to the shared classification index; the parent column shows the
 * owning golongan per row (eager-loaded server-side, no client fetch).
 */
export default function ClassificationCategoryIndex() {
    const { categories } = usePage<PageProps>().props;

    const rows: ResourceIndexRow[] = categories.data.map((category) => ({
        id: category.id,
        code: category.code,
        name: category.name,
        parentLabel: category.group
            ? `${category.group.code} — ${category.group.name}`
            : null,
    }));

    return (
        <ResourceIndex
            title="Kategori"
            description="Level kedua rantai klasifikasi — setiap kategori menumpuk di bawah satu golongan."
            rows={rows}
            total={categories.total}
            from={categories.from}
            currentPage={categories.current_page}
            lastPage={categories.last_page}
            pageHref={(page) => categoriesIndex({ query: { page } }).url}
            onlyKeys={['categories']}
            createHref={categoriesCreate().url}
            createLabel="Tambah kategori"
            editHref={(id) => categoriesEdit({ category: id }).url}
            deleteHref={(id) => categoriesDestroy({ category: id }).url}
            emptyState={{
                title: 'Belum ada kategori',
                description:
                    'Buat kategori di bawah golongan — kelompok dan sub kelompok menumpuk di bawahnya.',
            }}
            labels={{
                singular: 'kategori',
                code: 'Kode',
                parent: 'Golongan',
            }}
        />
    );
}

ClassificationCategoryIndex.layout = {
    breadcrumbs: [
        { title: 'Klasifikasi', href: '#' },
        { title: 'Kategori', href: categoriesIndex().url },
    ],
};
