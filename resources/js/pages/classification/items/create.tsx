import ItemController from '@/actions/App/Http/Controllers/Classification/ItemController';
import {
    ResourceForm,
    type ParentOption,
} from '@/components/classification/resource-form';
import { usePage } from '@inertiajs/react';
import { index as itemsIndex } from '@/routes/classifications/items';

type PageProps = {
    subClusters: { id: string; code: string; name: string }[];
};

/**
 * Create-item page (Admin Tenant).
 *
 * Items carry no code column (their identity is the per-tenant unique
 * name); the sub-cluster is optional — an item may stay unclassified, the
 * same semantics as auto-created imports (rules §1.3).
 */
export default function ClassificationItemCreate() {
    const { subClusters } = usePage<PageProps>().props;

    const options: ParentOption[] = subClusters.map((subCluster) => ({
        id: subCluster.id,
        label: `${subCluster.code} — ${subCluster.name}`,
    }));

    return (
        <ResourceForm
            formProps={ItemController.store.form()}
            hasCode={false}
            nameLabel="Nama item"
            parentSelect={{
                fieldName: 'asset_sub_cluster_id',
                label: 'Sub kelompok',
                options,
                required: false,
            }}
            cancelHref={itemsIndex().url}
        />
    );
}

ClassificationItemCreate.layout = {
    breadcrumbs: [
        { title: 'Klasifikasi', href: '#' },
        { title: 'Item', href: itemsIndex().url },
        { title: 'Tambah', href: '' },
    ],
};
