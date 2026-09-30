import { usePage } from '@inertiajs/react';
import AssetGroupController from '@/actions/App/Http/Controllers/Classification/AssetGroupController';
import { ResourceForm } from '@/components/classification/resource-form';
import { index as groupsIndex } from '@/routes/classifications/groups';

type PageProps = {
    group: { id: string; code: string; name: string };
};

/**
 * Edit-golongan page (Admin Tenant).
 *
 * The shared form pre-fills the code and name; the code stays editable
 * until a child level references it (ADR-0001) and a rejected edit renders
 * as a validation error under the code field.
 */
export default function ClassificationGroupEdit() {
    const { group } = usePage<PageProps>().props;

    return (
        <ResourceForm
            formProps={AssetGroupController.update.form({ group: group.id })}
            values={{ code: group.code, name: group.name }}
            codeLabel="Kode golongan"
            nameLabel="Nama golongan"
            cancelHref={groupsIndex().url}
        />
    );
}

ClassificationGroupEdit.layout = {
    breadcrumbs: [
        { title: 'Klasifikasi', href: '#' },
        { title: 'Golongan', href: groupsIndex().url },
        { title: 'Edit', href: '' },
    ],
};
