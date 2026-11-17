export type OmrAnswer = { numero_pregunta: number; marcada: string | null; estado: 'valida' | 'blanco' | 'multiple' | 'dudosa' };
export type OmrReading = { dni: string | null; grupo: string | null; respuestas: OmrAnswer[]; confianza: number };
export type OmrLayout = {
    diseno?: string; page: { width: number; height: number }; alignment_marks: number[][]; alignment_size: number;
    bubble_radius: number;
    dni: { columnas: number; x: number; y: number; paso_x: number; paso_y: number; radio: number };
    grupo: { x: number; y: number; paso_x: number; radio: number };
    questions: { numero_pregunta: number; opciones: { label: string; x: number; y: number }[] }[];
};
type Point = { x: number; y: number };
export type Bitmap = { width: number; height: number; data: Uint8ClampedArray };
function black(bitmap: Bitmap, x: number, y: number): boolean {
    x = Math.round(x); y = Math.round(y);
    if (x < 0 || y < 0 || x >= bitmap.width || y >= bitmap.height) return false;
    const i = (Math.round(y) * bitmap.width + Math.round(x)) * 4;
    return Math.max(bitmap.data[i], bitmap.data[i + 1], bitmap.data[i + 2]) < 100;
}
function markers(bitmap: Bitmap): Point[] {
    const { width, height } = bitmap;
    const result: Point[] = [];
    // Buscar cuadrados sólidos en las cuatro esquinas; no utilizar burbujas o texto como anclas.
    for (const [right, bottom] of [[false,false],[true,false],[false,true],[true,true]]) {
        const x0 = right ? Math.floor(width * .8) : 0, x1 = right ? width : Math.ceil(width * .2);
        const y0 = bottom ? Math.floor(height * .8) : 0, y1 = bottom ? height : Math.ceil(height * .2);
        const visited = new Uint8Array((x1 - x0) * (y1 - y0));
        const candidates: { point: Point; distance: number }[] = [];
        for (let y = y0; y < y1; y++) for (let x = x0; x < x1; x++) {
            const index = (y - y0) * (x1 - x0) + x - x0;
            if (visited[index] || !black(bitmap,x,y)) continue;
            const stack = [[x,y]];
            visited[index] = 1;
            let count = 0, minX = x, maxX = x, minY = y, maxY = y;
            while (stack.length) {
                const [px,py] = stack.pop()!;
                count++; minX = Math.min(minX,px); maxX = Math.max(maxX,px); minY = Math.min(minY,py); maxY = Math.max(maxY,py);
                for (const [nx,ny] of [[px-1,py],[px+1,py],[px,py-1],[px,py+1]]) {
                    if (nx<x0 || nx>=x1 || ny<y0 || ny>=y1) continue;
                    const ni = (ny-y0)*(x1-x0)+nx-x0;
                    if (!visited[ni] && black(bitmap,nx,ny)) { visited[ni]=1; stack.push([nx,ny]); }
                }
            }
            const w=maxX-minX+1,h=maxY-minY+1;
            if (w<6 || h<6 || w/h<.75 || w/h>1.33 || count/(w*h)<.86 || w>width*.045 || h>height*.045) continue;
            const point={x:(minX+maxX)/2,y:(minY+maxY)/2};
            const distance=Math.hypot(point.x-(right?width:0),point.y-(bottom?height:0));
            candidates.push({point,distance});
        }
        candidates.sort((a,b)=>a.distance-b.distance);
        if (!candidates.length || (candidates[1] && candidates[1].distance-candidates[0].distance<Math.min(width,height)*.015)) throw new Error('No se distinguen las cuatro marcas negras de las esquinas. Fotografía la hoja completa, recta y bien iluminada.');
        result.push(candidates[0].point);
    }
    return result;
}
function project(source: Point[], destination: Point[]): (x: number,y: number)=>Point {
    const matrix: number[][]=[];
    source.forEach((p,i)=> { const q=destination[i]; matrix.push([p.x,p.y,1,0,0,0,-q.x*p.x,-q.x*p.y,q.x]); matrix.push([0,0,0,p.x,p.y,1,-q.y*p.x,-q.y*p.y,q.y]); });
    for (let column=0;column<8;column++) {
        let pivot=column;
        for(let row=column+1;row<8;row++) if(Math.abs(matrix[row][column])>Math.abs(matrix[pivot][column])) pivot=row;
        [matrix[pivot],matrix[column]]=[matrix[column],matrix[pivot]];
        const divisor=matrix[column][column];
        if(Math.abs(divisor)<1e-8) throw new Error('No se pudo alinear la cartilla. Vuelve a tomar la foto.');
        matrix[column]=matrix[column].map(v=>v/divisor);
        for(let row=0;row<8;row++) if(row!==column) { const factor=matrix[row][column]; matrix[row]=matrix[row].map((v,i)=>v-factor*matrix[column][i]); }
    }
    const h=matrix.map(row=>row[8]);
    return (x,y)=> { const divisor=h[6]*x+h[7]*y+1; return {x:(h[0]*x+h[1]*y+h[2])/divisor,y:(h[3]*x+h[4]*y+h[5])/divisor}; };
}
function choose(scores: number[]): { index: number | null; state: OmrAnswer['estado'] } {
    const filled=scores.map((score,index)=>({score,index})).filter(v=>v.score>=.62);
    if(filled.length>1) return {index:null,state:'multiple'};
    if(filled.length===1 && scores.every((s,i)=>i===filled[0].index || s<.3)) return {index:filled[0].index,state:'valida'};
    if(scores.every(s=>s<.3)) return {index:null,state:'blanco'};
    return {index:null,state:'dudosa'};
}
export function readOmr(bitmap: Bitmap, layout: OmrLayout): OmrReading {
    if(!layout || layout.diseno!=='compacto' || !layout.dni || !layout.grupo || layout.alignment_marks.length!==4) throw new Error('La lectura automática requiere una plantilla compacta nueva con DNI y marcas de referencia.');
    const points=markers(bitmap);
    const source=layout.alignment_marks.map(([x,y])=>({x:x+layout.alignment_size/2,y:y+layout.alignment_size/2}));
    const transform=project(source,points);
    function score(x:number,y:number,r:number): number {
        let marked=0,total=0;
        for(let dx=-7;dx<=7;dx++) for(let dy=-7;dy<=7;dy++) { if(dx*dx+dy*dy>49 || dx*dx+dy*dy<25) continue; const point=transform(x+dx*r*.82/7,y+dy*r*.82/7); if(point.x<0||point.y<0||point.x>=bitmap.width||point.y>=bitmap.height) throw new Error('Parte de la cartilla está fuera de la foto.'); total++; if(black(bitmap,point.x,point.y)) marked++; }
        return marked/total;
    }
    const digits=Array.from({length:8},(_,column)=>choose(Array.from({length:10},(_,digit)=>score(layout.dni.x+column*layout.dni.paso_x,layout.dni.y+digit*layout.dni.paso_y,layout.dni.radio))));
    const dni=digits.every(d=>d.state==='valida') ? digits.map(d=>d.index).join('') : null;
    const group=choose(Array.from({length:4},(_,i)=>score(layout.grupo.x+i*layout.grupo.paso_x,layout.grupo.y,layout.grupo.radio)));
    const respuestas=layout.questions.map(question=> { const result=choose(question.opciones.map(option=>score(option.x,option.y,layout.bubble_radius))); return {numero_pregunta:question.numero_pregunta,marcada:result.index===null?null:question.opciones[result.index].label,estado:result.state}; });
    return {dni,grupo:group.index===null?null:String.fromCharCode(65+group.index),respuestas,confianza:dni && group.state==='valida' && !respuestas.some(a=>a.estado==='dudosa'||a.estado==='multiple') ? 95 : 0};
}
export async function readOmrFile(file: File, layout: OmrLayout): Promise<OmrReading> {
    const bitmap=await createImageBitmap(file);
    try {
        const scale=Math.min(1,1600/Math.max(bitmap.width,bitmap.height));
        const canvas=document.createElement('canvas'); canvas.width=Math.round(bitmap.width*scale); canvas.height=Math.round(bitmap.height*scale);
        const context=canvas.getContext('2d',{willReadFrequently:true});
        if(!context) throw new Error('Tu navegador no permite analizar la imagen. Puedes enviarla para revisión manual.');
        context.drawImage(bitmap,0,0,canvas.width,canvas.height);
        return readOmr(context.getImageData(0,0,canvas.width,canvas.height),layout);
    } finally { bitmap.close(); }
}
