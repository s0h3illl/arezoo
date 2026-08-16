import type { Ref } from 'vue';
import { ref } from 'vue';

import type { Toast, ToastTone } from '@/types';

const toasts = ref<Toast[]>([]);

let lastId = 0;

export function raiseToast(message: string, tone: ToastTone = 'success'): void {
    toasts.value.push({ id: ++lastId, message, tone });
}

export function dismissToast(id: number): void {
    toasts.value = toasts.value.filter((toast) => toast.id !== id);
}

export function useToasts(): Ref<Toast[]> {
    return toasts;
}
