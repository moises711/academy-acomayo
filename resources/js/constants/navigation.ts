import {
    BarChart3,
    BookOpen,
    CalendarRange,
    ClipboardList,
    Clock,
    FileText,
    ScanLine,
    Home,
    History,
    GraduationCap,
    Globe2,
    MessagesSquare,
    Megaphone,
    QrCode,
    Settings,
    Users,
    Wallet,
} from 'lucide-react';

type NavigationItem = {
    title: string;
    url: string;
    icon: typeof Home;
    permission: string;
    permissionAny?: string[];
};

type NavigationGroup = { title: string; icon: typeof Home; items: NavigationItem[] };

export const navigationGroups: NavigationGroup[] = [
    {
        title: 'Inicio',
        icon: Home,
        items: [
            { title: 'Dashboard', url: '/dashboard', icon: Home, permission: 'dashboard.ver' },
        ],
    },
    {
        title: 'Portal',
        icon: GraduationCap,
        items: [
            { title: 'Mi portal', url: '/mi', icon: GraduationCap, permission: 'portal.mis_datos' },
        ],
    },
    {
        title: 'Académico',
        icon: BookOpen,
        items: [
            { title: 'Estudiantes', url: '/estudiantes', icon: Users, permission: 'estudiantes.ver' },
            { title: 'Diseño de carnets', url: '/carnets/plantillas', icon: QrCode, permission: 'carnets.disenar' },
            { title: 'Matrículas', url: '/matriculas', icon: ClipboardList, permission: 'matriculas.ver' },
            { title: 'Ciclos', url: '/ciclos', icon: CalendarRange, permission: 'academico.gestionar' },
            { title: 'Catálogos', url: '/catalogos', icon: Settings, permission: 'academico.gestionar' },
            { title: 'Cursos', url: '/cursos', icon: BookOpen, permission: 'academico.gestionar' },
            { title: 'Docentes', url: '/docentes', icon: Users, permission: 'docentes.gestionar' },
            { title: 'Horarios', url: '/horarios', icon: Clock, permission: 'academico.gestionar' },
            { title: 'Asistencias', url: '/asistencias', icon: QrCode, permission: 'asistencia.registrar_qr', permissionAny: ['asistencia.ver', 'asistencia.registrar_manual'] },
        ],
    },
    {
        title: 'Evaluación',
        icon: BarChart3,
        items: [
            { title: 'Simulacros y resultados', url: '/simulacros', icon: FileText, permission: 'evaluaciones.gestionar' },
            { title: 'Notas', url: '/notas', icon: BookOpen, permission: 'notas.gestionar' },
            { title: 'Lectura OMR', url: '/omr', icon: ScanLine, permission: 'omr.gestionar' },
        ],
    },
    {
        title: 'Comunicación',
        icon: MessagesSquare,
        items: [
            { title: 'Anuncios y materiales', url: '/comunicacion', icon: Megaphone, permission: 'comunicacion.gestionar' },
            { title: 'Consultas web', url: '/consultas', icon: MessagesSquare, permission: 'consultas.gestionar' },
        ],
    },
    {
        title: 'Sitio web',
        icon: Globe2,
        items: [
            { title: 'Contenido del sitio web', url: '/sitio-web', icon: Globe2, permission: 'usuarios.gestionar' },
        ],
    },
    {
        title: 'Administración',
        icon: Settings,
        items: [
            { title: 'Historial de ciclos', url: '/historial-ciclos', icon: History, permission: 'ciclos.historial' },
            { title: 'Pagos', url: '/pagos', icon: Wallet, permission: 'finanzas.ver' },
            { title: 'Pagos a docentes', url: '/pagos-docentes', icon: Wallet, permission: 'pagos_docentes.gestionar' },
            { title: 'Reportes', url: '/reportes', icon: BarChart3, permission: 'reportes.ver' },
            { title: 'Auditoría', url: '/auditoria', icon: History, permission: 'auditoria.ver' },
            { title: 'Usuarios', url: '/usuarios', icon: Users, permission: 'usuarios.gestionar' },
            { title: 'Configuración', url: '/configuracion', icon: Settings, permission: 'usuarios.gestionar' },
        ],
    },
];
