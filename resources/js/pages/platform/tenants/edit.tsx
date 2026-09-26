import { Form, Head, Link, usePage } from '@inertiajs/react';
import TenantController from '@/actions/App/Http/Controllers/Platform/TenantController';
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
import { index } from '@/routes/platform/tenants';

type TenantItem = {
    id: string;
    code: string;
    name: string;
    status: 'active' | 'inactive' | 'suspended';
};

type PageProps = {
    tenant: TenantItem;
};

const STATUS_LABELS: Record<TenantItem['status'], string> = {
    active: 'Aktif',
    inactive: 'Nonaktif',
    suspended: 'Ditangguhkan',
};

/**
 * Edit-tenant page (platform admin, T01b).
 *
 * Status transitions intentionally live on the index row actions (with
 * confirmation), so this form only edits identity fields.
 */
export default function PlatformTenantEdit() {
    const { tenant } = usePage<PageProps>().props;

    return (
        <>
            <Head title={`Edit Tenant · ${tenant.code}`} />

            <div className="mx-auto max-w-xl space-y-6">
                <Heading
                    title="Edit tenant"
                    description="Perubahan kode berlaku ke semua referensi yang memakai kode ini."
                />

                <Card className="rounded-[4px] border-border-solid bg-surface-solid shadow-none">
                    <CardHeader>
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <CardTitle className="text-base font-semibold text-text-primary">
                                    Identitas tenant
                                </CardTitle>
                                <CardDescription>
                                    Kode harus tetap unik antar tenant.
                                </CardDescription>
                            </div>
                            <Badge
                                role="status"
                                variant="outline"
                                className="border-border-solid bg-surface-solid-alt text-text-secondary"
                            >
                                {STATUS_LABELS[tenant.status]}
                            </Badge>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <Form
                            {...TenantController.update.form({ tenant: tenant.id })}
                            className="space-y-5"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="space-y-2">
                                        <Label htmlFor="code">Kode tenant</Label>
                                        <Input
                                            id="code"
                                            name="code"
                                            required
                                            maxLength={64}
                                            pattern="[A-Za-z0-9_-]+"
                                            defaultValue={tenant.code}
                                            autoComplete="off"
                                        />
                                        <InputError message={errors.code} />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="name">Nama tenant</Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            required
                                            maxLength={255}
                                            defaultValue={tenant.name}
                                        />
                                        <InputError message={errors.name} />
                                    </div>

                                    <div className="flex items-center gap-3 pt-1">
                                        <Button type="submit" disabled={processing}>
                                            {processing ? 'Menyimpan…' : 'Simpan'}
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

PlatformTenantEdit.layout = {
    breadcrumbs: [
        { title: 'Platform', href: '#' },
        { title: 'Tenant', href: index().url },
        { title: 'Edit', href: '' },
    ],
};
