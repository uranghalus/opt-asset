import { usePage } from '@inertiajs/react';
import {
    ResourceIndex,
    type ResourceIndexRow,
} from '@/components/classification/resource-index';
import {
    create as groupsCreate,
    destroy as groupsDestroy,
    edit as groupsEdit,
    index as groupsIndex,
} from '@/routes/classifications/groups';

type PageProps = {
    groups: {
        data: { id: string; code: string; name: string }[];
        total: number;
        from: number | null;
        current_page: number;
        last_page: number;
    };
};

/**
 * Asset group (golongan) index — the top level of the classification chain.
 *
 * Delegates to the shared classification index, which renders the solid
 * table, row actions, pagination, and the delete dialog (ADR-0001 surfaces
 * there as a validation error when a referenced level cannot be deleted).
 */
export default function ClassificationGroupIndex() {
    const { groups } = usePage<PageProps>().props;

    const rows: ResourceIndexRow[] = groups.data.map((group) => ({
        id: group.id,
        code: group.code,
        name: group.name,
        parentLabel: null,
    }));

    return (
        <ResourceIndex
            title="Golongan"
            description="Level teratas rantai klasifikasi — kode di sini membentuk awal kode aset."
            rows={rows}
            total={groups.total}
            from={groups.from}
            currentPage={groups.current_page}
            lastPage={groups.last_page}
            pageHref={(page) => groupsIndex({ query: { page } }).url}
            onlyKeys={['groups']}
            createHref={groupsCreate().url}
            createLabel="Tambah golongan"
            editHref={(id) => groupsEdit({ group: id }).url}
            deleteHref={(id) => groupsDestroy({ group: id }).url}
            emptyState={{
                title: 'Belum ada golongan',
                description:
                    'Mulai dengan membuat golongan pertama — kategori, kelompok, dan sub kelompok menumpuk di bawahnya.',
            }}
            labels={{
                singular: 'golongan',
                code: 'Kode',
                parent: '',
            }}
        />
    );
}

ClassificationGroupIndex.layout = {
    breadcrumbs: [
        { title: 'Klasifikasi', href: '#' },
        { title: 'Golongan', href: groupsIndex().url },
    ],
};
