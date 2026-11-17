import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readOmr } from '../resources/js/lib/omr-reader.ts';
const layout={diseno:'compacto',page:{width:1200,height:900},alignment_marks:[[6,6],[1182,6],[6,882],[1182,882]],alignment_size:12,bubble_radius:12,dni:{columnas:8,x:41.25,y:210,paso_x:42.5,paso_y:43,radio:12},grupo:{x:140,y:804,paso_x:50,radio:15},questions:Array.from({length:80},(_,i)=>({numero_pregunta:i+1,opciones:Array.from({length:4},(_,j)=>({label:String.fromCharCode(65+j),x:435+Math.floor(i/20)*200+j*38,y:200+(i%20)*32}))}))};
function photo({dni='80000001',doubles=false,missing=false,scale=1}={}) {
 const width=Math.round(1200*scale),height=Math.round(900*scale),data=new Uint8ClampedArray(width*height*4).fill(255);
 function paint(x,y,rx,ry){for(let py=Math.floor((y-ry)*scale);py<=(y+ry)*scale;py++)for(let px=Math.floor((x-rx)*scale);px<=(x+rx)*scale;px++){if(px>=0&&py>=0&&px<width&&py<height){const k=(py*width+px)*4;data[k]=data[k+1]=data[k+2]=0;}}}
 layout.alignment_marks.forEach(([x,y],i)=>{if(!missing||i!==3)paint(x+6,y+6,5.5,5.5)});
 function fill(x,y,r=12){for(let dy=-r;dy<=r;dy++)for(let dx=-r;dx<=r;dx++)if(dx*dx+dy*dy<=r*r)paint(x+dx,y+dy,.3,.3);}
 [...dni].forEach((digit,i)=>fill(41.25+i*42.5,210+Number(digit)*43));fill(190,804,15);
 layout.questions.forEach(q=>{const o=q.opciones[(q.numero_pregunta-1)%4];fill(o.x,o.y);});
 if(doubles)fill(layout.questions[0].opciones[1].x,200);
 return {width,height,data};
}
test('identifica DNI, grupo B y las 80 respuestas',()=>{const r=readOmr(photo(),layout);assert.equal(r.dni,'80000001');assert.equal(r.grupo,'B');assert.equal(r.respuestas.length,80);r.respuestas.forEach((a,i)=>assert.deepEqual(a,{numero_pregunta:i+1,marcada:String.fromCharCode(65+i%4),estado:'valida'}));});
test('conserva los ceros iniciales y funciona con imagen reducida',()=>{assert.equal(readOmr(photo({dni:'00123456',scale:.75}),layout).dni,'00123456');});
test('no adivina una respuesta con doble marca',()=>{assert.deepEqual(readOmr(photo({doubles:true}),layout).respuestas[0],{numero_pregunta:1,marcada:null,estado:'multiple'});});
test('rechaza foto sin cuatro referencias',()=>{assert.throws(()=>readOmr(photo({missing:true}),layout),/cuatro marcas/);});
test('las letras impresas dentro de burbujas sin rellenar no se leen como marcas',()=>{
 const bitmap=photo();
 for(const q of layout.questions)for(const o of q.opciones){
  // Simula la caja negra de un carácter de 15px, sin pintar el anillo de lectura.
  for(let y=o.y-5;y<=o.y+5;y++)for(let x=o.x-4;x<=o.x+4;x++){const k=(Math.round(y)*bitmap.width+Math.round(x))*4;bitmap.data[k]=bitmap.data[k+1]=bitmap.data[k+2]=0;}
 }
 const reading=readOmr(bitmap,layout);assert.ok(reading.respuestas.every(a=>a.estado==='valida'));
});
