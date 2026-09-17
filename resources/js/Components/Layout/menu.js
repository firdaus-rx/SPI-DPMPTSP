import {
    LayoutDashboard,
    ClipboardList,
    Plus,
    FileUp,
    Scale,
} from '@lucide/vue';

/**
 * SINGLE SOURCE OF TRUTH untuk seluruh navigasi aplikasi.
 *
 * Setiap item mendukung strategi aktif berikut:
 * - exact  : aktif hanya saat path persis sama
 * - prefix : aktif saat path sama / diawali href + '/'
 * - match  : regex kustom (Daftar Pengawasan agar /create & /import tidak ikut aktif)
 */
export const NAV_SECTIONS = [
    {
        label: 'Utama',
        items: [
            {
                href: '/dashboard',
                label: 'Dashboard',
                icon: LayoutDashboard,
                exact: true,
            },
        ],
    },
    {
        label: 'Daftar List Sanksi Pencabutan',
        items: [
            {
                href: '/pengawasan',
                label: 'Daftar Sanksi Pencabutan',
                icon: ClipboardList,
                match: /^\/pengawasan(\/\d+(\/edit)?)?$/,
            },
            {
                href: '/pengawasan/create',
                label: 'Tambah Sanksi Pencabutan',
                icon: Plus,
                exact: true,
            },
            {
                href: '/pengawasan/import',
                label: 'Impor Dokumen',
                icon: FileUp,
                prefix: true,
                badge: 'OCR',
            },
        ],
    },
    {
        label: 'Sanksi Administratif Usulan Pencabutan Perizinan Berusaha',
        items: [
            {
                href: '/sanksi-administratif',
                label: 'Daftar Usulan Pencabutan',
                icon: Scale,
                match: /^\/sanksi-administratif(\/\d+(\/edit)?)?$/,
            },
            {
                href: '/sanksi-administratif/create',
                label: 'Tambah Usulan',
                icon: Plus,
                exact: true,
            },
            {
                href: '/sanksi-administratif/import',
                label: 'Impor Dokumen',
                icon: FileUp,
                prefix: true,
                badge: 'OCR',
            },
        ],
    },
];

/** Tautan cepat pada dropdown pengguna di navbar. */
export const USER_MENU = NAV_SECTIONS.flatMap((section) => section.items);

/** Section aktif untuk URL saat ini. */
export function activeNavSection(url) {
    return NAV_SECTIONS.find((section) =>
        section.items.some((item) => isActiveUrl(url, item))
    );
}

/** Path aktif tanpa query string. */
export function activePath(url) {
    return (url || '/').split('?')[0];
}

/** Menentukan apakah sebuah item menu sedang aktif. */
export function isActiveUrl(url, item) {
    const path = activePath(url);
    if (item.exact) return path === item.href;
    if (item.match) return item.match.test(path);
    if (item.prefix) return path === item.href || path.startsWith(item.href + '/');
    return path.startsWith(item.href);
}

/** Breadcrumb diturunkan otomatis dari URL aktif. */
export function breadcrumbs(url) {
    const path = activePath(url);
    const crumbs = [{ label: 'Dashboard', href: '/dashboard' }];

    if (path === '/dashboard') return crumbs;

    if (path.startsWith('/pengawasan')) {
        crumbs.push({ label: 'Daftar List Sanksi Pencabutan', href: '/pengawasan' });

        if (path === '/pengawasan/create') {
            crumbs.push({ label: 'Tambah Sanksi Pencabutan' });
        } else if (path.startsWith('/pengawasan/import')) {
            crumbs.push({ label: 'Impor Dokumen' });
        } else {
            const idMatch = path.match(/^\/pengawasan\/(\d+)(\/edit)?$/);
            if (idMatch) {
                crumbs.push({ label: 'Detail', href: `/pengawasan/${idMatch[1]}` });
                if (idMatch[2]) crumbs.push({ label: 'Edit' });
            }
        }
        return crumbs;
    }

    if (path.startsWith('/sanksi-administratif')) {
        crumbs.push({ label: 'Sanksi Administratif Usulan Pencabutan Perizinan Berusaha', href: '/sanksi-administratif' });

        if (path === '/sanksi-administratif/create') {
            crumbs.push({ label: 'Tambah Usulan' });
        } else if (path.startsWith('/sanksi-administratif/import')) {
            crumbs.push({ label: 'Impor Dokumen' });
        } else {
            const idMatch = path.match(/^\/sanksi-administratif\/(\d+)(\/edit)?$/);
            if (idMatch) {
                crumbs.push({ label: 'Detail', href: `/sanksi-administratif/${idMatch[1]}` });
                if (idMatch[2]) crumbs.push({ label: 'Edit' });
            }
        }
        return crumbs;
    }

    return crumbs;
}
