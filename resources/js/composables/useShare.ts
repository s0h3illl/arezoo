import { raiseToast } from '@/composables/useToast';

export async function shareProfile(): Promise<void> {
    await share('#');
}

async function share(path: string): Promise<void> {
    const url = new URL(path, window.location.href).href;

    try {
        if (navigator.share !== undefined) {
            await navigator.share({ url });

            raiseToast('لینک به اشتراک گذاشته شد');

            return;
        }

        await navigator.clipboard.writeText(url);

        raiseToast('لینک کپی شد');
    } catch (error) {
        if (error instanceof DOMException && error.name === 'AbortError') {
            return;
        }

        raiseToast('نشد لینک رو بفرستیم، دوباره امتحان کن', 'error');
    }
}
