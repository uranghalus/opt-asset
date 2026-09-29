import { Form, Head, Link, usePage } from '@inertiajs/react';
import BusinessUnitController from '@/actions/App/Http/Controllers/Platform/BusinessUnitController';
import InputError from '@/components/input-error';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/platform/business-units';

type BusinessUnitItem = {
    id: string;
    code: string;
    name: string;
    status: 'active' | 'inactive' | 'suspended';
};

type PageProps = {
    businessUnit: BusinessUnitItem;
};

const STATUS_LABELS: Record<BusinessUnitItem['status'], string> = {
    active: 'Aktif',
    inactive: 'Nonaktif',
    suspended: 'Ditangguhkan',
};

/**
 * Edit-business-unit page (platform admin).
 *
 * Status transitions intentionally live on the index row actions (with
 * confirmation), so this form only edits identity fields.
 */
export default function PlatformBusinessUnitEdit() {
    const { businessUnit } = usePage<PageProps>().props;

    return (
        <>
            <Head title={`Edit Unit Usaha · ${businessUnit.code}`} />

            <div className="mx-auto max-w-xl space-y-6">
                <Heading
                    title="Edit unit usaha"
                    description="Perubahan kode berlaku ke semua referensi yang memakai kode ini."
                />

                <Card className="rounded-[4px] border-border-solid bg-surface-solid shadow-none">
                    <CardHeader>
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <CardTitle className="text-base font-semibold text-text-primary">
                                    Identitas unit usaha
                                </CardTitle>
                                <CardDescription>
                                    Kode harus tetap unik antar unit usaha.
                                </CardDescription>
                            </div>
                            <Badge
                                role="status"
                                variant="outline"
                                className="border-border-solid bg-surface-solid-alt text-text-secondary"
                            >
                                {STATUS_LABELS[businessUnit.status]}
                            </Badge>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <Form
                            {...BusinessUnitController.update.form({
                                tenant: businessUnit.id,
                            })}
                            className="space-y-5"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="space-y-2">
                                        <Label htmlFor="code">
                                            Kode unit usaha
                                        </Label>
                                        <Input
                                            id="code"
                                            name="code"
                                            required
                                            maxLength={64}
                                            pattern="[A-Za-z0-9_-]+"
                                            defaultValue={businessUnit.code}
                                            autoComplete="off"
                                        />
                                        <InputError message={errors.code} />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="name">
                                            Nama unit usaha
                                        </Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            required
                                            maxLength={255}
                                            defaultValue={businessUnit.name}
                                        />
                                        <InputError message={errors.name} />
                                    </div>

                                    <div className="flex items-center gap-3 pt-1">
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                        >
                                            {processing
                                                ? 'Menyimpan…'
                                                : 'Simpan'}
                                        </Button>
                                        <Button variant="ghost" asChild>
                                            <Link href={index()}>Batal</Link>
                                        </Button>
                                    </div>
                                </>
                            )}
                        </Form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

PlatformBusinessUnitEdit.layout = {
    breadcrumbs: [
        { title: 'Platform', href: '#' },
        { title: 'Unit Usaha', href: index().url },
        { title: 'Edit', href: '' },
    ],
};
