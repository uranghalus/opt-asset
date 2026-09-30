import type { ComponentProps } from 'react';
import { Form, usePage } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

/**
 * One selectable parent of a child chain level.
 */
export type ParentOption = {
    /** ULID of the parent record (the acting tenant's own). */
    id: string;
    /** Human label shown in the select, e.g. "A — Golongan A". */
    label: string;
};

/**
 * Configuration for the parent select of a child chain level. On create
 * pages the select renders from `options`; on edit pages the parent is
 * never editable and only `selectedLabel` is shown.
 */
export type ParentSelectConfig = {
    /** Form field name, e.g. "asset_group_id". */
    fieldName: string;
    /** Human label for the field. */
    label: string;
    /** Selectable parents (create pages, or editable parents like items). */
    options?: ParentOption[];
    /** Pre-selected parent id when the select renders (defaults to none). */
    selectedId?: string;
    /** Pre-selected parent label (read-only edit pages). */
    selectedLabel?: string;
    /** Whether a parent must be chosen (chain levels yes, items no). */
    required?: boolean;
};

/**
 * Props of the shared classification form.
 */
export type ResourceFormProps = {
    /** Wayfinder form definition — store.form() on create, update.form({...}) on edit. */
    formProps: Omit<ComponentProps<typeof Form>, 'children'>;
    /** Existing values on edit pages. */
    values?: { code?: string; name?: string };
    /** Whether the level has a code column (items do not). */
    hasCode?: boolean;
    /** Human label for the code field. */
    codeLabel?: string;
    /** Human label for the name field. */
    nameLabel?: string;
    /** Parent select configuration (child levels only). */
    parentSelect?: ParentSelectConfig;
    /** Cancel link target, usually the level's index page. */
    cancelHref: string;
};

/**
 * Shared create/edit form for every classification chain level and items.
 *
 * Solid surface card per DESIGN.md data-layer rule (forms sit on
 * surface-solid without blur); validation errors render under each field.
 * Submits through the wayfinder action passed as `formProps` — the page
 * spreads `store.form()` on create and `update.form({...})` on edit.
 */
export function ResourceForm({
    formProps,
    values,
    hasCode = true,
    codeLabel = 'Kode',
    nameLabel = 'Nama',
    parentSelect,
    cancelHref,
}: ResourceFormProps) {
    const { errors } = usePage().props;

    return (
        <Card className="rounded-[4px] border-border-solid bg-surface-solid shadow-none">
            <CardContent>
                <Form {...formProps} className="space-y-5">
                    {({ processing, errors: formErrors }) => {
                        const fieldErrors = formErrors as Record<
                            string,
                            string | undefined
                        >;

                        return (
                            <>
                                {parentSelect && !parentSelect.options && (
                                    <div className="space-y-2">
                                        <Label>{parentSelect.label}</Label>
                                        <p className="text-sm text-text-primary">
                                            {parentSelect.selectedLabel}
                                        </p>
                                    </div>
                                )}

                                {parentSelect && parentSelect.options && (
                                    <div className="space-y-2">
                                        <Label htmlFor={parentSelect.fieldName}>
                                            {parentSelect.label}
                                        </Label>
                                        <select
                                            id={parentSelect.fieldName}
                                            name={parentSelect.fieldName}
                                            required={
                                                parentSelect.required ?? true
                                            }
                                            defaultValue={
                                                parentSelect.selectedId ?? ''
                                            }
                                            className="h-9 w-full rounded-md border border-input bg-transparent px-2.5 text-sm text-text-primary shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                                        >
                                            {!(
                                                parentSelect.required ?? true
                                            ) && (
                                                <option value="">
                                                    Tanpa{' '}
                                                    {parentSelect.label.toLowerCase()}
                                                </option>
                                            )}
                                            {parentSelect.options.map(
                                                (option) => (
                                                    <option
                                                        key={option.id}
                                                        value={option.id}
                                                    >
                                                        {option.label}
                                                    </option>
                                                ),
                                            )}
                                        </select>
                                        <InputError
                                            message={
                                                fieldErrors[
                                                    parentSelect.fieldName
                                                ] ??
                                                errors?.[parentSelect.fieldName]
                                            }
                                        />
                                    </div>
                                )}

                                {hasCode && (
                                    <div className="space-y-2">
                                        <Label htmlFor="code">
                                            {codeLabel}
                                        </Label>
                                        <Input
                                            id="code"
                                            name="code"
                                            required
                                            maxLength={255}
                                            defaultValue={values?.code}
                                            autoComplete="off"
                                        />
                                        <InputError
                                            message={
                                                fieldErrors.code ?? errors?.code
                                            }
                                        />
                                    </div>
                                )}

                                <div className="space-y-2">
                                    <Label htmlFor="name">{nameLabel}</Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        required
                                        maxLength={255}
                                        defaultValue={values?.name}
                                    />
                                    <InputError
                                        message={
                                            fieldErrors.name ?? errors?.name
                                        }
                                    />
                                </div>

                                <div className="flex items-center gap-3 pt-1">
                                    <Button type="submit" disabled={processing}>
                                        {processing ? 'Menyimpan…' : 'Simpan'}
                                    </Button>
                                    <Button variant="ghost" asChild>
                                        <a href={cancelHref}>Batal</a>
                                    </Button>
                                </div>
                            </>
                        );
                    }}
                </Form>
            </CardContent>
        </Card>
    );
}
