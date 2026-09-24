import { Link } from "@inertiajs/react";
import {
    ArrowRightLeft,
    BookOpen,
    Building2,
    FolderGit2,
    History,
    LayoutGrid,
    List,
    ScanLine,
    ShieldCheck,
    Tags,
    Trash2,
    TrendingDown,
    Users,
} from "lucide-react";
import AppLogo from "@/components/app-logo";
import { NavFooter } from "@/components/nav-footer";
import { NavMain } from "@/components/nav-main";
import { NavUser } from "@/components/nav-user";
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from "@/components/ui/sidebar";
import { dashboard } from "@/routes";
import type { NavItem } from "@/types";

const navGroups = [
    {
        title: "Operasional",
        items: [
            { title: "Dashboard", href: dashboard(), icon: LayoutGrid },
            { title: "Daftar Aset", href: "#", icon: List },
            { title: "Scan Barcode", href: "#", icon: ScanLine },
            { title: "Mutasi", href: "#", icon: ArrowRightLeft },
            { title: "Disposal", href: "#", icon: Trash2 },
        ],
    },
    {
        title: "Laporan",
        items: [
            { title: "Riwayat & Audit", href: "#", icon: History },
            { title: "Penyusutan", href: "#", icon: TrendingDown },
        ],
    },
    {
        title: "Administrasi",
        items: [
            { title: "Klasifikasi", href: "#", icon: Tags },
            { title: "Setup SSO", href: "#", icon: ShieldCheck },
            { title: "RBAC", href: "#", icon: Users },
            { title: "Tenant Provisioning", href: "#", icon: Building2 },
        ],
    },
];

export function AppSidebar() {
    return (
        <Sidebar
            collapsible="icon"
            variant="inset"
            className="bg-sidebar backdrop-blur-[20px] border-r border-sidebar-border shadow-light dark:shadow-dark"
        >
            <SidebarHeader>
                <div className="flex justify-center py-4">
                    <AppLogo />
                </div>
            </SidebarHeader>

            <SidebarContent className="gap-4">
                {navGroups.map((group) => (
                    <NavMain
                        key={group.title}
                        title={group.title}
                        items={group.items}
                    />
                ))}
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
