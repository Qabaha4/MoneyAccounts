import { InertiaLinkProps } from '@inertiajs/vue3';
import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function urlIsActive(
    urlToCheck: NonNullable<InertiaLinkProps['href']>,
    currentUrl: string,
) {
    const target = toUrl(urlToCheck);
    if (target === currentUrl) return true;
    if (!target.endsWith('/')) return currentUrl.startsWith(target + '/') || currentUrl.startsWith(target + '?');
    return currentUrl.startsWith(target);
}

export function toUrl(href: NonNullable<InertiaLinkProps['href']>) {
    return typeof href === 'string' ? href : href?.url;
}
