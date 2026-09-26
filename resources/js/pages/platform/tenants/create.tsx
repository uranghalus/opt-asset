import { Form, Head, Link, usePage } from '@inertiajs/react';
import TenantController from '@/actions/App/Http/Controllers/Platform/TenantController';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/platform/tenants';

/**
 * Create-tenant page (platform admin, T01b).
 *
 * Form component posts to the wayfinder action; validation errors render
 * under each field. Solid surface card per DESIGN.md data-layer rule.
 */
export default function PlatformTenantCreate() {
    const { errors } = usePage().props;

    return (
        <>
            <Head title="Tambah Tenant" />

            <div className="mx-auto max-w-xl space-y-6">
                <Heading
                    title="Tambah tenant"
                    description="Tenant baru langsung aktif dan bisa dipakai oleh pengguna SSO-nya."
                />

                <Card className="rounded-[4px] border-border-solid bg-surface-solid shadow-none">
                    <CardHeader>
                        <CardTitle className="text-base font-semibold text-text-primary">
                            Identitas tenant
                        </CardTitle>
                        <CardDescription>
                            Kode tenant dipakai dalam URL dan harus unik.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Form
                            {...TenantController.store.form()}
                            className="space-y-5"
                        >
                            {({ processing, errors: formErrors }) => (
                                <>
                                    <div className="space-y-2">
                                        <Label htmlFor="code">Kode tenant</Label>
                                        <Input
                                            id="code"
                                            name="code"
                                            required
                                            maxLength={64}
                                            pattern="[A-Za-z0-9_-]+"
                                            placeholder="mis. HO-2026"
                                            autoComplete="off"
                                        />
                                        <p className="text-[12px] text-text-secondary">
                                            Huruf, angka, tanda hubung, atau garis bawah.
                                        </p>
                                        <InputError message={formErrors.code ?? errors?.code} />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="name">Nama tenant</Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            required
                                            maxLength={255}
                                            placeholder="mis. PT Head Office"
                                        />
                                        <InputError message={formErrors.name ?? errors?.name} />
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

PlatformTenantCreate.layout = {
    breadcrumbs: [
        { title: 'Platform', href: '#' },
        { title: 'Tenant', href: index().url },
        { title: 'Tambah', href: '' },
    ],
};
