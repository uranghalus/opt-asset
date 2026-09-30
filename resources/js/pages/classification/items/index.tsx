import { usePage } from '@inertiajs/react';
import {
    ResourceIndex,
    type ResourceIndexRow,
} from '@/components/classification/resource-index';
import {
    create as itemsCreate,
    destroy as itemsDestroy,
    edit as itemsEdit,
    index as itemsIndex,
} from '@/routes/classifications/items';

type PageProps = {
    items: {
        data: {
            id: string;
            name: string;
            subCluster: { id: string; code: string; name: string } | null;
        }[];
        total: number;
        from: number | null;
        current_page: number;
        last_page: number;
    };
};

/**
 * Item index — the leaf level of the classification chain.
 *
 * Items carry no code column (their identity is the per-tenant unique
 * name); the parent column shows the owning sub kelompok, or the
 * unclassified state for auto-created imports (rules §1.3).
 */
export default function ClassificationItemIndex() {
    const { items } = usePage<PageProps>().props;

    const rows: ResourceIndexRow[] = items.data.map((item) => ({
        id: item.id,
        code: null,
        name: item.name,
        parentLabel: item.subCluster
            ? `${item.subCluster.code} — ${item.subCluster.name}`
            : 'Tanpa sub kelompok',
    }));

    return (
        <ResourceIndex
            title="Item"
            description="Level terbawah rantai klasifikasi — item aset terhubung ke satu sub kelompok atau tetap tanpa klasifikasi."
            rows={rows}
            total={items.total}
            from={items.from}
            currentPage={items.current_page}
            lastPage={items.last_page}
            pageHref={(page) => itemsIndex({ query: { page } }).url}
            onlyKeys={['items']}
            createHref={itemsCreate().url}
            createLabel="Tambah item"
            editHref={(id) => itemsEdit({ item: id }).url}
            deleteHref={(id) => itemsDestroy({ item: id }).url}
            emptyState={{
                title: 'Belum ada item',
                description:
                    'Buat item di bawah sub kelompok, atau biarkan tanpa klasifikasi — import Excel juga bisa membuatnya otomatis.',
            }}
            labels={{
                singular: 'item',
                code: 'Kode',
                parent: 'Sub Kelompok',
            }}
        />
    );
}

ClassificationItemIndex.layout = {
    breadcrumbs: [
        { title: 'Klasifikasi', href: '#' },
        { title: 'Item', href: itemsIndex().url },
    ],
};
