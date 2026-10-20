import { useConfirm } from 'primevue/useconfirm';

/**
 * Remplace window.confirm() par le ConfirmDialog global (groupe "app" monté dans le layout Sakai).
 *
 *   const confirmAction = useConfirmAction();
 *   confirmAction({ message: 'Fermer ce ticket ?', accept: () => router.post(...) });
 *   confirmAction({ message: 'Supprimer ?', danger: true, accept: ... });
 */
export function useConfirmAction() {
    const confirm = useConfirm();

    return ({ message, header = 'Confirmation', acceptLabel = 'Confirmer', rejectLabel = 'Annuler', danger = false, icon, accept, reject } = {}) => {
        confirm.require({
            group: 'app',
            message,
            header,
            icon: icon || (danger ? 'pi pi-exclamation-triangle' : 'pi pi-question-circle'),
            rejectProps: { label: rejectLabel, severity: 'secondary', outlined: true },
            acceptProps: { label: acceptLabel, severity: danger ? 'danger' : undefined },
            accept,
            reject,
        });
    };
}
