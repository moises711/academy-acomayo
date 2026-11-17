export type CartillaQuestion = { numero_pregunta: number; label_x?: number; y?: number; opciones?: { label: string; x: number; y: number }[] };
export type CartillaConfig = { diseno?: string; titulo?: string; instrucciones?: string; columnas?: number; questions: CartillaQuestion[] };
export function compactQuestions(numbers: number[], count: number): CartillaQuestion[] {
    return numbers.map((numero_pregunta, i) => {
        const left = 380 + Math.floor(i / 20) * 200;
        const y = 200 + (i % 20) * 32;
        return { numero_pregunta, label_x: left + 12, y, opciones: Array.from({length: count}, (_, j) => ({label: String.fromCharCode(65 + j), x: left + 55 + j * (count === 4 ? 38 : 30), y})) };
    });
}
export default function OmrCartilla({ id, config, selected, onMark }: { id?: string; config: CartillaConfig; selected?: Record<number, string>; onMark?: (number: number, answer: string) => void }) {
    const groups = Array.from({length: Math.ceil(config.questions.length / 20)}, (_, i) => config.questions.slice(i * 20, i * 20 + 20));
    const instructions = (config.instrucciones ?? 'Llena un círculo por cada dígito del DNI. No dejes espacios vacíos.').match(/.{1,43}(?:\s|$)|.{1,43}/g) ?? [];
    const ink = '#B91C1C';
    return <svg id={id} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 900" width={onMark ? '1440' : '297mm'} height={onMark ? '1080' : '210mm'} className={onMark ? 'block max-w-none bg-white' : 'block h-auto w-full bg-white'} role="group" aria-label={onMark ? 'Cartilla para marcar respuestas correctas' : 'Cartilla imprimible con DNI y respuestas'}>
        <rect width="1200" height="900" fill="white" />
        {[[6,6],[1182,6],[6,882],[1182,882]].map(([x,y]) => <rect key={`${x}-${y}`} x={x} y={y} width="12" height="12" fill="black" />)}
        <g fontFamily="Arial, sans-serif" fill="#172033" stroke={ink} strokeWidth="1">
            <text x="20" y="47" stroke="none" fontSize="23" fontWeight="bold" textLength={(config.titulo?.length ?? 0) > 70 ? 1140 : undefined} lengthAdjust="spacingAndGlyphs">{config.titulo ?? 'CARTILLA DE RESPUESTAS'}</text>
            <text x="20" y="83" stroke="none" fontSize="16">Nombres: ___________________________________    Fecha: ______________</text>
            <rect x="20" y="120" width="340" height="530" fill="none" />
            <path d="M20 160H360 M20 195H360" fill="none" />
            <text x="190" y="146" textAnchor="middle" stroke="none" fontSize="20">NÚMERO DE DNI</text>
            {Array.from({length:8},(_,column) => <g key={column}>
                {column > 0 && <path d={`M${20 + column * 42.5} 160V650`} fill="none" />}
                {Array.from({length:10},(_,digit) => <g key={digit}><circle cx={41.25 + column * 42.5} cy={210 + digit * 43} r="12" fill="white" /><text x={41.25 + column * 42.5} y={215 + digit * 43} stroke="none" textAnchor="middle" fontSize="15">{digit}</text></g>)}
            </g>)}
            {instructions.map((line,i) => <text key={i} x="190" y={680 + i * 21} stroke="none" textAnchor="middle" fontSize="15">{line.trim()}</text>)}
            <text x="20" y="810" fontSize="18" stroke="none">GRUPO:</text>
            {['A','B','C','D'].map((group,i) => <g key={group}><circle cx={140 + i * 50} cy="804" r="15" fill="white" /><text x={140 + i * 50} y="810" textAnchor="middle" stroke="none" fontSize="17">{group}</text></g>)}
            {groups.map((group, block) => {
                const left = 380 + block * 200;
                return <g key={block}><rect x={left} y="120" width="192" height="720" fill="none" /><path d={`M${left} 176H${left + 192}`} fill="none" /><text x={left + 96} y="143" textAnchor="middle" stroke="none" fontSize="18">PREGUNTAS</text><text x={left + 96} y="166" textAnchor="middle" stroke="none" fontSize="17">{group[0].numero_pregunta}–{group[group.length - 1].numero_pregunta}</text>
                    {group.map(question => <g key={question.numero_pregunta}><text x={question.label_x} y={(question.y ?? 0) + 5} stroke="none" fontSize="15">{question.numero_pregunta}</text>
                        {question.opciones?.map(option => {
                            const marked = selected?.[question.numero_pregunta] === option.label;
                            return <g key={option.label} role={onMark ? 'button' : undefined} aria-label={onMark ? `Pregunta ${question.numero_pregunta}, alternativa ${option.label}` : undefined} aria-pressed={onMark ? marked : undefined} tabIndex={onMark ? 0 : undefined} onClick={onMark ? () => onMark(question.numero_pregunta, option.label) : undefined} onKeyDown={onMark ? e => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); onMark(question.numero_pregunta, option.label); } } : undefined} className={onMark ? 'cursor-pointer focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-600' : undefined}>
                                <rect x={option.x - 18} y={option.y - 16} width="36" height="32" fill="transparent" stroke="none" />
                                <circle cx={option.x} cy={option.y} r="12" fill={marked ? 'black' : 'white'} stroke={marked ? 'black' : ink} />
                                <text x={option.x} y={option.y + 5} stroke="none" fill={marked ? 'white' : '#172033'} textAnchor="middle" fontSize="15">{option.label}</text>
                            </g>;
                        })}
                    </g>)}
                </g>;
            })}
        </g>
    </svg>;
}
