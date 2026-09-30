import AssetGroupController from '@/actions/App/Http/Controllers/Classification/AssetGroupController';
import { ResourceForm } from '@/components/classification/resource-form';
import { index as groupsIndex } from '@/routes/classifications/groups';

/**
 * Create-golongan page (Admin Tenant).
 *
 * Groups are the top level of the chain, so no parent select is needed.
 * The shared form posts to the wayfinder action; validation errors render
 * under each field. Solid surface card per DESIGN.md data-layer rule.
 */
export default function ClassificationGroupCreate() {
    return (
        <ResourceForm
            formProps={AssetGroupController.store.form()}
            codeLabel="Kode golongan"
            nameLabel="Nama golongan"
            cancelHref={groupsIndex().url}
        />
    );
}

ClassificationGroupCreate.layout = {
    breadcrumbs: [
        { title: 'Klasifikasi', href: '#' },
        { title: 'Golongan', href: groupsIndex().url },
        { title: 'Tambah', href: '' },
    ],
};
