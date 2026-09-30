import { usePage } from '@inertiajs/react';
import ItemController from '@/actions/App/Http/Controllers/Classification/ItemController';
import {
    ResourceForm,
    type ParentOption,
} from '@/components/classification/resource-form';
import { index as itemsIndex } from '@/routes/classifications/items';

type PageProps = {
    item: {
        id: string;
        name: string;
        subCluster: { id: string; code: string; name: string } | null;
    };
};

/**
 * Edit-item page (Admin Tenant).
 *
 * Items carry no code column; the sub-cluster select is optional and the
 * current value is pre-selected.
 */
export default function ClassificationItemEdit() {
    const { item } = usePage<PageProps>().props;

    const subClusters = usePage<{
        subClusters?: { id: string; code: string; name: string }[];
    }>().props.subClusters;

    const options: ParentOption[] = (subClusters ?? []).map((subCluster) => ({
        id: subCluster.id,
        label: `${subCluster.code} — ${subCluster.name}`,
    }));

    return (
        <ResourceForm
            formProps={ItemController.update.form({ item: item.id })}
            values={{ name: item.name }}
            hasCode={false}
            nameLabel="Nama item"
            parentSelect={{
                fieldName: 'asset_sub_cluster_id',
                label: 'Sub kelompok',
                options,
                required: false,
                selectedId: item.subCluster?.id ?? '',
            }}
            cancelHref={itemsIndex().url}
        />
    );
}

ClassificationItemEdit.layout = {
    breadcrumbs: [
        { title: 'Klasifikasi', href: '#' },
        { title: 'Item', href: itemsIndex().url },
        { title: 'Edit', href: '' },
    ],
};
