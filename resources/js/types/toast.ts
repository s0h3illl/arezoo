export type Toast = {
    id: number;
    message: string;
    tone: ToastTone;
};

export type ToastTone = 'success' | 'error';
