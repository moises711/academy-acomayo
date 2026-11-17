import { ReactNode } from 'react';
import Modal from '@/Components/Modal';

export default function FormModal({
    show,
    title,
    onClose,
    children,
}: {
    show: boolean;
    title: string;
    onClose: () => void;
    children: ReactNode;
}) {
    return (
        <Modal show={show} onClose={onClose} maxWidth="lg">
            <div className="p-4 sm:p-6">
                <h3 className="text-lg font-semibold text-gray-900">{title}</h3>
                <div className="mt-4">{children}</div>
            </div>
        </Modal>
    );
}
